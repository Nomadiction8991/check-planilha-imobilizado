<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

require_once dirname(__DIR__, 2) . '/services/produto_parser_service.php';
$pp_config = require dirname(__DIR__, 3) . '/config/parser/produto_parser_config.php';

use voku\helper\UTF8;
use PhpOffice\PhpSpreadsheet\IOFactory;

const IP_BATCH_SIZE = 200;
const IP_JOB_DIR = __DIR__ . '/../../../storage/tmp';

// --- Funções utilitárias ---
function ip_corrige_encoding($texto) {
    if ($texto === null) return '';
    $texto = trim((string)$texto);
    if ($texto === '') return '';
    $texto = UTF8::fix_utf8($texto);
    $texto = UTF8::remove_invisible_characters($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return trim($texto);
}

function ip_fix_mojibake($texto) {
    if ($texto === null) return '';
    $texto = (string)$texto;
    if ($texto === '') return '';
    return UTF8::fix_utf8($texto);
}

function ip_to_uppercase($texto) {
    if ($texto === null || $texto === '') return '';
    $texto = UTF8::fix_utf8((string)$texto);
    return UTF8::strtoupper($texto);
}

function ip_job_path(string $jobId): string {
    return IP_JOB_DIR . '/import_job_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId) . '.json';
}

function ip_save_job(array $job): void {
    if (!is_dir(IP_JOB_DIR)) {
        @mkdir(IP_JOB_DIR, 0775, true);
    }
    file_put_contents(ip_job_path($job['id']), json_encode($job));
}

function ip_load_job(string $jobId): ?array {
    $path = ip_job_path($jobId);
    if (!is_file($path)) {
        return null;
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

function ip_remove_job(string $jobId): void {
    $path = ip_job_path($jobId);
    if (is_file($path)) {
        @unlink($path);
    }
}

function ip_response_json(array $payload, int $status = 200): void {
    json_response($payload, $status);
}

function ip_purge_old_jobs(): void {
    if (!is_dir(IP_JOB_DIR)) {
        return;
    }
    $patterns = [
        IP_JOB_DIR . '/import_job_*.json',
        IP_JOB_DIR . '/upload_import_*.csv',
    ];
    foreach ($patterns as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }
}

function ip_cleanup_job_resources(array $job): void {
    if (!empty($job['file_path']) && is_file($job['file_path'])) {
        @unlink($job['file_path']);
    }
    if (!empty($job['id'])) {
        ip_remove_job($job['id']);
    }
}

function ip_prepare_job(array $job, PDO $conexao, array $pp_config): array {
    $posicao_data = $job['posicao_data'] ?? 'D13';
    $pulo_linhas = (int)($job['pulo_linhas'] ?? 25);
    $coluna_localidade = strtoupper(trim($job['coluna_localidade'] ?? 'K'));
    $mapeamento_codigo = strtoupper(trim($job['mapeamento_codigo'] ?? 'A'));
    $mapeamento_complemento = strtoupper(trim($job['mapeamento_complemento'] ?? 'D'));
    $mapeamento_dependencia = strtoupper(trim($job['mapeamento_dependencia'] ?? 'P'));

    $planilha = IOFactory::load($job['file_path']);
    $aba = $planilha->getActiveSheet();

    $valor_data = trim((string)$aba->getCell($posicao_data)->getCalculatedValue());
    $data_mysql = null;
    if ($valor_data !== '') {
        if (is_numeric($valor_data)) {
            $data_mysql = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor_data)->format('Y-m-d');
        } else {
            $ts = strtotime($valor_data);
            if ($ts !== false) {
                $data_mysql = date('Y-m-d', $ts);
            }
        }
    }

    $linhas = $aba->toArray();
    $linha_atual = 0;
    $registros_candidatos = 0;
    $dependencias_unicas = [];
    $localidades_unicas = [];

    $idx_codigo = pp_colunaParaIndice($mapeamento_codigo);
    $idx_complemento = pp_colunaParaIndice($mapeamento_complemento);
    $idx_dependencia = pp_colunaParaIndice($mapeamento_dependencia);
    $idx_localidade = pp_colunaParaIndice($coluna_localidade);

    foreach ($linhas as $linha) {
        $linha_atual++;
        if ($linha_atual <= $pulo_linhas) { continue; }
        if (empty(array_filter($linha))) { continue; }
        $codigo_tmp = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
        if ($codigo_tmp !== '') { $registros_candidatos++; }

        if (isset($linha[$idx_dependencia])) {
            $dep_raw = ip_fix_mojibake(ip_corrige_encoding($linha[$idx_dependencia]));
            $dep_norm = pp_normaliza($dep_raw);
            if ($dep_norm !== '' && !array_key_exists($dep_norm, $dependencias_unicas)) {
                $dependencias_unicas[$dep_norm] = $dep_raw;
            }
        }

        if (isset($linha[$idx_localidade])) {
            $localidade_raw = (string)$linha[$idx_localidade];
            $localidade_num = preg_replace('/\D+/', '', $localidade_raw);
            if ($localidade_num !== '') {
                $codigo_localidade = (int)$localidade_num;
                if (!in_array($codigo_localidade, $localidades_unicas, true)) {
                    $localidades_unicas[] = $codigo_localidade;
                }
            }
        }
    }

    if ($registros_candidatos === 0) {
        throw new Exception('Nenhuma linha de produto encontrada após o cabeçalho. Verifique o mapeamento de colunas e o número de linhas a pular.');
    }

    if (empty($localidades_unicas)) {
        throw new Exception('Nenhum código de localidade encontrado na coluna ' . $coluna_localidade . '.');
    }

    foreach ($dependencias_unicas as $dep_desc) {
        try {
            $dep_desc_upper = ip_to_uppercase($dep_desc);
            $stmtDep = $conexao->prepare('SELECT id FROM dependencias WHERE descricao = :descricao');
            $stmtDep->bindValue(':descricao', $dep_desc_upper);
            $stmtDep->execute();
            $existeDep = $stmtDep->fetch(PDO::FETCH_ASSOC);
            if (!$existeDep) {
                $stmtInsertDep = $conexao->prepare('INSERT INTO dependencias (descricao) VALUES (:descricao)');
                $stmtInsertDep->bindValue(':descricao', $dep_desc_upper);
                $stmtInsertDep->execute();
            }
        } catch (Throwable $e) {
            // ignora duplicidade e segue
        }
    }

    $dep_map = [];
    $stmtDepAll = $conexao->query('SELECT id, descricao FROM dependencias');
    foreach ($stmtDepAll->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $dep_map[pp_normaliza($d['descricao'])] = (int)$d['id'];
    }

    $comum_processado_id = null;
    $comuns_existentes = 0;
    $comuns_cadastradas = 0;
    $comuns_falha = [];

    foreach ($localidades_unicas as $codLoc) {
        try {
            $stmtBuscaComum = $conexao->prepare('SELECT id FROM comums WHERE codigo = :codigo');
            $stmtBuscaComum->bindValue(':codigo', $codLoc, PDO::PARAM_INT);
            $stmtBuscaComum->execute();
            $comumEncontrado = $stmtBuscaComum->fetch(PDO::FETCH_ASSOC);

            if ($comumEncontrado) {
                if ($comum_processado_id === null) {
                    $comum_processado_id = (int)$comumEncontrado['id'];
                }
                $comuns_existentes++;
            } else {
                $novoId = garantir_comum_por_codigo($conexao, $codLoc);
                if ($comum_processado_id === null) {
                    $comum_processado_id = (int)$novoId;
                }
                $comuns_cadastradas++;
            }
        } catch (Throwable $e) {
            $comuns_falha[] = $codLoc;
        }
    }

    if (empty($comum_processado_id)) {
        throw new Exception('Nenhum comum válido encontrado ou criado a partir da coluna de localidade.');
    }

    $mapeamento_colunas_str = 'codigo=' . $mapeamento_codigo . ';complemento=' . $mapeamento_complemento . ';dependencia=' . $mapeamento_dependencia . ';localidade=' . $coluna_localidade;

    $stmtCfg = $conexao->prepare('REPLACE INTO configuracoes (id, mapeamento_colunas, posicao_data, pulo_linhas) VALUES (1, :mapeamento_colunas, :posicao_data, :pulo_linhas)');
    $stmtCfg->bindValue(':mapeamento_colunas', $mapeamento_colunas_str);
    $stmtCfg->bindValue(':posicao_data', $posicao_data);
    $stmtCfg->bindValue(':pulo_linhas', $pulo_linhas);
    $stmtCfg->execute();

    $tipos_bens = [];
    $stmtTipos = $conexao->prepare('SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC');
    if ($stmtTipos->execute()) {
        $tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtProdExist = $conexao->prepare('SELECT * FROM produtos WHERE comum_id = :comum_id');
    $stmtProdExist->bindValue(':comum_id', $comum_processado_id, PDO::PARAM_INT);
    $stmtProdExist->execute();
    $produtos_existentes = [];
    while ($p = $stmtProdExist->fetch(PDO::FETCH_ASSOC)) {
        $key = pp_normaliza((string)$p['codigo']);
        $produtos_existentes[$key] = $p;
    }

    $stmtMaxId = $conexao->query('SELECT COALESCE(MAX(id_produto), 0) AS max_id FROM produtos');
    $id_produto_sequencial = (int)($stmtMaxId->fetchColumn() ?? 0) + 1;

    $job['linhas'] = $linhas;
    $job['cursor'] = 0;
    $job['pulo_linhas'] = $pulo_linhas;
    $job['idx_codigo'] = $idx_codigo;
    $job['idx_complemento'] = $idx_complemento;
    $job['idx_dependencia'] = $idx_dependencia;
    $job['idx_localidade'] = $idx_localidade;
    $job['registros_candidatos'] = $registros_candidatos;
    $job['comum_processado_id'] = $comum_processado_id;
    $job['dep_map'] = $dep_map;
    $job['tipos_bens'] = $tipos_bens;
    $job['produtos_existentes'] = $produtos_existentes;
    $job['id_produto_sequencial'] = $id_produto_sequencial;
    $job['codigos_processados'] = [];
    $job['stats'] = [
        'novos' => 0,
        'atualizados' => 0,
        'excluidos' => 0,
        'processados' => 0,
    ];
    $job['erros_produtos'] = [];
    $job['status'] = 'ready';
    $job['data_mysql'] = $data_mysql;

    ip_save_job($job);
    return $job;
}

// --- Fluxo principal ---
$action = $_GET['action'] ?? $_POST['action'] ?? 'start';

if ($action === 'process') {
    if (!is_ajax_request()) {
        ip_response_json(['message' => 'Acesso inválido.'], 400);
    }

    $jobId = $_GET['job'] ?? $_POST['job'] ?? '';
    $job = ip_load_job($jobId);
    if (!$job) {
        ip_response_json(['message' => 'Job de importação não encontrado ou expirado.'], 404);
    }

    if (($job['status'] ?? 'pending') === 'pending') {
        try {
            $job = ip_prepare_job($job, $conexao, $pp_config);
        } catch (Throwable $prepEx) {
            ip_cleanup_job_resources($job);
            ip_response_json(['message' => 'Falha ao preparar importação: ' . $prepEx->getMessage()], 500);
        }
    }

    // marca como em processamento para exibir retomada na tela de importação
    $job['status'] = 'processing';
    ip_save_job($job);

    @set_time_limit(0);

    try {
        $linhas = $job['linhas'];
        $totalLinhas = count($linhas);
        $inicio = (int)($job['cursor'] ?? 0);
        $fim = min($totalLinhas, $inicio + IP_BATCH_SIZE);

        $pulo_linhas = (int)$job['pulo_linhas'];
        $idx_codigo = (int)$job['idx_codigo'];
        $idx_complemento = (int)$job['idx_complemento'];
        $idx_dependencia = (int)$job['idx_dependencia'];
        $idx_localidade = (int)$job['idx_localidade'];

        $tipos_bens = $job['tipos_bens'];
        $tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

        $conexao->beginTransaction();

        $codigos_processados = $job['codigos_processados'] ?? [];
        $produtos_existentes = $job['produtos_existentes'] ?? [];
        $dep_map = $job['dep_map'] ?? [];
        $id_produto_sequencial = (int)$job['id_produto_sequencial'];
        $stats = $job['stats'];
        $erros_produtos = $job['erros_produtos'] ?? [];
        $comum_processado_id = (int)$job['comum_processado_id'];

        for ($i = $inicio; $i < $fim; $i++) {
            $linhaNumero = $i + 1;
            $linha = $linhas[$i];

            if ($linhaNumero <= $pulo_linhas) {
                continue;
            }
            if (empty(array_filter($linha))) {
                continue;
            }

            try {
                $codigo = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
                if ($codigo === '') {
                    continue;
                }
                $codigo_key = pp_normaliza($codigo);

                $complemento_original = isset($linha[$idx_complemento]) ? trim((string)$linha[$idx_complemento]) : '';
                $dependencia_original = isset($linha[$idx_dependencia]) ? ip_fix_mojibake(ip_corrige_encoding($linha[$idx_dependencia])) : '';

                $texto_base = $complemento_original;
                [$codigo_detectado, $texto_sem_prefixo] = pp_extrair_codigo_prefixo($texto_base);
                [$tipo_detectado, $texto_pos_tipo] = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
                $tipo_bem_id = (int)$tipo_detectado['id'];
                $tipo_bem_codigo = $tipo_detectado['codigo'];
                $tipo_bem_desc = $tipo_detectado['descricao'];

                $aliases_tipo_atual = null;
                $aliases_originais = null;
                if ($tipo_bem_id) {
                    foreach ($tipos_aliases as $tbTmp) {
                        if ($tbTmp['id'] === $tipo_bem_id) {
                            $aliases_tipo_atual = $tbTmp['aliases'];
                            $aliases_originais = $tbTmp['aliases_originais'] ?? null;
                            break;
                        }
                    }
                }

                [$ben_raw, $comp_raw] = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
                $ben = ip_to_uppercase(preg_replace('/\s+/', ' ', trim(ip_fix_mojibake(ip_corrige_encoding($ben_raw)))));
                $complemento_limpo = ip_to_uppercase(preg_replace('/\s+/', ' ', trim(ip_fix_mojibake(ip_corrige_encoding($comp_raw)))));

                $ben_valido = false;
                if ($ben !== '' && $tipo_bem_id > 0 && $aliases_tipo_atual) {
                    $ben_norm = pp_normaliza($ben);
                    foreach ($aliases_tipo_atual as $alias_norm) {
                        if ($alias_norm === $ben_norm || pp_match_fuzzy($ben, $alias_norm)) {
                            $ben_valido = true;
                            break;
                        }
                    }
                }

                if (!$ben_valido && $tipo_bem_id > 0 && !empty($aliases_tipo_atual)) {
                    foreach ($aliases_tipo_atual as $alias_norm) {
                        if ($alias_norm !== '') {
                            $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_bem_desc));
                            foreach ($tokens as $tok) {
                                if (pp_normaliza($tok) === $alias_norm) {
                                    $ben = ip_to_uppercase(preg_replace('/\s+/', ' ', trim(ip_fix_mojibake(ip_corrige_encoding($tok)))));
                                    $ben_valido = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if ($ben === '' && $complemento_limpo === '') {
                    $complemento_limpo = ip_to_uppercase(preg_replace('/\s+/', ' ', trim(ip_fix_mojibake(ip_corrige_encoding($texto_sem_prefixo)))));
                    if ($complemento_limpo === '') {
                        $complemento_limpo = ip_to_uppercase(preg_replace('/\s+/', ' ', trim(ip_fix_mojibake(ip_corrige_encoding($complemento_original)))));
                    }
                }

                if ($ben !== '' && $complemento_limpo !== '') {
                    $complemento_limpo = pp_remover_ben_do_complemento($ben, $complemento_limpo);
                }

                $dependencia_rotulo = ip_to_uppercase(ip_fix_mojibake(ip_corrige_encoding($dependencia_original)));
                $dependencia_id = 0;
                $dep_key = pp_normaliza($dependencia_rotulo);
                if ($dep_key !== '') {
                    if (isset($dep_map[$dep_key])) {
                        $dependencia_id = $dep_map[$dep_key];
                    } else {
                        $stmtDepIns = $conexao->prepare('INSERT INTO dependencias (descricao) VALUES (:d)');
                        $stmtDepIns->bindValue(':d', $dependencia_rotulo);
                        if ($stmtDepIns->execute()) {
                            $dependencia_id = (int)$conexao->lastInsertId();
                            $dep_map[$dep_key] = $dependencia_id;
                        }
                    }
                }

                $descricao_completa_calc = pp_montar_descricao(1, $tipo_bem_codigo, $tipo_bem_desc, $ben, $complemento_limpo, $dependencia_rotulo, $pp_config);

                $tem_erro_parsing = ($tipo_bem_id === 0 && $codigo_detectado !== null) || ($tipo_bem_id > 0 && $ben !== '' && !$ben_valido);

                $codigos_processados[$codigo_key] = true;

                if (isset($produtos_existentes[$codigo_key])) {
                    $prodExist = $produtos_existentes[$codigo_key];
                    $sql_update_prod = 'UPDATE produtos SET '
                        . 'descricao_completa = :descricao_completa, '
                        . 'complemento = :complemento, '
                        . 'bem = :bem, '
                        . 'dependencia_id = :dependencia_id, '
                        . 'editado_dependencia_id = 0, '
                        . 'tipo_bem_id = :tipo_bem_id, '
                        . 'comum_id = :comum_id '
                        . 'WHERE id_produto = :id_produto';
                    $stmtUp = $conexao->prepare($sql_update_prod);
                    $stmtUp->bindValue(':descricao_completa', ip_to_uppercase($descricao_completa_calc));
                    $stmtUp->bindValue(':complemento', ip_to_uppercase($complemento_limpo));
                    $stmtUp->bindValue(':bem', ip_to_uppercase($ben));
                    $stmtUp->bindValue(':dependencia_id', $dependencia_id, PDO::PARAM_INT);
                    $stmtUp->bindValue(':tipo_bem_id', $tipo_bem_id, PDO::PARAM_INT);
                    $stmtUp->bindValue(':comum_id', $comum_processado_id, PDO::PARAM_INT);
                    $stmtUp->bindValue(':id_produto', $prodExist['id_produto'], PDO::PARAM_INT);
                    if ($stmtUp->execute()) {
                        $stats['atualizados']++;
                    } else {
                        $err = $stmtUp->errorInfo();
                        throw new Exception($err[2] ?? 'Erro ao atualizar produto existente');
                    }
                } else {
                    $obs_prefix = $tem_erro_parsing ? '[REVISAR] ' : '';
                    $sql_produto = <<<SQL
INSERT INTO produtos (
    comum_id, id_produto, codigo, descricao_completa, editado_descricao_completa,
    tipo_bem_id, editado_tipo_bem_id, bem, editado_bem,
    complemento, editado_complemento, dependencia_id, editado_dependencia_id,
    checado, editado, imprimir_etiqueta, imprimir_14_1,
    observacao, ativo, novo, condicao_14_1,
    administrador_acessor_id, doador_conjugue_id
) VALUES (
    :comum_id, :id_produto, :codigo, :descricao_completa, '',
    :tipo_bem_id, 0, :bem, '',
    :complemento, '', :dependencia_id, 0,
    0, 0, 0, :imprimir_14_1,
    :observacao, 1, 0, :condicao_14_1,
    0, 0
)
SQL;
                    $stmt_prod = $conexao->prepare($sql_produto);
                    $stmt_prod->bindValue(':comum_id', $comum_processado_id, PDO::PARAM_INT);
                    $stmt_prod->bindValue(':id_produto', $id_produto_sequencial, PDO::PARAM_INT);
                    $stmt_prod->bindValue(':codigo', $codigo);
                    $stmt_prod->bindValue(':descricao_completa', ip_to_uppercase($descricao_completa_calc));
                    $stmt_prod->bindValue(':tipo_bem_id', $tipo_bem_id, PDO::PARAM_INT);
                    $stmt_prod->bindValue(':bem', ip_to_uppercase($ben));
                    $stmt_prod->bindValue(':complemento', ip_to_uppercase($complemento_limpo));
                    $stmt_prod->bindValue(':dependencia_id', $dependencia_id, PDO::PARAM_INT);
                    $stmt_prod->bindValue(':imprimir_14_1', 0, PDO::PARAM_INT);
                    $stmt_prod->bindValue(':observacao', mb_strtoupper($obs_prefix, 'UTF-8'));
                    $stmt_prod->bindValue(':condicao_14_1', '2');
                    if ($stmt_prod->execute()) {
                        $stats['novos']++;
                        $id_produto_sequencial++;
                    } else {
                        $err = $stmt_prod->errorInfo();
                        $erro_msg = 'Linha ' . $linhaNumero . ': ' . ($err[2] ?? 'Erro desconhecido no INSERT');
                        $erros_produtos[] = $erro_msg;
                        error_log('ERRO INSERT PRODUTO: ' . json_encode($err));
                    }
                }

                $stats['processados']++;
            } catch (Exception $e) {
                $erro_msg = 'Linha ' . $linhaNumero . ': ' . $e->getMessage();
                $erros_produtos[] = $erro_msg;
                error_log('Erro linha ' . $linhaNumero . ': ' . $e->getMessage());
            }
        }

        $jobDone = ($fim >= $totalLinhas);

        if ($jobDone) {
            foreach ($produtos_existentes as $key => $prod) {
                if (!isset($codigos_processados[$key])) {
                    $stmtDel = $conexao->prepare('DELETE FROM produtos WHERE id_produto = :id_produto');
                    $stmtDel->bindValue(':id_produto', $prod['id_produto'], PDO::PARAM_INT);
                    if ($stmtDel->execute()) {
                        $stats['excluidos']++;
                    }
                }
            }
        }

        $conexao->commit();

        $job['cursor'] = $fim;
        $job['codigos_processados'] = $codigos_processados;
        $job['dep_map'] = $dep_map;
        $job['id_produto_sequencial'] = $id_produto_sequencial;
        $job['stats'] = $stats;
        $job['erros_produtos'] = $erros_produtos;
        $job['produtos_existentes'] = $produtos_existentes;

        if ($jobDone) {
            $mensagem = 'Importação concluída! Novos: ' . $stats['novos'] . ', Atualizados: ' . $stats['atualizados'] . ', Excluídos: ' . $stats['excluidos'] . '.';
            if (!empty($erros_produtos)) {
                $mensagem .= ' Erros em ' . count($erros_produtos) . ' linha(s).';
            }

            $_SESSION['mensagem'] = $mensagem;
            $_SESSION['tipo_mensagem'] = empty($erros_produtos) ? 'success' : 'warning';

            ip_cleanup_job_resources($job);

            ip_response_json([
                'done' => true,
                'success' => empty($erros_produtos),
                'message' => $mensagem,
                'errors' => $erros_produtos,
                'redirect' => '/app/views/planilhas/planilha_importar.php'
            ]);
        }

        ip_save_job($job);

        $percent = 0;
        if ($job['registros_candidatos'] > 0) {
            $percent = min(100, round(($job['stats']['processados'] / $job['registros_candidatos']) * 100, 2));
        }

        ip_response_json([
            'done' => false,
            'progress' => $percent,
            'stats' => $job['stats'],
            'total' => $job['registros_candidatos'],
            'processed' => $job['stats']['processados'],
            'errors' => $erros_produtos,
        ]);
    } catch (Throwable $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        ip_response_json(['message' => 'Falha ao processar lote: ' . $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'start') {
    @set_time_limit(0);

    $arquivo_csv = $_FILES['arquivo_csv'] ?? null;
    $posicao_data = trim($_POST['posicao_data'] ?? 'D13');
    $pulo_linhas = (int)($_POST['pulo_linhas'] ?? 25);
    $coluna_localidade = strtoupper(trim($_POST['coluna_localidade'] ?? 'K'));
    $mapeamento_codigo = strtoupper(trim($_POST['mapeamento_codigo'] ?? 'A'));
    $mapeamento_complemento = strtoupper(trim($_POST['mapeamento_complemento'] ?? 'D'));
    $mapeamento_dependencia = strtoupper(trim($_POST['mapeamento_dependencia'] ?? 'P'));
    $debug_import = isset($_POST['debug_import']);

    try {
        if (!$arquivo_csv || $arquivo_csv['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }
        $extensao = strtolower(pathinfo($arquivo_csv['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        ip_purge_old_jobs();

        $jobId = uniqid('import_', true);
        if (!is_dir(IP_JOB_DIR)) {
            @mkdir(IP_JOB_DIR, 0775, true);
        }
        $destino = IP_JOB_DIR . '/upload_' . $jobId . '.csv';
        if (!move_uploaded_file($arquivo_csv['tmp_name'], $destino)) {
            throw new Exception('Não foi possível armazenar o arquivo enviado.');
        }
        $job = [
            'id' => $jobId,
            'file_path' => $destino,
            'created_at' => time(),
            'status' => 'pending',
            'posicao_data' => $posicao_data,
            'pulo_linhas' => $pulo_linhas,
            'coluna_localidade' => $coluna_localidade,
            'mapeamento_codigo' => $mapeamento_codigo,
            'mapeamento_complemento' => $mapeamento_complemento,
            'mapeamento_dependencia' => $mapeamento_dependencia,
            'registros_candidatos' => 0,
            'linhas' => [],
            'cursor' => 0,
            'stats' => [
                'novos' => 0,
                'atualizados' => 0,
                'excluidos' => 0,
                'processados' => 0,
            ],
            'erros_produtos' => [],
            'debug' => [
                'requested_debug' => $debug_import,
            ],
        ];

        ip_save_job($job);

        header('Location: /app/views/planilhas/importacao_progresso.php?job=' . urlencode($jobId));
        exit;
    } catch (Exception $e) {
        $_SESSION['mensagem'] = 'Erro: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: /app/views/planilhas/planilha_importar.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /app/views/planilhas/planilha_importar.php');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=/app/views/planilhas/planilha_importar.php"></head><body>';
    echo '<p>Redirecionando para o formulário de importação... Se não for redirecionado automaticamente, <a href="/app/views/planilhas/planilha_importar.php">clique aqui</a>.</p>';
    echo '</body></html>';
    exit;
}

if ($action === 'cancel') {
    if (!is_ajax_request()) {
        ip_response_json(['message' => 'Acesso inválido.'], 400);
    }
    $jobId = $_GET['job'] ?? $_POST['job'] ?? '';
    $job = ip_load_job($jobId);
    if (!$job) {
        ip_response_json(['message' => 'Job de importação não encontrado ou expirado.'], 404);
    }

    ip_cleanup_job_resources($job);
    $_SESSION['mensagem'] = 'Importação cancelada pelo usuário.';
    $_SESSION['tipo_mensagem'] = 'warning';

    ip_response_json([
        'canceled' => true,
        'redirect' => '/app/views/planilhas/planilha_importar.php'
    ]);
}

ip_response_json(['message' => 'Ação inválida ou método não suportado.'], 400);


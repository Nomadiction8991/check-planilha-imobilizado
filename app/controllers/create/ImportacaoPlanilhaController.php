<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

require_once dirname(__DIR__, 2) . '/services/produto_parser_service.php';
$pp_config = require dirname(__DIR__, 3) . '/config/parser/produto_parser_config.php';

use voku\helper\UTF8;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

const IP_BATCH_SIZE = 200;
const IP_JOB_DIR = __DIR__ . '/../../../storage/tmp';
const IP_LOG_LIMIT = 300;
const IP_LOCK_FILE = IP_JOB_DIR . '/importacao.lock';

class ImportacaoReadFilter implements IReadFilter
{
    private array $columns;
    private string $dataColumn;
    private int $dataRow;

    public function __construct(array $columns, string $dataColumn, int $dataRow)
    {
        $this->columns = array_map('strtoupper', array_values(array_filter($columns, fn($col) => $col !== '')));
        $this->dataColumn = strtoupper($dataColumn) ?: 'A';
        $this->dataRow = max(1, $dataRow);
    }

    public function readCell($column, $row, $worksheetName = ''): bool
    {
        $column = strtoupper($column);
        if ($column === $this->dataColumn && $row === $this->dataRow) {
            return true;
        }
        return in_array($column, $this->columns, true);
    }
}

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

function ip_append_log(array &$job, string $level, string $message): void {
    if (!isset($job['log']) || !is_array($job['log'])) {
        $job['log'] = [];
    }
    $job['log'][] = [
        'ts' => time(),
        'level' => $level,
        'message' => $message,
    ];
    if (count($job['log']) > IP_LOG_LIMIT) {
        $job['log'] = array_slice($job['log'], -IP_LOG_LIMIT);
    }
}

function ip_parse_planilha_data($valor): ?string {
    if ($valor instanceof DateTimeInterface) {
        return $valor->format('Y-m-d');
    }

    if (is_numeric($valor)) {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)->format('Y-m-d');
        } catch (Throwable $e) {
            // segue para outros formatos
        }
    }

    $valor_str = trim((string)$valor);
    if ($valor_str === '') {
        return null;
    }

    $valor_normalizado = str_replace(['.', '-'], '/', $valor_str);
    $formatos = ['d/m/Y', 'd/m/y', 'd/m/Y H:i', 'd/m/Y H:i:s'];
    foreach ($formatos as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $valor_normalizado);
        if ($dt && $dt->format($fmt) === $valor_normalizado) {
            return $dt->format('Y-m-d');
        }
    }

    $ts = strtotime($valor_normalizado);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return null;
}

function ip_obter_codigo_comum($valor): int {
    $texto = trim((string)$valor);
    if ($texto === '') {
        return 0;
    }

    $codigo = extrair_codigo_comum($texto);
    if ($codigo > 0) {
        return $codigo;
    }

    $apenas_digitos = preg_replace('/\D+/', '', $texto);
    if ($apenas_digitos === '') {
        return 0;
    }

    return (int)$apenas_digitos;
}

function ip_job_path(string $jobId): string {
    return IP_JOB_DIR . '/import_job_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId) . '.json';
}

function ip_acquire_import_lock(bool $nonBlocking = true) {
    if (!is_dir(IP_JOB_DIR)) {
        @mkdir(IP_JOB_DIR, 0775, true);
    }
    $fp = @fopen(IP_LOCK_FILE, 'c+');
    if (!$fp) {
        return null;
    }

    $op = LOCK_EX;
    if ($nonBlocking) {
        $op |= LOCK_NB;
    }

    if (!flock($fp, $op)) {
        fclose($fp);
        return null;
    }

    @ftruncate($fp, 0);
    @fwrite($fp, json_encode(['pid' => getmypid(), 'ts' => time()], JSON_UNESCAPED_UNICODE));
    @fflush($fp);
    return $fp;
}

function ip_release_import_lock($fp): void {
    if (is_resource($fp)) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
    }
}

function ip_save_job(array $job): void {
    if (!is_dir(IP_JOB_DIR)) {
        @mkdir(IP_JOB_DIR, 0775, true);
    }

    $path = ip_job_path($job['id']);
    $json = json_encode($job, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"error":"Falha ao serializar job"}';
    }

    $rand = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
    $tmp = $path . '.tmp.' . $rand;

    file_put_contents($tmp, $json, LOCK_EX);
    @rename($tmp, $path);
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

function ip_response_and_release($lock, array $payload, int $status = 200): void {
    if (isset($lock)) {
        ip_release_import_lock($lock);
    }
    ip_response_json($payload, $status);
}

function ip_is_csv(string $filePath): bool {
    return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv';
}

function ip_detect_csv_delimiter(string $filePath): string {
    $candidates = [',', ';', "\t", '|'];
    $best = ',';
    $bestMedian = -1;
    $bestVar = PHP_INT_MAX;

    foreach ($candidates as $delim) {
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delim);

        $counts = [];
        $sampled = 0;
        foreach ($file as $row) {
            if ($row === null || $row === [null]) {
                continue;
            }
            if (empty(array_filter((array)$row, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }
            $counts[] = count((array)$row);
            $sampled++;
            if ($sampled >= 10) {
                break;
            }
        }

        if (!$counts) {
            continue;
        }

        sort($counts);
        $median = $counts[(int)floor(count($counts) / 2)];
        $mean = array_sum($counts) / count($counts);
        $variance = 0.0;
        foreach ($counts as $count) {
            $variance += ($count - $mean) * ($count - $mean);
        }
        $variance = $variance / max(1, count($counts));

        if ($median > $bestMedian || ($median === $bestMedian && $variance < $bestVar)) {
            $bestMedian = $median;
            $bestVar = $variance;
            $best = $delim;
        }
    }

    return $best;
}

function ip_ensure_processed_table(PDO $conexao): void {
    $conexao->exec('CREATE TABLE IF NOT EXISTS import_job_processed (
        job_id VARCHAR(128) NOT NULL,
        id_produto INT NOT NULL,
        comum_id INT NOT NULL,
        PRIMARY KEY (job_id, id_produto),
        KEY idx_comum (comum_id, id_produto)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function ip_track_processed_ids(PDO $conexao, string $jobId, array $ids, int $comumId): void {
    $ids = array_values(array_filter(array_unique($ids), fn($pid) => (int)$pid > 0));
    if (empty($ids)) {
        return;
    }

    ip_ensure_processed_table($conexao);
    $chunkSize = 120;
    foreach (array_chunk($ids, $chunkSize) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '(?,?,?)'));
        $params = [];
        foreach ($chunk as $pid) {
            $params[] = $jobId;
            $params[] = (int)$pid;
            $params[] = (int)$comumId;
        }
        $sql = 'INSERT IGNORE INTO import_job_processed (job_id, id_produto, comum_id) VALUES ' . $placeholders;
        $stmt = $conexao->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log('[Importação] Falha ao registrar produtos processados (comum ' . $comumId . '): ' . $e->getMessage());
        }
    }
}

function ip_cleanup_processed_ids(PDO $conexao, string $jobId): void {
    $stmt = $conexao->prepare('DELETE FROM import_job_processed WHERE job_id = :job');
    $stmt->bindValue(':job', $jobId);
    $stmt->execute();
}

function ip_read_csv_batch(string $filePath, string $delimiter, int $startLine, int $batchSize): array {
    $file = new SplFileObject($filePath, 'r');
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    $file->setCsvControl($delimiter);

    if ($startLine > 0) {
        $file->seek($startLine);
    } else {
        $file->rewind();
    }

    $rows = [];
    while (!$file->eof() && count($rows) < $batchSize) {
        $lineIdx = $file->key();
        $row = $file->fgetcsv();
        if ($row === null || $row === [null]) {
            continue;
        }
        if (empty(array_filter((array)$row, fn($v) => trim((string)$v) !== ''))) {
            continue;
        }
        $rows[] = ['line' => $lineIdx + 1, 'row' => $row];
    }

    return [$rows, $file->key(), $file->eof()];
}

function ip_bulk_upsert_produtos(PDO $conexao, array $rows, int $chunkSize = 500): void {
    if (empty($rows)) {
        return;
    }

    $cols = [
        'comum_id','id_produto','codigo','descricao_completa','editado_descricao_completa',
        'tipo_bem_id','editado_tipo_bem_id','bem','editado_bem',
        'complemento','editado_complemento','dependencia_id','editado_dependencia_id',
        'checado','editado','imprimir_etiqueta','imprimir_14_1',
        'observacao','ativo','novo','condicao_14_1',
        'administrador_acessor_id','doador_conjugue_id'
    ];

    foreach (array_chunk($rows, $chunkSize) as $chunk) {
        $placeholders = [];
        $params = [];
        foreach ($chunk as $row) {
            $placeholders[] = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            foreach ($cols as $c) {
                $params[] = $row[$c];
            }
        }

        $sql = 'INSERT INTO produtos (' . implode(',', $cols) . ')
                VALUES ' . implode(',', $placeholders) . '
                ON DUPLICATE KEY UPDATE 
                    descricao_completa = VALUES(descricao_completa),
                    complemento = VALUES(complemento),
                    bem = VALUES(bem),
                    dependencia_id = VALUES(dependencia_id),
                    editado_dependencia_id = VALUES(editado_dependencia_id),
                    tipo_bem_id = VALUES(tipo_bem_id),
                    comum_id = VALUES(comum_id),
                    editado_descricao_completa = VALUES(editado_descricao_completa),
                    editado_bem = VALUES(editado_bem),
                    editado_complemento = VALUES(editado_complemento)';

        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
    }
}

function ip_delete_unprocessed(PDO $conexao, string $jobId): int {
    ip_ensure_processed_table($conexao);
    $sql = 'DELETE p FROM produtos p
            JOIN (SELECT DISTINCT comum_id FROM import_job_processed WHERE job_id = :job) c ON c.comum_id = p.comum_id
            LEFT JOIN import_job_processed t ON t.job_id = :job AND t.id_produto = p.id_produto
            WHERE t.id_produto IS NULL';
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':job', $jobId);
    $stmt->execute();
    return (int)$stmt->rowCount();
}

function ip_fetch_existentes_batch(PDO $conexao, array $codigos_norm, array $comum_ids): array {
    if (empty($codigos_norm)) {
        return [];
    }
    $codigos_norm = array_values(array_unique($codigos_norm));
    $comum_ids = array_values(array_unique($comum_ids));

    $placeCodes = implode(',', array_fill(0, count($codigos_norm), '?'));
    $placeComum = implode(',', array_fill(0, max(1, count($comum_ids)), '?'));
    $sql = 'SELECT * FROM produtos WHERE codigo IN (' . $placeCodes . ')';
    $params = $codigos_norm;
    if (!empty($comum_ids)) {
        $sql .= ' AND comum_id IN (' . $placeComum . ')';
        $params = array_merge($params, $comum_ids);
    }
    $stmt = $conexao->prepare($sql);
    foreach ($params as $i => $v) {
        $stmt->bindValue($i + 1, $v);
    }
    $stmt->execute();
    $map = [];
    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $codigo_norm = pp_normaliza((string)$p['codigo']);
        $key = ($p['comum_id'] ?? 0) . '|' . $codigo_norm;
        $map[$key] = $p;
    }
    return $map;
}

function ip_prescan_csv(array $job, PDO $conexao, array $pp_config): array {
    $filePath = $job['file_path'];
    $delimiter = $job['delimiter'] ?? ip_detect_csv_delimiter($filePath);
    $pulo_linhas = (int)($job['pulo_linhas'] ?? 25);
    $posicao_data = strtoupper(trim($job['posicao_data'] ?? 'D13'));
    if (!preg_match('/^([A-Z]+)(\d+)$/', $posicao_data, $matches)) {
        $matches = ['', 'D', '13'];
    }
    $data_column = $matches[1] ?: 'D';
    $data_row = max(1, (int)$matches[2]);

    $idx_codigo = pp_colunaParaIndice($job['mapeamento_codigo']);
    $idx_complemento = pp_colunaParaIndice($job['mapeamento_complemento']);
    $idx_dependencia = pp_colunaParaIndice($job['mapeamento_dependencia']);
    $idx_localidade = pp_colunaParaIndice($job['coluna_localidade']);
    $idx_data = pp_colunaParaIndice($data_column);

    $dep_norm_unicas = [];
    $dep_raw_unicas = [];
    $localidades_unicas = [];
    $registros_candidatos = 0;
    $total_linhas = 0;
    $valor_data = '';

    $file = new SplFileObject($filePath, 'r');
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
    $file->setCsvControl($delimiter);

    foreach ($file as $linha_idx => $linha) {
        if ($linha === null) { continue; }
        $total_linhas++;
        $linha_num = $linha_idx + 1; // 1-based
        if ($linha_num === $data_row && isset($linha[$idx_data])) {
            $valor_data = $linha[$idx_data];
        }
        if ($linha_num <= $pulo_linhas) { continue; }
        if (empty(array_filter((array)$linha, fn($v) => trim((string)$v) !== ''))) { continue; }

        $codigo_tmp = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
        if ($codigo_tmp !== '') {
            $registros_candidatos++;
        }

        if (isset($linha[$idx_dependencia])) {
            $dep_raw = ip_fix_mojibake(ip_corrige_encoding($linha[$idx_dependencia]));
            $dep_norm = pp_normaliza($dep_raw);
            if ($dep_norm !== '' && !isset($dep_norm_unicas[$dep_norm])) {
                $dep_norm_unicas[$dep_norm] = true;
                $dep_raw_unicas[] = $dep_raw;
            }
        }

        if (isset($linha[$idx_localidade])) {
            $codigo_localidade = ip_obter_codigo_comum($linha[$idx_localidade]);
            if ($codigo_localidade > 0 && !in_array($codigo_localidade, $localidades_unicas, true)) {
                $localidades_unicas[] = $codigo_localidade;
            }
        }
    }

    if ($registros_candidatos === 0) {
        throw new Exception('Nenhuma linha de produto encontrada após o cabeçalho. Verifique o mapeamento de colunas e o número de linhas a pular.');
    }

    $data_mysql = ip_parse_planilha_data($valor_data);
    $hoje = date('Y-m-d');
    if ($data_mysql === null || $data_mysql === '') {
        throw new Exception('Data da planilha não encontrada na posição ' . $posicao_data . '. Valor lido: "' . trim((string)$valor_data) . '".');
    }
    if ($data_mysql !== $hoje) {
        throw new Exception('Data da planilha (' . $data_mysql . ') difere da data de hoje (' . $hoje . '). Importação cancelada.');
    }

    foreach ($dep_raw_unicas as $dep_desc) {
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
    $map_comum_ids = [];
    foreach ($localidades_unicas as $codLoc) {
        try {
            $stmtBuscaComum = $conexao->prepare('SELECT id FROM comums WHERE codigo = :codigo');
            $stmtBuscaComum->bindValue(':codigo', $codLoc, PDO::PARAM_INT);
            $stmtBuscaComum->execute();
            $comumEncontrado = $stmtBuscaComum->fetch(PDO::FETCH_ASSOC);

            if ($comumEncontrado) {
                $map_comum_ids[$codLoc] = (int)$comumEncontrado['id'];
                if ($comum_processado_id === null) {
                    $comum_processado_id = (int)$comumEncontrado['id'];
                }
            } else {
                $novoId = garantir_comum_por_codigo($conexao, $codLoc);
                $map_comum_ids[$codLoc] = (int)$novoId;
                if ($comum_processado_id === null) {
                    $comum_processado_id = (int)$novoId;
                }
            }
        } catch (Throwable $e) {
            // ignora, tentativa seguinte
        }
    }

    if (empty($comum_processado_id)) {
        throw new Exception('Nenhum comum válido encontrado ou criado a partir da coluna de localidade.');
    }

    $job['delimiter'] = $delimiter;
    $job['data_mysql'] = $data_mysql;
    $job['dep_map'] = $dep_map;
    $job['map_comum_ids'] = $map_comum_ids;
    $job['comum_processado_id'] = $comum_processado_id;
    $job['registros_candidatos'] = $registros_candidatos;
    $job['total_linhas'] = $total_linhas;
    $job['cursor'] = $pulo_linhas; // próxima linha útil a processar (0-based)
    $job['status'] = 'ready';

    return $job;
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

    ip_normalizar_csv_encoding($job['file_path']);

    $isCsv = ip_is_csv($job['file_path']);
    $data_mismatch = false;
    $linhas = [];
    $registros_candidatos = 0;
    $dependencias_unicas = [];
    $localidades_unicas = [];
    $codigos_unicos_norm = [];
    $idx_codigo = pp_colunaParaIndice($mapeamento_codigo);
    $idx_complemento = pp_colunaParaIndice($mapeamento_complemento);
    $idx_dependencia = pp_colunaParaIndice($mapeamento_dependencia);
    $idx_localidade = pp_colunaParaIndice($coluna_localidade);
    $dep_map = [];
    $comum_processado_id = null;
    $map_comum_ids = [];
    $produtos_existentes = [];
    $produtos_existentes_por_codigo = [];

    if ($isCsv) {
        $job = ip_prescan_csv($job, $conexao, $pp_config);
        $data_mysql = $job['data_mysql'];
        $dep_map = $job['dep_map'];
        $map_comum_ids = $job['map_comum_ids'];
        $comum_processado_id = $job['comum_processado_id'];
        $registros_candidatos = $job['registros_candidatos'];
        $linhas = [];
    } else {
        $posicao_data_ref = strtoupper(trim($posicao_data));
        if (!preg_match('/^([A-Z]+)(\d+)$/', $posicao_data_ref, $matches)) {
            $matches = ['','D','13'];
        }
        $data_column = $matches[1] ?: 'D';
        $data_row = max(1, (int)$matches[2]);

        $columns_para_ler = array_unique(array_filter([
            $mapeamento_codigo,
            $mapeamento_complemento,
            $mapeamento_dependencia,
            $coluna_localidade,
            $data_column,
        ]));

        $reader = IOFactory::createReaderForFile($job['file_path']);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ImportacaoReadFilter($columns_para_ler, $data_column, $data_row));
        $planilha = $reader->load($job['file_path']);
        $aba = $planilha->getActiveSheet();

        $valor_data_cell = $aba->getCell($posicao_data);
        $valor_data = '';
        if ($valor_data_cell) {
            $valor_data = $valor_data_cell->getFormattedValue();
            if ($valor_data === '') {
                $valor_data = $valor_data_cell->getCalculatedValue();
            }
        }

        $data_mysql = ip_parse_planilha_data($valor_data);

        if ($data_mysql === null || $data_mysql === '') {
            $valor_debug = trim((string)$valor_data);
            throw new Exception('Data da planilha não encontrada na célula ' . $posicao_data . '. Valor lido: "' . $valor_debug . '". Importação cancelada.');
        }

        $hoje = date('Y-m-d');
        $data_mismatch = ($data_mysql !== $hoje);

        $linhas = $aba->toArray();
        $total_linhas = count($linhas);
        $linha_atual = 0;
        $registros_candidatos = 0;
        $dependencias_unicas = [];
        $localidades_unicas = [];
        $codigos_unicos_norm = [];

        foreach ($linhas as $linha) {
            $linha_atual++;
            if ($linha_atual <= $pulo_linhas) { continue; }
            if (empty(array_filter($linha))) { continue; }
            $codigo_tmp = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
            if ($codigo_tmp !== '') {
                $registros_candidatos++;
                $cod_norm = pp_normaliza($codigo_tmp);
                if ($cod_norm !== '') {
                    $codigos_unicos_norm[$cod_norm] = true;
                }
            }

            if (isset($linha[$idx_dependencia])) {
                $dep_raw = ip_fix_mojibake(ip_corrige_encoding($linha[$idx_dependencia]));
                $dep_norm = pp_normaliza($dep_raw);
                if ($dep_norm !== '' && !array_key_exists($dep_norm, $dependencias_unicas)) {
                    $dependencias_unicas[$dep_norm] = $dep_raw;
                }
            }

            if (isset($linha[$idx_localidade])) {
                $codigo_localidade = ip_obter_codigo_comum($linha[$idx_localidade]);
                if ($codigo_localidade > 0 && !in_array($codigo_localidade, $localidades_unicas, true)) {
                    $localidades_unicas[] = $codigo_localidade;
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
        $map_comum_ids = [];

        foreach ($localidades_unicas as $codLoc) {
            try {
                $stmtBuscaComum = $conexao->prepare('SELECT id FROM comums WHERE codigo = :codigo');
                $stmtBuscaComum->bindValue(':codigo', $codLoc, PDO::PARAM_INT);
                $stmtBuscaComum->execute();
                $comumEncontrado = $stmtBuscaComum->fetch(PDO::FETCH_ASSOC);

                if ($comumEncontrado) {
                    $map_comum_ids[$codLoc] = (int)$comumEncontrado['id'];
                    if ($comum_processado_id === null) {
                        $comum_processado_id = (int)$comumEncontrado['id'];
                    }
                    $comuns_existentes++;
                } else {
                    $novoId = garantir_comum_por_codigo($conexao, $codLoc);
                    $map_comum_ids[$codLoc] = (int)$novoId;
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

    }

    $mapeamento_colunas_str = 'codigo=' . $mapeamento_codigo . ';complemento=' . $mapeamento_complemento . ';dependencia=' . $mapeamento_dependencia . ';localidade=' . $coluna_localidade;

    // Garantir que apenas a última configuração seja mantida
    $conexao->exec('DELETE FROM configuracoes');
    $stmtCfg = $conexao->prepare('INSERT INTO configuracoes (mapeamento_colunas, posicao_data, pulo_linhas, data_importacao) VALUES (:mapeamento_colunas, :posicao_data, :pulo_linhas, :data_importacao)');
    $stmtCfg->bindValue(':mapeamento_colunas', $mapeamento_colunas_str);
    $stmtCfg->bindValue(':posicao_data', $posicao_data);
    $stmtCfg->bindValue(':pulo_linhas', $pulo_linhas);
    $stmtCfg->bindValue(':data_importacao', $data_mysql);
    $stmtCfg->execute();

    if ($data_mismatch) {
        throw new Exception('Data da planilha (' . $data_mysql . ') difere da data de hoje (' . $hoje . '). Importação cancelada.');
    }

    $tipos_bens = [];
    $stmtTipos = $conexao->prepare('SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC');
    if ($stmtTipos->execute()) {
        $tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    }
    $tipos_aliases_calculados = pp_construir_aliases_tipos($tipos_bens);

    $produtos_existentes = [];
    $produtos_existentes_por_codigo = [];
    $codigos_busca = array_keys($codigos_unicos_norm);
    if (!empty($codigos_busca)) {
        $placeholders = implode(',', array_fill(0, count($codigos_busca), '?'));
        $stmtProdExist = $conexao->prepare('SELECT * FROM produtos WHERE codigo IN (' . $placeholders . ')');
        foreach ($codigos_busca as $i => $cod) {
            $stmtProdExist->bindValue($i + 1, $cod);
        }
        $stmtProdExist->execute();
        while ($p = $stmtProdExist->fetch(PDO::FETCH_ASSOC)) {
            $codigo_norm = pp_normaliza((string)$p['codigo']);
            $key = ($p['comum_id'] ?? 0) . '|' . $codigo_norm;
            $produtos_existentes[$key] = $p;
            $produtos_existentes_por_codigo[$codigo_norm][] = [
                'key' => $key,
                'produto' => $p,
            ];
        }
    }

    $stmtMaxId = $conexao->query('SELECT COALESCE(MAX(id_produto), 0) AS max_id FROM produtos');
    $id_produto_sequencial = (int)($stmtMaxId->fetchColumn() ?? 0) + 1;

    $job['linhas'] = $linhas;
    $job['cursor'] = $job['cursor'] ?? 0;
    $job['pulo_linhas'] = $pulo_linhas;
    $job['idx_codigo'] = $idx_codigo;
    $job['idx_complemento'] = $idx_complemento;
    $job['idx_dependencia'] = $idx_dependencia;
    $job['idx_localidade'] = $idx_localidade;
    $job['registros_candidatos'] = $job['registros_candidatos'] ?? $registros_candidatos;
    $job['comum_processado_id'] = $job['comum_processado_id'] ?? $comum_processado_id;
    $job['dep_map'] = $job['dep_map'] ?? $dep_map;
    $job['tipos_bens'] = $tipos_bens;
    $job['tipos_aliases'] = $tipos_aliases_calculados;
    $job['produtos_existentes'] = $produtos_existentes;
    $job['produtos_existentes_por_codigo'] = $produtos_existentes_por_codigo;
    $job['id_produto_sequencial'] = $id_produto_sequencial;
    $job['codigos_processados'] = $job['codigos_processados'] ?? [];
    $job['stats'] = $job['stats'] ?? [
        'novos' => 0,
        'atualizados' => 0,
        'excluidos' => 0,
        'processados' => 0,
    ];
    $job['erros_produtos'] = $job['erros_produtos'] ?? [];
    $job['status'] = 'ready';
    $job['data_mysql'] = $data_mysql;
    $job['map_comum_ids'] = $job['map_comum_ids'] ?? $map_comum_ids;
    $job['is_csv'] = $isCsv;
    $job['total_linhas'] = $job['total_linhas'] ?? ($total_linhas ?? 0);

    ip_append_log($job, 'info', 'Leitura inicial concluída. Total estimado de linhas úteis: ' . $job['registros_candidatos'] . '.');

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

    $lock = ip_acquire_import_lock(true);
    if (!$lock) {
        $percent = 0;
        if (!empty($job['registros_candidatos'])) {
            $percent = min(100, round((($job['stats']['processados'] ?? 0) / $job['registros_candidatos']) * 100, 2));
        }
        ip_response_json([
            'done' => false,
            'busy' => true,
            'progress' => $percent,
            'stats' => $job['stats'] ?? [],
            'total' => $job['registros_candidatos'] ?? 0,
            'processed' => $job['stats']['processados'] ?? 0,
            'errors' => $job['erros_produtos'] ?? [],
            'log' => $job['log'] ?? [],
        ]);
    }

    try {
        if (($job['status'] ?? 'pending') === 'pending') {
            try {
                $job = ip_prepare_job($job, $conexao, $pp_config);
            } catch (Throwable $prepEx) {
                ip_cleanup_job_resources($job);
                ip_release_import_lock($lock);
                ip_response_json(['message' => 'Falha ao preparar importação: ' . $prepEx->getMessage()], 500);
                return;
            }
        }

        $job['status'] = 'processing';
        ip_save_job($job);

        @set_time_limit(0);

        $isCsv = !empty($job['is_csv']);
        $inicio = (int)($job['cursor'] ?? 0);
        $batchItems = [];
        $jobDone = false;
        $fim = $inicio;

        if ($isCsv) {
            $delimiter = $job['delimiter'] ?? ',';
            [$batchItems, $nextCursor, $eof] = ip_read_csv_batch($job['file_path'], $delimiter, $inicio, IP_BATCH_SIZE);
            $fim = $nextCursor;
            $jobDone = $eof;
        } else {
            $linhas = $job['linhas'] ?? [];
            $totalLinhas = count($linhas);
            $fim = min($totalLinhas, $inicio + IP_BATCH_SIZE);
            for ($i = $inicio; $i < $fim; $i++) {
                $batchItems[] = [
                    'line' => $i + 1,
                    'row' => $linhas[$i],
                ];
            }
            $jobDone = ($fim >= $totalLinhas);
        }

        $pulo_linhas = (int)($job['pulo_linhas']);
        $idx_codigo = (int)($job['idx_codigo'] ?? 0);
        $idx_complemento = (int)($job['idx_complemento'] ?? 0);
        $idx_dependencia = (int)($job['idx_dependencia'] ?? 0);
        $idx_localidade = (int)($job['idx_localidade'] ?? 0);
        $tipos_aliases = $job['tipos_aliases'] ?? [];
        $codigos_processados = $job['codigos_processados'] ?? [];
        $produtos_existentes = $job['produtos_existentes'] ?? [];
        $produtos_existentes_por_codigo = $job['produtos_existentes_por_codigo'] ?? [];
        $dep_map = $job['dep_map'] ?? [];
        $id_produto_sequencial = (int)$job['id_produto_sequencial'];
        $stats = $job['stats'];
        $erros_produtos = $job['erros_produtos'] ?? [];
        $comum_processado_id = (int)$job['comum_processado_id'];
        $map_comum_ids = $job['map_comum_ids'] ?? [];
        $processedByComum = [];

        $transactionStarted = false;
        $transactionStarted = $conexao->beginTransaction();
        if (!$transactionStarted) {
            throw new Exception('Não foi possível iniciar a transação de importação.');
        }
        ip_append_log($job, 'info', 'Processando lote a partir da linha ' . ($inicio + 1) . '. Items: ' . count($batchItems) . '.');

        foreach ($batchItems as $item) {
            $linhaNumero = $item['line'];
            $linha = $item['row'];

            if ($linhaNumero <= $pulo_linhas) {
                continue;
            }
            if (empty(array_filter((array)$linha))) {
                continue;
            }

            try {
                $codigo = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
                if ($codigo === '') {
                    continue;
                }

                $codigo_norm = pp_normaliza($codigo);

                $localidade_raw = isset($linha[$idx_localidade]) ? (string)$linha[$idx_localidade] : '';
                $codigo_localidade = ip_obter_codigo_comum($localidade_raw);
                $comum_destino_id = $comum_processado_id;
                if ($codigo_localidade > 0) {
                    if (!isset($map_comum_ids[$codigo_localidade])) {
                        try {
                            $stmtBuscaComum = $conexao->prepare('SELECT id FROM comums WHERE codigo = :codigo');
                            $stmtBuscaComum->bindValue(':codigo', $codigo_localidade, PDO::PARAM_INT);
                            $stmtBuscaComum->execute();
                            $comumEncontrado = $stmtBuscaComum->fetch(PDO::FETCH_ASSOC);
                            if ($comumEncontrado) {
                                $map_comum_ids[$codigo_localidade] = (int)$comumEncontrado['id'];
                            } else {
                                $map_comum_ids[$codigo_localidade] = (int)garantir_comum_por_codigo($conexao, $codigo_localidade);
                            }
                        } catch (Throwable $e) {
                            error_log('Falha ao resolver comum ' . $codigo_localidade . ': ' . $e->getMessage());
                        }
                    }
                    if (isset($map_comum_ids[$codigo_localidade])) {
                        $comum_destino_id = (int)$map_comum_ids[$codigo_localidade];
                    }
                }

                $codigo_key = $comum_destino_id . '|' . $codigo_norm;
                $codigo_key_original = $codigo_key;
                $prodExist = null;
                if (isset($produtos_existentes[$codigo_key])) {
                    $prodExist = $produtos_existentes[$codigo_key];
                } elseif (isset($produtos_existentes_por_codigo[$codigo_norm]) && !empty($produtos_existentes_por_codigo[$codigo_norm])) {
                    $fallbackProd = $produtos_existentes_por_codigo[$codigo_norm][0];
                    $prodExist = $fallbackProd['produto'];
                    $codigo_key_original = $fallbackProd['key'];
                }

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
                if ($codigo_key_original !== $codigo_key) {
                    $codigos_processados[$codigo_key_original] = true; // evita exclusão de produtos movidos entre comuns
                }

                if ($prodExist) {
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
                    $stmtUp->bindValue(':comum_id', $comum_destino_id, PDO::PARAM_INT);
                    $stmtUp->bindValue(':id_produto', $prodExist['id_produto'], PDO::PARAM_INT);
                    if ($stmtUp->execute()) {
                        $stats['atualizados']++;
                        $processedByComum[$comum_destino_id][] = (int)$prodExist['id_produto'];
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
                    $stmt_prod->bindValue(':comum_id', $comum_destino_id, PDO::PARAM_INT);
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
                        $processedByComum[$comum_destino_id][] = (int)$id_produto_sequencial;
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

        foreach ($processedByComum as $cid => $ids) {
            ip_track_processed_ids($conexao, $jobId, array_values(array_unique($ids)), (int)$cid);
        }

        if ($jobDone) {
            $deleted = ip_delete_unprocessed($conexao, $jobId);
            $stats['excluidos'] += $deleted;
            ip_append_log($job, 'info', 'Exclusão final concluída: ' . $deleted . ' produto(s) removido(s) por não estarem na planilha.');
            ip_cleanup_processed_ids($conexao, $jobId);
        }

        if ($transactionStarted && $conexao->inTransaction()) {
            $conexao->commit();
        }

        $job['cursor'] = $fim;
        $job['codigos_processados'] = $codigos_processados;
        $job['dep_map'] = $dep_map;
        $job['id_produto_sequencial'] = $id_produto_sequencial;
        $job['stats'] = $stats;
        $job['erros_produtos'] = $erros_produtos;
        $job['produtos_existentes'] = $produtos_existentes;
        $job['map_comum_ids'] = $map_comum_ids;

        $rangeStart = $inicio + 1;
        $rangeEnd = $isCsv ? ($inicio + count($batchItems)) : $fim;
        $resumo_lote = 'Lote concluído (' . $rangeStart . '-' . max($rangeStart, $rangeEnd) . '): ' . $stats['processados'] . ' processados, ' . $stats['novos'] . ' novos, ' . $stats['atualizados'] . ' atualizados, ' . $stats['excluidos'] . ' excluídos.';
        ip_append_log($job, 'info', $resumo_lote);

        if ($jobDone) {
            $mensagem = 'Importação concluída! Novos: ' . $stats['novos'] . ', Atualizados: ' . $stats['atualizados'] . ', Excluídos: ' . $stats['excluidos'] . '.';
            if (!empty($erros_produtos)) {
                $mensagem .= ' Erros em ' . count($erros_produtos) . ' linha(s).';
            }

            ip_append_log($job, 'info', 'Processamento finalizado. ' . $mensagem);

            $_SESSION['mensagem'] = $mensagem;
            $_SESSION['tipo_mensagem'] = empty($erros_produtos) ? 'success' : 'warning';

            ip_cleanup_job_resources($job);
            ip_response_and_release($lock, [
                'done' => true,
                'success' => empty($erros_produtos),
                'message' => $mensagem,
                'errors' => $erros_produtos,
                'redirect' => base_url('index.php'),
                'log' => $job['log'] ?? [],
            ]);
            return;
        }

        ip_save_job($job);

        $percent = 0;
        if ($job['registros_candidatos'] > 0) {
            $percent = min(100, round(($job['stats']['processados'] / $job['registros_candidatos']) * 100, 2));
        }

        ip_response_and_release($lock, [
            'done' => false,
            'progress' => $percent,
            'stats' => $job['stats'],
            'total' => $job['registros_candidatos'],
            'processed' => $job['stats']['processados'],
            'errors' => $erros_produtos,
            'log' => $job['log'] ?? [],
        ]);
        return;
    } catch (Throwable $e) {
        if ($transactionStarted && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        ip_release_import_lock($lock);
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

    $lock = null;
    $transactionStarted = false;
    try {
        $lock = ip_acquire_import_lock(true);
        if (!$lock) {
            throw new Exception('Já existe uma importação em andamento. Aguarde finalizar para iniciar outra.');
        }

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
        ip_normalizar_csv_encoding($destino);
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
            'log' => [],
        ];

        ip_append_log($job, 'info', 'Upload recebido. Arquivo armazenado e aguardando preparação.');

        ip_save_job($job);

        ip_release_import_lock($lock);

        header('Location: ' . base_url('app/views/planilhas/importacao_progresso.php?job=' . urlencode($jobId)));
        exit;
    } catch (Exception $e) {
        if (isset($lock)) {
            ip_release_import_lock($lock);
        }
        $_SESSION['mensagem'] = 'Erro: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: ' . base_url('app/views/planilhas/planilha_importar.php'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $importUrl = base_url('app/views/planilhas/planilha_importar.php');
    header('Location: ' . $importUrl);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=' . $importUrl . '"></head><body>';
    echo '<p>Redirecionando para o formulário de importação... Se não for redirecionado automaticamente, <a href="' . $importUrl . '">clique aqui</a>.</p>';
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

    ip_append_log($job, 'warning', 'Importação cancelada pelo usuário.');
    ip_cleanup_job_resources($job);
    $_SESSION['mensagem'] = 'Importação cancelada pelo usuário.';
    $_SESSION['tipo_mensagem'] = 'warning';

    ip_response_json([
        'canceled' => true,
        'redirect' => base_url('app/views/planilhas/planilha_importar.php'),
        'log' => $job['log'] ?? [],
    ]);
}

ip_response_json(['message' => 'Ação inválida ou método não suportado.'], 400);

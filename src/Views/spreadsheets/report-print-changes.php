<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';



$id_planilha = $_GET['id'] ?? ($id_planilha ?? null);
if (!$id_planilha) {
  header('Location: ' . base_url('/'));
  exit;
}


try {
  $sql_planilha = "SELECT id, descricao as comum, cnpj, administracao, cidade FROM comums WHERE id = :id";
  $stmt_planilha = $conexao->prepare($sql_planilha);
  $stmt_planilha->bindValue(':id', $id_planilha);
  $stmt_planilha->execute();
  $planilha = $stmt_planilha->fetch();
  if (!$planilha) {
    throw new Exception('Planilha não encontrada.');
  }
} catch (PDOException $e) {

  if ($e->getCode() === '42S02' || stripos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false) {
    try {
      $stmt = $conexao->prepare('SELECT id, descricao as comum FROM comums WHERE id = :id');
      $stmt->bindValue(':id', $id_planilha, PDO::PARAM_INT);
      $stmt->execute();
      $comum = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($comum) {

        $planilha = ['id' => (int)$comum['id'], 'comum' => $comum['comum'], 'comum_id' => (int)$comum['id'], 'ativo' => 1];
        $using_comum_fallback = true;
      } else {
        throw new Exception('Comum não encontrada.');
      }
    } catch (Exception $ex) {
      die("Erro ao carregar planilha/comum: " . $ex->getMessage());
    }
  } else {
    die("Erro ao carregar planilha: " . $e->getMessage());
  }
} catch (Exception $e) {
  die("Erro ao carregar planilha: " . $e->getMessage());
}


$mostrar_pendentes = isset($_GET['mostrar_pendentes']);
$mostrar_checados = isset($_GET['mostrar_checados']);
$mostrar_observacao = isset($_GET['mostrar_observacao']);
$mostrar_checados_observacao = isset($_GET['mostrar_checados_observacao']);
$mostrar_etiqueta = isset($_GET['mostrar_etiqueta']);
$mostrar_alteracoes = isset($_GET['mostrar_alteracoes']);
$mostrar_novos = isset($_GET['mostrar_novos']);
$filtro_dependencia = isset($_GET['dependencia']) && $_GET['dependencia'] !== '' ? (int)$_GET['dependencia'] : ''; 

try {

  $sql_PRODUTOS = "SELECT p.*, 
                     CAST(p.checado AS SIGNED) as checado, 
                     CAST(p.ativo AS SIGNED) as ativo, 
                     CAST(p.imprimir_etiqueta AS SIGNED) as imprimir, 
                     p.observacao as observacoes, 
                     CAST(p.editado AS SIGNED) as editado,
                     tb.codigo AS tipo_codigo,
                     tb.descricao AS tipo_desc,
                     etb.codigo AS editado_tipo_codigo,
                     etb.descricao AS editado_tipo_desc,
                    NULLIF(CONCAT_WS(' ', p.editado_bem, p.editado_complemento), '') as nome_editado,
                    p.editado_dependencia_id as dependencia_editada,
                    d_orig.descricao AS dependencia_desc,
                    d_edit.descricao AS editado_dependencia_desc,
                    COALESCE(d_edit.descricao, d_orig.descricao, '') as dependencia,
                    NULLIF(CONCAT_WS(' ', p.bem, p.complemento), '') as descricao_completa,
                    'comum' as origem
                     FROM produtos p
                     LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                     LEFT JOIN tipos_bens etb ON p.editado_tipo_bem_id = etb.id
                     LEFT JOIN dependencias d_orig ON p.dependencia_id = d_orig.id
                     LEFT JOIN dependencias d_edit ON p.editado_dependencia_id = d_edit.id
                     WHERE p.comum_id = :id_comum";
  $params = [':id_comum' => $id_planilha];
  if (!empty($filtro_dependencia)) {
    $sql_PRODUTOS .= " AND (
            (CAST(p.editado AS SIGNED) = 1 AND p.editado_dependencia_id = :dependencia) OR
            (CAST(p.editado AS SIGNED) IS NULL OR CAST(p.editado AS SIGNED) = 0) AND p.dependencia_id = :dependencia
        )";
    $params[':dependencia'] = $filtro_dependencia;
  }
  $sql_PRODUTOS .= " ORDER BY p.codigo";
  $stmt_PRODUTOS = $conexao->prepare($sql_PRODUTOS);
  foreach ($params as $k => $v) {
    $stmt_PRODUTOS->bindValue($k, $v);
  }
  $stmt_PRODUTOS->execute();
  $todos_PRODUTOS = $stmt_PRODUTOS->fetchAll();
} catch (Exception $e) {
  die("Erro ao carregar PRODUTOS: " . $e->getMessage());
}

try {

  $sql_dependencias = "
        SELECT DISTINCT p.dependencia_id as dependencia FROM produtos p WHERE p.comum_id = :id_comum1
        UNION
        SELECT DISTINCT p.editado_dependencia_id as dependencia FROM produtos p
        WHERE p.comum_id = :id_comum2 AND p.editado = 1 AND p.editado_dependencia_id IS NOT NULL
        ORDER BY dependencia
    ";
  $stmt_dependencias = $conexao->prepare($sql_dependencias);
  $stmt_dependencias->bindValue(':id_comum1', $id_planilha);
  $stmt_dependencias->bindValue(':id_comum2', $id_planilha);
  $stmt_dependencias->execute();
  $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
  $dependencia_options = [];
}


$dependencias_map = [];
if (!empty($dependencia_options)) {
  $placeholders = implode(',', array_fill(0, count($dependencia_options), '?'));
  $stmtDepMap = $conexao->prepare("SELECT id, descricao FROM dependencias WHERE id IN ($placeholders)");
  foreach ($dependencia_options as $idx => $depId) {
    $stmtDepMap->bindValue($idx + 1, (int)$depId, PDO::PARAM_INT);
  }
  if ($stmtDepMap->execute()) {
    foreach ($stmtDepMap->fetchAll(PDO::FETCH_ASSOC) as $d) {
      $dependencias_map[(int)$d['id']] = [
        'descricao' => $d['descricao']
      ];
    }
  }
}

$PRODUTOS_pendentes = $PRODUTOS_checados = $PRODUTOS_observacao = $PRODUTOS_checados_observacao = $PRODUTOS_etiqueta = $PRODUTOS_alteracoes = $PRODUTOS_novos = []; 

// Helper: build display title like in `spreadsheets/view.php` (tipo + bem + complemento + {dependencia})
function _build_produto_titulo(array $p, bool $useEdited = false): string {
  $tipoCodigo = trim((string)($useEdited ? ($p['editado_tipo_codigo'] ?? '') : ($p['tipo_codigo'] ?? '')));
  $tipoDesc = trim((string)($useEdited ? ($p['editado_tipo_desc'] ?? '') : ($p['tipo_desc'] ?? '')));
  $tipoPart = '';
  if ($tipoCodigo !== '' || $tipoDesc !== '') {
    $tipoPart = '{' . mb_strtoupper(trim(($tipoCodigo ? $tipoCodigo . ' - ' : '') . $tipoDesc), 'UTF-8') . '}';
  }

  $bem = trim((string)($useEdited ? ($p['editado_bem'] ?? '') : ($p['bem'] ?? '')));
  $comp = trim((string)($useEdited ? ($p['editado_complemento'] ?? '') : ($p['complemento'] ?? '')));

  // remove redundant prefix in complemento (if it starts with bem)
  $descricao = $bem;
  if ($comp !== '') {
    $compTmp = $comp;
    if ($bem !== '' && mb_strtoupper(mb_substr($compTmp, 0, mb_strlen($bem), 'UTF-8'), 'UTF-8') === mb_strtoupper($bem, 'UTF-8')) {
      $compTmp = trim(mb_substr($compTmp, mb_strlen($bem), null, 'UTF-8'));
      $compTmp = preg_replace('/^[\s\-\/]+/u', '', $compTmp);
    }
    if ($compTmp !== '') $descricao .= ($descricao !== '' ? ' ' : '') . $compTmp;
  }

  $depImport = trim((string)($useEdited ? ($p['editado_dependencia_desc'] ?? $p['dependencia_desc'] ?? '') : ($p['dependencia_desc'] ?? '')));
  $depPart = $depImport !== '' ? ' {' . mb_strtoupper($depImport, 'UTF-8') . '}' : '';

  $titulo = trim(($tipoPart ? $tipoPart . ' ' : '') . $descricao . ($depPart ? ' ' . $depPart : ''));
  return $titulo === '' ? 'Sem descricao' : $titulo;
}

foreach ($todos_PRODUTOS as $PRODUTO) {

  // construir nomes como na view principal (tipo + bem + complemento + {dependencia})
  $PRODUTO['nome_original'] = _build_produto_titulo($PRODUTO, false);
  if ((int)($PRODUTO['editado'] ?? 0) === 1 || !empty(trim((string)($PRODUTO['nome_editado'] ?? '')))) {
    $PRODUTO['nome_atual'] = _build_produto_titulo($PRODUTO, true);
  } else {
    $PRODUTO['nome_atual'] = $PRODUTO['nome_original'];
  }


  if (($PRODUTO['origem'] ?? '') === 'cadastro') {
    $PRODUTOS_novos[] = $PRODUTO;
    if (!empty($PRODUTO['codigo'])) {
      $PRODUTOS_etiqueta[] = $PRODUTO;
    }
    continue;
  }


  $tem_observacao = !empty($PRODUTO['observacoes']);
  $esta_checado = ($PRODUTO['checado'] ?? 0) == 1;
  $esta_no_dr = ($PRODUTO['ativo'] ?? 1) == 0;
  $esta_etiqueta = ($PRODUTO['imprimir'] ?? 0) == 1;
  $tem_alteracoes = (int)($PRODUTO['editado'] ?? 0) === 1;
  $eh_pendente = is_null($PRODUTO['checado']) && ($PRODUTO['ativo'] ?? 1) == 1 && is_null($PRODUTO['imprimir']) && is_null($PRODUTO['observacoes']) && is_null($PRODUTO['editado']);

  if ($tem_alteracoes) {

    $PRODUTOS_alteracoes[] = $PRODUTO;
    $PRODUTOS_etiqueta[] = $PRODUTO;
  }

  if ($esta_etiqueta) {
    $PRODUTOS_etiqueta[] = $PRODUTO;
  } elseif ($tem_observacao && $esta_checado) {
    $PRODUTOS_checados_observacao[] = $PRODUTO;
  } elseif ($tem_observacao) {
    $PRODUTOS_observacao[] = $PRODUTO;
  } elseif ($esta_checado) {
    $PRODUTOS_checados[] = $PRODUTO;
  } elseif ($eh_pendente) {
    $PRODUTOS_pendentes[] = $PRODUTO;
  } else {
    $PRODUTOS_pendentes[] = $PRODUTO;
  }
}
$total_pendentes = count($PRODUTOS_pendentes);
$total_checados = count($PRODUTOS_checados);
$total_observacao = count($PRODUTOS_observacao);
$total_checados_observacao = count($PRODUTOS_checados_observacao);

$total_etiqueta = count($PRODUTOS_etiqueta);
$total_alteracoes = count($PRODUTOS_alteracoes);
$total_novos = count($PRODUTOS_novos);
$total_geral = count($todos_PRODUTOS); 


if (isset($_GET['debug'])) {
  echo "<pre>DEBUG - PRODUTOS com editado:<br>";
  foreach ($todos_PRODUTOS as $p) {
    if (($p['origem'] ?? '') !== 'cadastro') {
      $editado_valor = $p['editado'] ?? 'NULL';
      $editado_tipo = gettype($p['editado'] ?? null);
      $tem_nome_editado = !empty($p['nome_editado']) ? 'SIM' : 'NÃO';
      $tem_dep_editada = !empty($p['dependencia_editada']) ? 'SIM' : 'NÃO';
      if ((int)($p['editado'] ?? 0) === 1 || !empty($p['nome_editado']) || !empty($p['dependencia_editada'])) {
        echo "ID: {$p['id']} | Código: {$p['codigo']} | editado={$editado_valor} (tipo: {$editado_tipo}) | nome_editado: {$tem_nome_editado} | dep_editada: {$tem_dep_editada}<br>";
      }
    }
  }
  echo "Total em \$PRODUTOS_alteracoes: " . count($PRODUTOS_alteracoes) . "<br>";
  echo "</pre>";
}

$total_mostrar = 0;
if ($mostrar_pendentes) $total_mostrar += $total_pendentes;
if ($mostrar_checados) $total_mostrar += $total_checados;
if ($mostrar_observacao) $total_mostrar += $total_observacao;
if ($mostrar_checados_observacao) $total_mostrar += $total_checados_observacao;
if ($mostrar_etiqueta) $total_mostrar += $total_etiqueta;
if ($mostrar_alteracoes) $total_mostrar += $total_alteracoes; 
if ($mostrar_novos) $total_mostrar += $total_novos;


$pageTitle = 'Imprimir Alterações';
$backUrl = '/products/view?id=' . $id_planilha . '&comum_id=' . $id_planilha;
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuAlteracao" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuAlteracao">
            <li>
                <button class="dropdown-item" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="/logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';


$customCssPath = '/assets/css/spreadsheets/report-print-changes.css';



ob_start();
?>

<!-- Filtros -->
<div class="card mb-3 no-print">
  <div class="card-header">
    <i class="bi bi-filter-circle me-2"></i> Filtros do relatório
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
      <div class="col-12">
        <label class="form-label">Produtos:</label>
        <div class="row g-2">
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secPend" name="mostrar_pendentes" value="1" <?php echo $mostrar_pendentes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secPend">Pendentes (<?php echo $total_pendentes; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChec" name="mostrar_checados" value="1" <?php echo $mostrar_checados ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChec">Checados (<?php echo $total_checados; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secObs" name="mostrar_observacao" value="1" <?php echo $mostrar_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secObs">Com observação (<?php echo $total_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChecObs" name="mostrar_checados_observacao" value="1" <?php echo $mostrar_checados_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChecObs">Checados com observação (<?php echo $total_checados_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secEtiq" name="mostrar_etiqueta" value="1" <?php echo $mostrar_etiqueta ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secEtiq">Para impresao de etiquetas (<?php echo $total_etiqueta; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secAlt" name="mostrar_alteracoes" value="1" <?php echo $mostrar_alteracoes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secAlt">Editados (<?php echo $total_alteracoes; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secNovos" name="mostrar_novos" value="1" <?php echo $mostrar_novos ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secNovos">Novos (<?php echo $total_novos; ?>)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 d-grid">
        <button type="submit" class="btn btn-success"><i class="bi bi-funnel me-2"></i>Aplicar filtros</button>
      </div>
    </form>
  </div>
</div>

<!-- Cabeçalho do relatório -->
<div class="card mb-3">
  <div class="card-body text-center">
    <h5 class="mb-1 text-gradient">RELATÓRIO DE ALTERAÇÕES</h5>
    <div class="text-muted"><?php echo htmlspecialchars($planilha['comum']); ?></div>
    <div class="small text-muted">Gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
  </div>
    <!-- STATUS removed per UI request -->
  </div>

<!-- Resumo -->
<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-graph-up-arrow me-2"></i> Resumo geral
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Total de produtos:</strong> <?php echo $total_geral; ?></li>
      <li><strong>Pendentes:</strong> <?php echo $total_pendentes; ?></li>
      <li><strong>Checados:</strong> <?php echo $total_checados; ?></li>
      <li><strong>Com observação:</strong> <?php echo $total_observacao; ?></li>
      <li><strong>Checados com observação:</strong> <?php echo $total_checados_observacao; ?></li>
      <li><strong>Para impresao de etiquetas:</strong> <?php echo $total_etiqueta; ?></li>
      <li><strong>Editados:</strong> <?php echo $total_alteracoes; ?></li>
      <li><strong>Novos:</strong> <?php echo $total_novos; ?></li>
      <li><strong>Total a ser impresso:</strong> <?php echo $total_mostrar; ?></li>
    </ul>
  </div>
</div>

<?php if ($total_geral > 0 && $total_mostrar > 0): ?>
  <?php if ($mostrar_alteracoes && $total_alteracoes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Editados (<?php echo $total_alteracoes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Antigo</th>
                <th>Novo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_alteracoes as $PRODUTO): ?>
                <?php

                $antigo = [];
                $novo = [];


                $nome_original = $PRODUTO['nome_original'] ?? ($PRODUTO['nome'] ?? '');
                $nome_atual = $PRODUTO['nome_atual'] ?? $nome_original;
                if (!empty($PRODUTO['nome_editado']) && $PRODUTO['nome_editado'] != $nome_original) {
                  $antigo[] = htmlspecialchars($nome_original);
                  $novo[] = htmlspecialchars($nome_atual);
                } else {

                  $antigo[] = htmlspecialchars($nome_atual);
                  $novo[] = htmlspecialchars($nome_atual);
                }

                $texto_antigo = implode('<br>', $antigo);
                $texto_novo = implode('<br>', $novo);
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo $texto_antigo; ?></td>
                  <td><?php echo $texto_novo; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_pendentes && $total_pendentes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Pendentes (<?php echo $total_pendentes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Dependência</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_pendentes as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td><?php echo htmlspecialchars($PRODUTO['dependencia'] ?? ''); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados && $total_checados > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Checados (<?php echo $total_checados; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_checados as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_observacao && $total_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Com observação (<?php echo $total_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Observações</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_observacao as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td class="fst-italic"><?php echo htmlspecialchars($PRODUTO['observacoes']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados_observacao && $total_checados_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Checados com observação (<?php echo $total_checados_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Observações</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_checados_observacao as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td><?php echo htmlspecialchars($PRODUTO['observacoes']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>



  <?php if ($mostrar_etiqueta && $total_etiqueta > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Para impresao de etiquetas (<?php echo $total_etiqueta; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Descrição</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_etiqueta as $PRODUTO): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_novos && $total_novos > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Novos (<?php echo $total_novos; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Descrição Completa</th>
                <th class="text-center">Quantidade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_novos as $PRODUTO): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></strong></td>
                  <td class="text-center"><?php echo htmlspecialchars($PRODUTO['quantidade'] ?? 'N/A'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php elseif ($total_geral > 0 && $total_mostrar === 0): ?>
  <div class="alert alert-warning">
    <i class="bi bi-info-circle me-2"></i> Marque pelo menos uma seção para visualizar o relatório.
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="bi bi-emoji-frown me-2"></i> Nenhum PRODUTO encontrado para os filtros aplicados.
  </div>
<?php endif; ?>

<div class="text-center text-muted small my-3">
  Relatório gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?>
</div>

<?php
$contentHtml = ob_get_clean();

include $projectRoot . '/src/Views/layouts/app.php';

?>
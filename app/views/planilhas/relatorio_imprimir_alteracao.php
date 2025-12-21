<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Autentica+Ã¯Â¿Â½+Ã¯Â¿Â½o
// Agora: p+Ã¯Â¿Â½gina integrada ao layout app-wrapper (Bootstrap 5, 400px)

$id_planilha = $_GET['id'] ?? null;
if (!$id_planilha) {
  header('Location: ../../index.php');
  exit;
}

// BUSCAR dados da planilha
try {
  $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
  $stmt_planilha = $conexao->prepare($sql_planilha);
  $stmt_planilha->bindValue(':id', $id_planilha);
  $stmt_planilha->execute();
  $planilha = $stmt_planilha->fetch();
  if (!$planilha) {
    throw new Exception('Planilha não encontrada.');
  }
} catch (PDOException $e) {
  // Table not found (SQLSTATE 42S02 / error 1146) - provide actionable message
  if ($e->getCode() === '42S02' || stripos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false) {
    die("Erro: tabela 'planilhas' não encontrada no banco de dados.\n\nSolução: importe o schema do banco de dados com o arquivo `anvycomb_checkplanilha.sql` na raiz do projeto (ex: mysql -u user -p anvycomb_checkplanilha < anvycomb_checkplanilha.sql) ou verifique as configurações em `config/database.php`.");
  }
  die("Erro ao carregar planilha: " . $e->getMessage());
} catch (Exception $e) {
  die("Erro ao carregar planilha: " . $e->getMessage());
}

$mostrar_pendentes = isset($_GET['mostrar_pendentes']);
$mostrar_checados = isset($_GET['mostrar_checados']);
$mostrar_observacao = isset($_GET['mostrar_observacao']);
$mostrar_checados_observacao = isset($_GET['mostrar_checados_observacao']);
$mostrar_dr = isset($_GET['mostrar_dr']);
$mostrar_etiqueta = isset($_GET['mostrar_etiqueta']);
$mostrar_alteracoes = isset($_GET['mostrar_alteracoes']);
$mostrar_novos = isset($_GET['mostrar_novos']);
$filtro_dependencia = isset($_GET['dependencia']) && $_GET['dependencia'] !== '' ? (int)$_GET['dependencia'] : '';

try {
  // BUSCAR PRODUTOS da planilha importada (tabela PRODUTOS)
  $sql_PRODUTOS = "SELECT p.*, 
                     CAST(p.checado AS SIGNED) as checado, 
                     CAST(p.ativo AS SIGNED) as ativo, 
                     CAST(p.imprimir_etiqueta AS SIGNED) as imprimir, 
                     p.observacao as observacoes, 
                     CAST(p.editado AS SIGNED) as editado, 
                     p.editado_descricao_completa as nome_editado, 
                     p.editado_dependencia_id as dependencia_editada,
                     'planilha' as origem
                     FROM PRODUTOS p 
                     WHERE p.planilha_id = :id_planilha";
  $params = [':id_planilha' => $id_planilha];
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

  // BUSCAR PRODUTOS novos cadastrados manualmente (tabela PRODUTOS_cadastro n+Ã¯Â¿Â½o existe no schema atual)
  // $sql_novos = "SELECT pc.id, pc.id_planilha, pc.descricao_completa as nome, '' as codigo, pc.complemento as dependencia,
  //               pc.quantidade, pc.tipo_ben, pc.imprimir_14_1 as imprimir_cadastro, 'cadastro' as origem,
  //               NULL as checado, 1 as ativo, NULL as imprimir, NULL as observacoes, NULL as editado, NULL as nome_editado, NULL as dependencia_editada
  //               FROM PRODUTOS_cadastro pc
  //               WHERE pc.id_planilha = :id_planilha";
  // $params_novos = [':id_planilha' => $id_planilha];
  // if (!empty($filtro_dependencia)) { $sql_novos .= " AND pc.complemento LIKE :dependencia"; $params_novos[':dependencia'] = '%' . $filtro_dependencia . '%'; }
  // $sql_novos .= " ORDER BY pc.id";
  // $stmt_novos = $conexao->prepare($sql_novos);
  // foreach ($params_novos as $k => $v) { $stmt_novos->bindValue($k, $v); }
  // $stmt_novos->execute();
  // $PRODUTOS_cadastrados = $stmt_novos->fetchAll();

  // Combinar ambos os arrays (removido pois tabela PRODUTOS_cadastro n+Ã¯Â¿Â½o existe)
  // $todos_PRODUTOS = array_merge($todos_PRODUTOS, $PRODUTOS_cadastrados);
} catch (Exception $e) {
  die("Erro ao carregar PRODUTOS: " . $e->getMessage());
}

try {
  // BUSCAR depend+Ã¯Â¿Â½ncias originais + depend+Ã¯Â¿Â½ncias editadas
  $sql_dependencias = "
        SELECT DISTINCT p.dependencia_id as dependencia FROM PRODUTOS p WHERE p.planilha_id = :id_planilha1
        UNION
        SELECT DISTINCT p.editado_dependencia_id as dependencia FROM PRODUTOS p
        WHERE p.planilha_id = :id_planilha2 AND p.editado = 1 AND p.editado_dependencia_id IS NOT NULL
        ORDER BY dependencia
    ";
  $stmt_dependencias = $conexao->prepare($sql_dependencias);
  $stmt_dependencias->bindValue(':id_planilha1', $id_planilha);
  $stmt_dependencias->bindValue(':id_planilha2', $id_planilha);
  $stmt_dependencias->execute();
  $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
  $dependencia_options = [];
}

// Mapear ID -> descri+Ã¯Â¿Â½+Ã¯Â¿Â½o/c+Ã¯Â¿Â½digo para exibir label amig+Ã¯Â¿Â½vel no filtro
$dependencias_map = [];
if (!empty($dependencia_options)) {
  $placeholders = implode(',', array_fill(0, count($dependencia_options), '?'));
  $stmtDepMap = $conexao->prepare("SELECT id, codigo, descricao FROM dependencias WHERE id IN ($placeholders)");
  foreach ($dependencia_options as $idx => $depId) {
    $stmtDepMap->bindValue($idx + 1, (int)$depId, PDO::PARAM_INT);
  }
  if ($stmtDepMap->execute()) {
    foreach ($stmtDepMap->fetchAll(PDO::FETCH_ASSOC) as $d) {
      $dependencias_map[(int)$d['id']] = [
        'codigo' => $d['codigo'],
        'descricao' => $d['descricao']
      ];
    }
  }
}

$PRODUTOS_pendentes = $PRODUTOS_checados = $PRODUTOS_observacao = $PRODUTOS_checados_observacao = $PRODUTOS_dr = $PRODUTOS_etiqueta = $PRODUTOS_alteracoes = $PRODUTOS_novos = [];
foreach ($todos_PRODUTOS as $PRODUTO) {
  // Nome atual: usa descriÃ¯Â¿Â½Ã¯Â¿Â½o editada se existir, senÃ¯Â¿Â½o a descriÃ¯Â¿Â½Ã¯Â¿Â½o completa original
  $nome_editado = trim($PRODUTO['nome_editado'] ?? '');
  $nome_original = trim($PRODUTO['descricao_completa'] ?? ($PRODUTO['nome'] ?? ''));
  $nome_atual = $nome_editado !== '' ? $nome_editado : $nome_original;
  $PRODUTO['nome_atual'] = $nome_atual !== '' ? $nome_atual : 'Sem descricao';
  $PRODUTO['nome_original'] = $nome_original;

  // PRODUTOS novos = vindos da tabela PRODUTOS_cadastro
  if (($PRODUTO['origem'] ?? '') === 'cadastro') {
    $PRODUTOS_novos[] = $PRODUTO;
    if (!empty($PRODUTO['codigo'])) {
      $PRODUTOS_etiqueta[] = $PRODUTO; // novos com cÃ¯Â¿Â½digo tambÃ¯Â¿Â½m vÃ¯Â¿Â½o para etiqueta
    }
    continue;
  }

  // PRODUTOS da planilha importada (tabela PRODUTOS)
  $tem_observacao = !empty($PRODUTO['observacoes']);
  $esta_checado = ($PRODUTO['checado'] ?? 0) == 1;
  $esta_no_dr = ($PRODUTO['ativo'] ?? 1) == 0;
  $esta_etiqueta = ($PRODUTO['imprimir'] ?? 0) == 1;
  $tem_alteracoes = (int)($PRODUTO['editado'] ?? 0) === 1;
  $eh_pendente = is_null($PRODUTO['checado']) && ($PRODUTO['ativo'] ?? 1) == 1 && is_null($PRODUTO['imprimir']) && is_null($PRODUTO['observacoes']) && is_null($PRODUTO['editado']);

  if ($tem_alteracoes) {
    // Editados aparecem aqui e tambÃ¯Â¿Â½m mantÃ¯Â¿Â½m sua seÃ¯Â¿Â½Ã¯Â¿Â½o de STATUS
    $PRODUTOS_alteracoes[] = $PRODUTO;
    $PRODUTOS_etiqueta[] = $PRODUTO;
  }

  if ($esta_no_dr) {
    $PRODUTOS_dr[] = $PRODUTO;
  } elseif ($esta_etiqueta) {
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
$total_dr = count($PRODUTOS_dr);
$total_etiqueta = count($PRODUTOS_etiqueta);
$total_alteracoes = count($PRODUTOS_alteracoes);
$total_novos = count($PRODUTOS_novos);
$total_geral = count($todos_PRODUTOS);

// DEBUG: Verificar PRODUTOS com editado = 1
if (isset($_GET['debug'])) {
  echo "<pre>DEBUG - PRODUTOS com editado:<br>";
  foreach ($todos_PRODUTOS as $p) {
    if (($p['origem'] ?? '') !== 'cadastro') {
      $editado_valor = $p['editado'] ?? 'NULL';
      $editado_tipo = gettype($p['editado'] ?? null);
      $tem_nome_editado = !empty($p['nome_editado']) ? 'SIM' : 'N+Ã¯Â¿Â½O';
      $tem_dep_editada = !empty($p['dependencia_editada']) ? 'SIM' : 'N+Ã¯Â¿Â½O';
      if ((int)($p['editado'] ?? 0) === 1 || !empty($p['nome_editado']) || !empty($p['dependencia_editada'])) {
        echo "ID: {$p['id']} | C+Ã¯Â¿Â½digo: {$p['codigo']} | editado={$editado_valor} (tipo: {$editado_tipo}) | nome_editado: {$tem_nome_editado} | dep_editada: {$tem_dep_editada}<br>";
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
if ($mostrar_dr) $total_mostrar += $total_dr;
if ($mostrar_etiqueta) $total_mostrar += $total_etiqueta;
if ($mostrar_alteracoes) $total_mostrar += $total_alteracoes;
if ($mostrar_novos) $total_mostrar += $total_novos;

// Cabe+Ã¯Â¿Â½alho do layout
$pageTitle = 'Imprimir Altera+Ã¯Â¿Â½+Ã¯Â¿Â½es';
$backUrl = '../planilhas/planilha_visualizar.php?id=' . $id_planilha;
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
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// CSS de impress+Ã¯Â¿Â½o e ajustes para o wrapper mobile
$customCss = '
@media print {
  .app-header, .no-print { display: none !important; }
  .app-container { padding: 0 !important; }
  .mobile-wrapper { max-width: 100% !important; border-radius: 0 !important; box-shadow: none !important; }
  .app-content { padding: 0 !important; background: #fff !important; }
  table { page-break-inside: auto; }
  tr { page-break-inside: avoid; page-break-after: auto; }
}
.table thead th { font-size: 12px; }
.table td { font-size: 12px; }
';

// Conte+Ã¯Â¿Â½do da p+Ã¯Â¿Â½gina
ob_start();
?>

<!-- Filtros -->
<div class="card mb-3 no-print">
  <div class="card-header">
    <i class="bi bi-filter-circle me-2"></i> Filtros do relat+Ã¯Â¿Â½rio
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
      <div class="col-12">
        <label class="form-label">Se+Ã¯Â¿Â½+Ã¯Â¿Â½es a incluir</label>
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
              <label class="form-check-label" for="secObs">Com Observa+Ã¯Â¿Â½+Ã¯Â¿Â½o (<?php echo $total_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChecObs" name="mostrar_checados_observacao" value="1" <?php echo $mostrar_checados_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChecObs">Checados com Observa+Ã¯Â¿Â½+Ã¯Â¿Â½o (<?php echo $total_checados_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secDR" name="mostrar_dr" value="1" <?php echo $mostrar_dr ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secDR">Devolu+Ã¯Â¿Â½+Ã¯Â¿Â½o (DR) (<?php echo $total_dr; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secEtiq" name="mostrar_etiqueta" value="1" <?php echo $mostrar_etiqueta ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secEtiq">Para Etiqueta (<?php echo $total_etiqueta; ?>)</label>
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
              <label class="form-check-label" for="secNovos">Cadastrados Novos (<?php echo $total_novos; ?>)</label>
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

<!-- Cabe+Ã¯Â¿Â½alho do relat+Ã¯Â¿Â½rio -->
<div class="card mb-3">
  <div class="card-body text-center">
    <h5 class="mb-1 text-gradient">RELAT+Ã¯Â¿Â½RIO DE ALTERA+Ã¯Â¿Â½+Ã¯Â¿Â½ES</h5>
    <div class="text-muted">Planilha: <?php echo htmlspecialchars($planilha['comum']); ?></div>
    <div class="small text-muted">Gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
  </div>
  <div class="card-footer">
    <?php
    // STATUS din+Ã¯Â¿Â½mico da planilha com base nos totais
    if ($total_pendentes === $total_geral && $total_novos === 0) {
      $STATUS_calc = 'Pendente';
      $badge = 'secondary';
    } elseif ($total_pendentes === 0) {
      $STATUS_calc = 'Conclu+Ã¯Â¿Â½da';
      $badge = 'success';
    } else {
      $STATUS_calc = 'Em Execu+Ã¯Â¿Â½+Ã¯Â¿Â½o';
      $badge = 'warning text-dark';
    }
    ?>
    <div><strong>STATUS:</strong> <span class="badge bg-<?php echo $badge; ?>"><?php echo $STATUS_calc; ?></span></div>
  </div>
</div>

<!-- Resumo -->
<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-graph-up-arrow me-2"></i> Resumo geral
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Total de PRODUTOS:</strong> <?php echo $total_geral; ?></li>
      <li><strong>Checados:</strong> <?php echo $total_checados; ?></li>
      <li><strong>Com observa+Ã¯Â¿Â½+Ã¯Â¿Â½o:</strong> <?php echo $total_observacao; ?></li>
      <li><strong>Checados + observa+Ã¯Â¿Â½+Ã¯Â¿Â½o:</strong> <?php echo $total_checados_observacao; ?></li>
      <li><strong>DR:</strong> <?php echo $total_dr; ?></li>
      <li><strong>Etiqueta:</strong> <?php echo $total_etiqueta; ?></li>
      <li><strong>Pendentes:</strong> <?php echo $total_pendentes; ?></li>
      <li><strong>Com altera+Ã¯Â¿Â½+Ã¯Â¿Â½es:</strong> <?php echo $total_alteracoes; ?></li>
      <li><strong>Novos:</strong> <?php echo $total_novos; ?></li>
      <li class="mt-2"><strong>Total a ser impresso:</strong> <?php echo $total_mostrar; ?> PRODUTOS</li>
    </ul>
  </div>
</div>

<?php if ($total_geral > 0 && $total_mostrar > 0): ?>
  <?php if ($mostrar_alteracoes && $total_alteracoes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">PRODUTOS com altera+Ã¯Â¿Â½+Ã¯Â¿Â½es (<?php echo $total_alteracoes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>Antigo</th>
                <th>Novo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_alteracoes as $PRODUTO): ?>
                <?php
                // Construir texto antigo e novo
                $antigo = [];
                $novo = [];

                // Verificar alteraÃ¯Â¿Â½Ã¯Â¿Â½o no nome (usar descriÃ¯Â¿Â½Ã¯Â¿Â½es completas jÃ¯Â¿Â½ montadas)
                $nome_original = $PRODUTO['nome_original'] ?? ($PRODUTO['nome'] ?? '');
                $nome_atual = $PRODUTO['nome_atual'] ?? $nome_original;
                if (!empty($PRODUTO['nome_editado']) && $PRODUTO['nome_editado'] != $nome_original) {
                  $antigo[] = htmlspecialchars($nome_original);
                  $novo[] = htmlspecialchars($nome_atual);
                } else {
                  // Se nÃ¯Â¿Â½o mudou, mostrar o nome atual em ambas as colunas
                  $antigo[] = htmlspecialchars($nome_atual);
                  $novo[] = htmlspecialchars($nome_atual);
                }

                $texto_antigo = implode('<br>', $antigo);
                $texto_novo = implode('<br>', $novo);
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo $texto_antigo; ?></td>
                  <td class="table-warning"><?php echo $texto_novo; ?></td>
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
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
                <th>Depend+Ã¯Â¿Â½ncia</th>
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
      <div class="card-header">PRODUTOS checados (<?php echo $total_checados; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
                <th>Depend+Ã¯Â¿Â½ncia</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_checados as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td class="table-success"><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td><?php echo htmlspecialchars($PRODUTO['dependencia'] ?? ''); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_observacao && $total_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">PRODUTOS com observa+Ã¯Â¿Â½+Ã¯Â¿Â½o (<?php echo $total_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
                <th>Observa+Ã¯Â¿Â½+Ã¯Â¿Â½es</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_observacao as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td class="table-warning fst-italic"><?php echo htmlspecialchars($PRODUTO['observacoes']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados_observacao && $total_checados_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Checados + observa+Ã¯Â¿Â½+Ã¯Â¿Â½o (<?php echo $total_checados_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
                <th>Observa+Ã¯Â¿Â½+Ã¯Â¿Â½es</th>
              </tr>
            </thead>
            <tbody><?php foreach ($PRODUTOS_checados_observacao as $PRODUTO): ?><tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td class="table-secondary"><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                  <td class="table-secondary"><?php echo htmlspecialchars($PRODUTO['observacoes']); ?></td>
                </tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_dr && $total_dr > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Devolu+Ã¯Â¿Â½+Ã¯Â¿Â½o (DR) (<?php echo $total_dr; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_dr as $PRODUTO): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td class="table-danger"><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_etiqueta && $total_etiqueta > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Para Etiqueta (<?php echo $total_etiqueta; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C+Ã¯Â¿Â½digo</th>
                <th>DescriÃ¯Â¿Â½Ã¯Â¿Â½o</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_etiqueta as $PRODUTO): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($PRODUTO['codigo']); ?></strong></td>
                  <td class="table-success"><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></td>
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
      <div class="card-header">Cadastrados Novos (<?php echo $total_novos; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Descri+Ã¯Â¿Â½+Ã¯Â¿Â½o Completa</th>
                <th class="text-center">Quantidade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUTOS_novos as $PRODUTO): ?>
                <tr>
                  <td class="table-success"><strong><?php echo htmlspecialchars($PRODUTO['nome_atual']); ?></strong></td>
                  <td class="text-center"><?php echo htmlspecialchars($PRODUTO['quantidade'] ?? 'Ã¯Â¿Â½Ã¯Â¿Â½Ã¯Â¿Â½'); ?></td>
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
    <i class="bi bi-info-circle me-2"></i> Marque pelo menos uma se+Ã¯Â¿Â½+Ã¯Â¿Â½o para visualizar o relat+Ã¯Â¿Â½rio.
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="bi bi-emoji-frown me-2"></i> Nenhum PRODUTO encontrado para os filtros aplicados.
  </div>
<?php endif; ?>

<div class="text-center text-muted small my-3">
  Relat+Ã¯Â¿Â½rio gerado em <?php echo date('d/m/Y \+Ã¯Â¿Â½\s H:i:s'); ?>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_imprimir_alteracao_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

include __DIR__ . '/../layouts/app_wrapper.php';

unlink($tempFile);
?>
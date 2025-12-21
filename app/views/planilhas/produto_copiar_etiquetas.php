<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AutenticaÃƒÂ§ÃƒÂ£o

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
  if (!$planilha) throw new Exception('Planilha não encontrada.');
} catch (PDOException $e) {
  if ($e->getCode() === '42S02' || stripos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false) {
    die("Erro: tabela 'planilhas' não encontrada no banco de dados. Importe `anvycomb_checkplanilha.sql` (raiz do projeto) ou verifique `config/database.php`.");
  }
  die('Erro ao carregar planilha: ' . $e->getMessage());
} catch (Exception $e) {
  die('Erro ao carregar planilha: ' . $e->getMessage());
}

// DependÃƒÂªncias disponÃƒÂ­veis
try {
  $sql_dependencias = "
        SELECT DISTINCT d.descricao as dependencia FROM PRODUTOS p
        LEFT JOIN dependencias d ON COALESCE(p.editado_dependencia_id, p.dependencia_id) = d.id
        WHERE p.comum_id = :comum_id AND d.descricao IS NOT NULL
        ORDER BY dependencia
    ";
  $stmt_dependencias = $conexao->prepare($sql_dependencias);
  $stmt_dependencias->bindValue(':comum_id', $id_planilha);
  $stmt_dependencias->execute();
  $dependencias = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
  $dependencias = [];
}

$dependencia_selecionada = $_GET['dependencia'] ?? '';

// PRODUTOS marcados para imprimir (PRODUTOS checados)
try {
  $sql_PRODUTOS = "SELECT p.codigo, COALESCE(d_edit.descricao, d_orig.descricao, '') as dependencia
                     FROM PRODUTOS p 
                     LEFT JOIN dependencias d_orig ON p.dependencia_id = d_orig.id
                     LEFT JOIN dependencias d_edit ON p.editado_dependencia_id = d_edit.id
                     WHERE p.comum_id = :comum_id AND COALESCE(p.imprimir_etiqueta, 0) = 1";
  if (!empty($dependencia_selecionada)) {
    $sql_PRODUTOS .= " AND (
            (COALESCE(d_edit.descricao, d_orig.descricao) = :dependencia)
        )";
  }
  $sql_PRODUTOS .= " ORDER BY p.codigo";
  $stmt_PRODUTOS = $conexao->prepare($sql_PRODUTOS);
  $stmt_PRODUTOS->bindValue(':comum_id', $id_planilha);
  if (!empty($dependencia_selecionada)) {
    $stmt_PRODUTOS->bindValue(':dependencia', $dependencia_selecionada);
  }
  $stmt_PRODUTOS->execute();
  $PRODUTOS = $stmt_PRODUTOS->fetchAll(PDO::FETCH_ASSOC);

  // BUSCAR tambÃƒÂ©m PRODUTOS cadastrados (novos) com cÃƒÂ³digo preenchido
  // Nota: tabela PRODUTOS_cadastro nÃƒÂ£o existe no schema atual, entÃƒÂ£o comentado
  // $sql_novos = "SELECT pc.codigo, d.descricao as dependencia
  // FROM PRODUTOS_cadastro pc
  // LEFT JOIN dependencias d ON pc.id_dependencia = d.id
  // WHERE pc.id_planilha = :comum_id 
  // AND pc.codigo IS NOT NULL 
  // AND pc.codigo != ''";
  // if (!empty($dependencia_selecionada)) {
  //     $sql_novos .= " AND d.descricao = :dependencia";
  // }
  // $sql_novos .= " ORDER BY pc.codigo";
  // $stmt_novos = $conexao->prepare($sql_novos);
  // $stmt_novos->bindValue(':comum_id', $id_planilha);
  // if (!empty($dependencia_selecionada)) { $stmt_novos->bindValue(':dependencia', $dependencia_selecionada); }
  // $stmt_novos->execute();
  // $PRODUTOS_novos = $stmt_novos->fetchAll(PDO::FETCH_ASSOC);

  $PRODUTOS_novos = []; // Temporariamente vazio atÃƒÂ© tabela existir

  // Combinar PRODUTOS checados e novos
  $PRODUTOS = array_merge($PRODUTOS, $PRODUTOS_novos);

  $codigos = array_column($PRODUTOS, 'codigo');
  $PRODUTOS_sem_espacos = array_map(fn($c) => str_replace(' ', '', $c), $codigos);
  $codigos_concatenados = implode(',', $PRODUTOS_sem_espacos);
} catch (Exception $e) {
  $codigos_concatenados = '';
  $PRODUTOS = [];
  $mensagem = 'Erro ao carregar PRODUTOS: ' . $e->getMessage();
}

$pageTitle = 'Copiar Etiquetas';
$backUrl = '../planilhas/planilha_visualizar.php?id=' . urlencode($comum_id) . '&comum_id=' . urlencode($comum_id);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuEtiquetas" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuEtiquetas">
            <li>
                <a class="dropdown-item" href="../planilhas/planilha_visualizar.php?id=' . $comum_id . '&comum_id=' . $comum_id . '">
                    <i class="bi bi-eye me-2"></i>VISUALIZAR Planilha
                </a>
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

ob_start();
?>

<?php if (!empty($mensagem)): ?>
  <div class="alert alert-danger"><?php echo $mensagem; ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-tag me-2"></i>
    CÃƒÂ³digos para ImpressÃƒÂ£o de Etiquetas
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      Lista com os cÃƒÂ³digos dos PRODUTOS marcados como "Para Imprimir" e dos PRODUTOS novos cadastrados com cÃƒÂ³digo preenchido.
    </p>

    <?php if (!empty($dependencias)): ?>
      <div class="mb-3">
        <label for="filtroDependencia" class="form-label">Filtrar por dependÃƒÂªncia</label>
        <select class="form-select" id="filtroDependencia" onchange="filtrarPorDependencia()">
          <option value="">Todas as dependÃƒÂªncias</option>
          <?php foreach ($dependencias as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo ($dependencia_selecionada === $dep) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-2 small">
      <div class="col-6">
        <div class="card shadow-sm-custom">
          <div class="card-body text-center">
            <div class="h4 mb-0"><?php echo count($PRODUTOS); ?></div>
            <div class="text-muted">PRODUTOS</div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card shadow-sm-custom">
          <div class="card-body text-center">
            <div class="h4 mb-0"><?php echo count(array_unique($PRODUTOS_sem_espacos ?? [])); ?></div>
            <div class="text-muted">CÃƒÂ³digos ÃƒÂºnicos</div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($PRODUTOS)): ?>
      <div class="mt-3 position-relative">
        <label for="codigosField" class="form-label">CÃƒÂ³digos</label>
        <textarea id="codigosField" class="form-control" rows="6" readonly onclick="this.select()"><?php echo htmlspecialchars($codigos_concatenados); ?></textarea>
        <button class="btn btn-primary btn-sm mt-2 w-100" onclick="copiarCodigos()">
          <i class="bi bi-clipboard-check me-2"></i>
          Copiar para ÃƒÂ¡rea de transferÃƒÂªncia
        </button>
        <div class="form-text">Clique no campo para selecionar tudo rapidamente.</div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning mt-3 text-center">
        <strong>Nenhum PRODUTO disponÃƒÂ­vel para etiquetas.</strong>
        <?php if (!empty($dependencia_selecionada)): ?>
          <div class="small">NÃƒÂ£o hÃƒÂ¡ PRODUTOS marcados ou cadastrados com cÃƒÂ³digo na dependÃƒÂªncia "<?php echo htmlspecialchars($dependencia_selecionada); ?>".</div>
        <?php else: ?>
          <div class="small">Marque PRODUTOS com o ÃƒÂ­cone de etiqueta Ã°Å¸ÂÂ·Ã¯Â¸Â ou cadastre PRODUTOS com cÃƒÂ³digo preenchido.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  function copiarCodigos() {
    const codigosField = document.getElementById('codigosField');
    codigosField.select();
    codigosField.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(codigosField.value).then(() => {
      const btn = document.activeElement;
    });
  }

  function filtrarPorDependencia() {
    const dependencia = document.getElementById('filtroDependencia').value;
    const url = new URL(window.location);
    if (dependencia) url.searchParams.set('dependencia', dependencia);
    else url.searchParams.delete('dependencia');
    window.location.href = url.toString();
  }
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_copiar_etiquetas_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
$headerActions = '';
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>
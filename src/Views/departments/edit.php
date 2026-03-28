<?php

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'EDITAR DEPÊNDÊNCIA';
$backUrl = '/departments';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <?php if ($tipo_mensagem === 'success'): ?>
        <div class="p-4 mb-4 flex justify-between items-center" style="background:#f0fdf4;border:1px solid #86efac;border-radius:2px">
            <span style="color:#166534"><?php echo $mensagem; ?></span>
            <button type="button" style="color:#166534" onclick="this.parentElement.style.display='none';"><i class="bi bi-x"></i></button>
        </div>
    <?php else: ?>
        <div class="p-4 mb-4 flex justify-between items-center" style="background:#fef2f2;border:1px solid #fecaca;border-radius:2px">
            <span style="color:#991b1b"><?php echo $mensagem; ?></span>
            <button type="button" style="color:#991b1b" onclick="this.parentElement.style.display='none';"><i class="bi bi-x"></i></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($dependencia)): ?>
    <form method="POST" id="formDependencia" class="space-y-4">
        <input type="hidden" name="id" value="<?php echo (int)($dependencia['id'] ?? 0); ?>">
        <div class="bg-white border border-neutral-200 max-w-2xl mx-auto" style="border-radius:2px">
            <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
                <i class="bi bi-pencil-square text-neutral-600"></i>
                <span class="font-semibold text-neutral-700">EDITAR DEPENDÊNCIA</span>
            </div>
            <div class="p-6">

                <div>
                    <label for="descricao" class="block text-sm font-medium text-neutral-700 mb-2"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?> <span style="color:#991b1b">*</span></label>
                    <textarea class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" id="descricao" name="descricao" rows="3" required style="border-radius:2px"><?php echo htmlspecialchars($dependencia['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="text-neutral-500 text-sm"><?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Descrição da dependência'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 max-w-2xl mx-auto">
            <button type="submit" class="w-full px-4 py-3 bg-black hover:bg-neutral-900 text-white font-semibold transition flex items-center justify-center gap-2" style="border-radius:2px">
                <i class="bi bi-check-lg"></i>ATUALIZAR DEPENDÊNCIA
            </button>
        </div>
    </form>

    <script>
        // Validação do formulário
        document.getElementById('formDependencia').addEventListener('submit', function(e) {
            const descricao = document.getElementById('descricao').value.trim();

            if (!descricao) {
                e.preventDefault();
                alert('<?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase("A descrição é obrigatória!"), ENT_QUOTES, 'UTF-8'); ?>');
                return false;
            }
        });
    </script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

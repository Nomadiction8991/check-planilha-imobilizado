<?php


/**
 * View: Editar Comum
 * Formulário moderno para edição de comuns usando arquitetura MVC
 */
?>

<div class="px-3 py-6">
    <div class="bg-white border border-neutral-200 max-w-4xl mx-auto" style="border-radius:2px">
        <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200 flex items-center gap-2">
            <i class="bi bi-pencil-square text-neutral-600"></i>
            <span class="font-semibold text-neutral-700"><?= htmlspecialchars(mb_strtoupper('Editar Comum', 'UTF-8')) ?></span>
        </div>
        <div class="p-6">
            <?php if (!empty($_SESSION['mensagem'])): ?>
                <div class="border p-4 mb-4 flex justify-between items-center" style="border-radius:2px;background:#fafafa;border-color:#d4d4d4">
                    <span style="color:#171717"><?= htmlspecialchars($_SESSION['mensagem']) ?></span>
                    <button type="button" style="color:#171717" onclick="this.parentElement.style.display='none';"><i class="bi bi-x"></i></button>
                </div>
                <?php
                unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
                ?>
            <?php endif; ?>

            <form method="POST" action="/churches/edit" id="formEditarComum" class="space-y-4">
                <input type="hidden" name="id" value="<?= (int)$comum['id'] ?>">
                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="pagina" value="<?= (int)($pagina ?? 1) ?>">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <!-- Código -->
                    <div class="lg:col-span-3">
                        <label for="codigo" class="block text-sm font-medium text-neutral-700 mb-2 flex items-center gap-1">
                            <i class="bi bi-tag"></i>Código <span style="color:#991b1b">*</span>
                        </label>
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            id="codigo"
                            name="codigo"
                            value="<?= htmlspecialchars($comum['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                            maxlength="50">
                    </div>

                    <!-- Descrição -->
                    <div class="lg:col-span-9">
                        <label for="descricao" class="block text-sm font-medium text-neutral-700 mb-2 flex items-center gap-1">
                            <i class="bi bi-card-text"></i>Descrição <span style="color:#991b1b">*</span>
                        </label>
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            id="descricao"
                            name="descricao"
                            value="<?= htmlspecialchars($comum['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                            maxlength="255">
                    </div>

                    <!-- CNPJ -->
                    <div class="lg:col-span-6">
                        <label for="cnpj" class="block text-sm font-medium text-neutral-700 mb-2 flex items-center gap-1">
                            <i class="bi bi-file-earmark-text"></i>CNPJ
                        </label>
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            id="cnpj"
                            name="cnpj"
                            value="<?= htmlspecialchars($comum['cnpj'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="18"
                            placeholder="00.000.000/0000-00">
                    </div>

                    <!-- Administração -->
                    <div class="lg:col-span-6">
                        <label for="administracao" class="block text-sm font-medium text-neutral-700 mb-2 flex items-center gap-1">
                            <i class="bi bi-building"></i>Administração
                        </label>
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            id="administracao"
                            name="administracao"
                            value="<?= htmlspecialchars($comum['administracao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="100">
                    </div>

                    <!-- Cidade -->
                    <div class="lg:col-span-6">
                        <label for="cidade" class="block text-sm font-medium text-neutral-700 mb-2 flex items-center gap-1">
                            <i class="bi bi-geo-alt"></i>Cidade
                        </label>
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            id="cidade"
                            name="cidade"
                            value="<?= htmlspecialchars($comum['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="100">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t border-neutral-200">
                    <button type="submit" class="px-4 py-2 bg-black hover:bg-neutral-900 text-white font-semibold transition flex items-center justify-center gap-2" style="border-radius:2px">
                        <i class="bi bi-check-lg"></i>Salvar Alterações
                    </button>
                    <a href="<?= $backUrl ?? '/churches' ?>" class="px-4 py-2 bg-neutral-500 hover:bg-neutral-600 text-white font-semibold transition flex items-center justify-center gap-2" style="border-radius:2px">
                        <i class="bi bi-x-lg"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- InputMask para CNPJ (formatação no cliente) -->
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('cnpj');
        if (el) {
            // Máscara 00.000.000/0000-00 e limpa entrada incompleta
            Inputmask({
                mask: '99.999.999/9999-99',
                clearIncomplete: true
            }).mask(el);
        }
    });
</script>

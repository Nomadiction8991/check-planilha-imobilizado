<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


$pageTitle = to_uppercase('editar produto');
$backUrl = getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_STATUS);

ob_start();
?>

<style>
    .text-uppercase-input {
        text-transform: uppercase;
    }


    /* Estilos para o boto de microfone */
    .mic-btn {
        /* herda totalmente o estilo do .btn (Bootstrap) */
        cursor: pointer;
        padding: 0.5rem;
        transition: all 0.3s ease;
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .mic-btn:focus,
    .mic-btn:active {
        transform: none !important;
        box-shadow: none !important;
    }

    .mic-btn .material-icons-round {
        color: white !important;
        transition: color 0.3s ease;
    }

    .mic-btn.listening .material-icons-round {
        color: #dc3545 !important;
        /* vermelho quando gravando */
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.15);
        }
    }

    /* Garantir que botes do input-group no se movam */
    .input-group .btn {
        transform: none !important;
    }

    .input-group .btn:hover,
    .input-group .btn:focus,
    .input-group .btn:active {
        transform: none !important;
    }

    .mic-btn .material-icons-round {
        font-size: 20px;
        vertical-align: middle;
    }

    /* Estilos padro para todos os dispositivos (mobile-first) */
    .input-group {
        flex-wrap: nowrap !important;
        display: flex !important;
    }

    .input-group .form-control {
        min-width: 0;
        flex: 1 1 auto !important;
        /* Input preenche o espao restante */
    }

    .input-group>.btn {
        flex: 0 0 15% !important;
        /* Botes ocupam 15% cada */
        min-width: 45px !important;
        max-width: 60px !important;
        padding: 0.375rem 0.25rem !important;
        font-size: 1.1rem !important;
    }

    .input-group>.btn .material-icons-round,
    .input-group>.btn i {
        font-size: 20px !important;
    }

    /* Cores das linhas baseadas no STATUS - Paleta marcante e diferenciada */
    .linha-pendente {
        background-color: #ffffff;
        border-left: none;
    }

    .linha-checado {
        background-color: #d4f4dd;
        border-left: 4px solid #10b759;
    }

    .linha-observacao {
        background-color: #fff4e6;
        border-left: 4px solid #fb8c00;
    }

    .linha-imprimir {
        background-color: #e3f2fd;
        border-left: 4px solid #1976d2;
    }

    .linha-dr {
        background-color: #F1F3F5;
        border-left: 4px solid #6C757D;
    }

    .linha-editado {
        background-color: #F3E5F5;
        border-left: 4px solid #8E24AA;
    }

    .linha-checado {
        background-color: #E9F7EF;
    }

    .linha-imprimir {
        background-color: #E8F4FF;
    }

    .linha-observacao {
        background-color: #FFF8E1;
    }

    /* Aviso de tipo no identificado - amarelo ouro forte */
    .tipo-nao-identificado {
        border-left: 4px solid #fdd835 !important;
    }

    /* Aes: usar padro Bootstrap para botes e manter largura proporcional */
    .acao-container .btn {
        aspect-ratio: 1 / 1;
        width: 48px;
        min-width: 48px;
        max-width: 48px;
        height: 48px;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        border-radius: 0.85rem;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease;
    }

    .acao-container .btn:not([disabled]):not(.disabled):hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
    }

    .acao-container .btn:not([disabled]):not(.disabled):hover {
        transform: translateY(-1px);
        ¤ // Iniciar bufferrios
        $headerActions .='
 <li><a class="dropdown-item" href="/relatorios/14-1?id=' . $comum_id . '&comum_id=' . $comum_id . '"><i class="bi bi-file-earmark-pdf me-2"></i>' . htmlspecialchars(to_uppercase(' Relatrio 14.1'), ENT_QUOTES, ' UTF-8') . '
        </a>$headerActions .='
 <li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="/logout"><i class="bi bi-box-arrow-right me-2"></i>Sair </a></li></ul></div>';

        // Iniciar buffer para capturar o contedo
        ob_start();
        ?><style>

        /* Estilos para o botºo de microfone */
        .mic-btn {
            /* herda totalmente o estilo do .btn (Bootstrap) */
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .mic-btn:focus,
        .mic-btn:active {
            transform: none !important;
            box-shadow: none !important;
        }

        .mic-btn .material-icons-round {
            color: white !important;
            transition: color 0.3s ease;
        }

        .mic-btn.listening .material-icons-round {
            color: #dc3545 !important;
            /* vermelho quando gravando */
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }
        }

        /* Garantir que botes do input-group nºo se movam */
        .input-group .btn {
            transform: none !important;
        }

        .input-group .btn:hover,
        .input-group .btn:focus,
        .input-group .btn:active {
            transform: none !important;
        }

        .mic-btn .material-icons-round {
            font-size: 20px;
            vertical-align: middle;
        }

        /* Estilos padrºo para todos os dispositivos (mobile-first) */
        .input-group {
            flex-wrap: nowrap !important;
            display: flex !important;
        }

        .input-group .form-control {
            min-width: 0;
            flex: 1 1 auto !important;
            /* Input preenche o espaºo restante */
        }

        .input-group>.btn {
            flex: 0 0 15% !important;
            /* Botes ocupam 15% cada */
            min-width: 45px !important;
            max-width: 60px !important;
            padding: 0.375rem 0.25rem !important;
            font-size: 1.1rem !important;
        }

        .input-group>.btn .material-icons-round,
        .input-group>.btn i {
            font-size: 20px !important;
        }

        /* Cores das linhas baseadas no STATUS - Paleta marcante e diferenciada */
        .linha-pendente {
            background-color: #ffffff;
            border-left: none;
        }

        .linha-checado {
            background-color: #d4f4dd;
            border-left: 4px solid #10b759;
        }

        .linha-observacao {
            background-color: #fff4e6;
            border-left: 4px solid #fb8c00;
        }

        .linha-imprimir {
            background-color: #e3f2fd;
            border-left: 4px solid #1976d2;
        }

        .linha-dr {
            background-color: #F1F3F5;
            border-left: 4px solid #6C757D;
        }

        .linha-editado {
            background-color: #F3E5F5;
            border-left: 4px solid #8E24AA;
        }

        .linha-checado {
            background-color: #E9F7EF;
        }

        .linha-imprimir {
            background-color: #E8F4FF;
        }

        .linha-observacao {
            background-color: #FFF8E1;
        }

        /* Aviso de tipo nºo identificado - amarelo ouro forte */
        .tipo-nao-identificado {
            border-left: 4px solid #fdd835 !important;
        }

        /* Aes: usar padro Bootstrap para botes e manter largura proporcional */
        .acao-container .btn {
            aspect-ratio: 1 / 1;
            width: 48px;
            min-width: 48px;
            max-width: 48px;
            height: 48px;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border-radius: 0.85rem;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease;
        }

        .acao-container .btn[disabled],
        .acao-container .btn.disabled {
            pointer-events: none;
            opacity: 0.55;
        }

        /* Boto visualmente desabilitado (mas clicvel quando necessrio, ex: imprimir que autocheca) */
        .acao-container .btn.disabled-visually {
            pointer-events: auto;
            opacity: 0.45;
            filter: grayscale(0.18);
        }

        .acao-container .btn.disabled-visually:hover {
            transform: none;
            box-shadow: none;
        }

        /* Cores dos botes (paleta coerente com tema) */
        .acao-container .action-check button {
            border-color: #28A745;
            color: #28A745;
        }

        .acao-container .action-check button.active,
        .acao-container .action-check button:hover {
            background: #28A745;
            color: #fff;
        }

        .acao-container .action-imprimir button {
            border-color: #0D6EFD;
            color: #0D6EFD;
        }

        .acao-container .action-imprimir button.active,
        .acao-container .action-imprimir button:hover {
            background: #0D6EFD;
            color: #fff;
        }

        /* Aparncia quando o boto de imprimir estiver bloqueado (produto editado) */
        .acao-container .action-imprimir button[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
            filter: grayscale(20%);
        }

        .acao-container .action-observacao {
            border-color: #FB8C00 !important;
            color: #FB8C00 !important;
        }

        .acao-container .action-observacao:hover {
            background: #FB8C00 !important;
            color: #fff !important;
        }

        .acao-container .action-etiqueta {
            border-color: #6F42C1 !important;
            color: #6F42C1 !important;
        }

        .acao-container .action-etiqueta:hover {
            background: #6F42C1 !important;
            color: #fff !important;
        }

        .acao-container .action-signatarios {
            border-color: #17A2B8 !important;
            color: #17A2B8 !important;
        }

        .acao-container .action-signatarios:hover {
            background: #17A2B8 !important;
            color: #fff !important;
        }

        .acao-container .action-editar {
            border-color: #6C757D !important;
            color: #6C757D !important;
        }

        .acao-container .action-editar:hover {
            background: #6C757D !important;
            color: #fff !important;
        }

        /* Indicadores de estado (para botes com toggle) */
        .acao-container .btn.active {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.18);
        }

        /* Linha com produtos em diferentes estados */
        .linha-pendente {
            background-color: #ffffff;
            border-left: none;
        }

        .linha-checado {
            background-color: #d4f4dd;
            border-left: 4px solid #10b759;
        }

        .linha-observacao {
            background-color: #fff4e6;
            border-left: 4px solid #fb8c00;
        }

        .linha-imprimir {
            background-color: #e3f2fd;
            border-left: 4px solid #1976d2;
        }

        .linha-dr {
            background-color: #F1F3F5;
            border-left: 4px solid #6C757D;
        }

        .linha-editado {
            background-color: #F3E5F5;
            border-left: 4px solid #8E24AA;
        }

        .linha-checado {
            background-color: #E9F7EF;
        }

        .linha-imprimir {
            background-color: #E8F4FF;
        }

        .linha-observacao {
            background-color: #FFF8E1;
        }

        /* Aviso de tipo no identificado - amarelo ouro forte */
        .tipo-nao-identificado {
            border-left: 4px solid #fdd835 !important;
        }

        /* Boto visualmente desabilitado (mas clicvel quando necessrio, ex: imprimir que autocheca) */
        .acao-container .btn.disabled-visually {
            pointer-events: auto;
            opacity: 0.45;
            filter: grayscale(0.18);
        }

        .acao-container .btn.disabled-visually:hover {
            transform: none;
            box-shadow: none;
        }

        /* Cores dos botes (paleta coerente com tema) */
        .acao-container .action-check button {
            border-color: #28A745;
            color: #28A745;
        }

        .acao-container .action-check button.active,
        .acao-container .action-check button:hover {
            background: #28A745;
            color: #fff;
        }

        .acao-container .action-imprimir button {
            border-color: #0D6EFD;
            color: #0D6EFD;
        }

        .acao-container .action-imprimir button.active,
        .acao-container .action-imprimir button:hover {
            background: #0D6EFD;
            color: #fff;
        }

        /* Aparncia quando o boto de imprimir estiver bloqueado (produto editado) */
        .acao-container .action-imprimir button[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
            filter: grayscale(20%);
        }

        .acao-container .action-observacao {
            border-color: #FB8C00 !important;
            color: #FB8C00 !important;
        }

        .acao-container .action-observacao.active,
        .acao-container .action-observacao:hover {
            background: #FB8C00;
            color: #fff !important;
        }

        /* Garantir que o cone dentro do boto de observao fique branco quando ativo */
        .acao-container .action-observacao.active i,
        .acao-container .action-observacao:hover i {
            color: #fff !important;
        }

        .acao-container .action-editar {
            border-color: #6F42C1 !important;
            color: #6F42C1 !important;
        }

        .acao-container .action-editar.active,
        .acao-container .action-editar:hover {
            background: #6F42C1;
            color: #fff !important;
        }

        /* Garantir que o cone dentro do boto tambm fique branco quando ativo */
        .acao-container .action-editar.active i,
        .acao-container .action-editar:hover i {
            color: #fff !important;
        }

        .acao-container form,
        .acao-container a {
            margin: 0;
        }

        .edicao-pendente {
            background: #6F42C1;
            color: #fff;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            margin: 3px 0 0.5rem;
            border: 1px solid #6F42C1;
        }

        .observacao-PRODUTO {
            background: #D4AF37;
            color: #fff;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            margin: 3px 0 0.5rem;
            border: 1px solid #D4AF37;
        }

        .info-PRODUTO {
            font-size: 0.9rem;
            color: #555;
        }

        .codigo-PRODUTO {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .acao-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(160px, 1fr));
            gap: 0.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            display: inline-block;
        }

        .legend-checked {
            background-color: #28A745;
        }

        .legend-observacao {
            background-color: #F59E0B;
        }

        .legend-imprimir {
            background-color: #0D6EFD;
        }

        .legend-editado {
            background-color: #8E24AA;
        }

        /* Forcar cores dos botoes de acao e icones quando ativos */
        .acao-container .action-imprimir button {
            border-color: #0D6EFD !important;
            color: #0D6EFD !important;
        }

        .acao-container .action-imprimir button.active,
        .acao-container .action-imprimir button:hover {
            background: linear-gradient(135deg, #0D6EFD 0%, #0A58CA 100%) !important;
            color: #fff !important;
            box-shadow: 0 6px 14px rgba(13, 110, 253, 0.3);
        }

        .acao-container .action-imprimir button.active i,
        .acao-container .action-imprimir button:hover i {
            color: #fff !important;
        }

        .acao-container .action-observacao {
            border-color: #F59E0B !important;
            color: #F59E0B !important;
        }

        .acao-container .action-observacao.active,
        .acao-container .action-observacao:hover {
            background: linear-gradient(135deg, #F59E0B 0%, #F2C94C 100%) !important;
            color: #fff !important;
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.3);
        }

        .acao-container .action-observacao.active i,
        .acao-container .action-observacao:hover i {
            color: #fff !important;
        }

        .acao-container .action-editar {
            border-color: #6F42C1 !important;
            color: #6F42C1 !important;
        }

        .acao-container .action-editar.active,
        .acao-container .action-editar:hover {
            background: linear-gradient(135deg, #6F42C1 0%, #4B2A7D 100%) !important;
            color: #fff !important;
            box-shadow: 0 6px 14px rgba(111, 66, 193, 0.3);
        }

        .acao-container .action-editar.active i,
        .acao-container .action-editar:hover i {
            color: #fff !important;
        }

        .edicao-pendente {
            background: linear-gradient(135deg, #6F42C1 0%, #4B2A7D 100%);
            color: #fff;
            padding: 0.5rem 0.6rem;
            border-radius: 10px;
            margin: 3px 0 0.5rem;
            border: 1px solid #4B2A7D;
            box-shadow: 0 8px 16px rgba(111, 66, 193, 0.25);
        }

        .observacao-PRODUTO {
            background: linear-gradient(135deg, #F59E0B 0%, #F2C94C 100%);
            color: #fff;
            padding: 0.5rem 0.6rem;
            border-radius: 10px;
            margin: 3px 0 0.5rem;
            border: 1px solid #E7B93D;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25);
        }
</style>




<script>
    // Mapeamento de tipos de bens e suas opçÁµes de bem
    const tiposBensOpcoes = <?php echo json_encode(array_reduce($tipos_bens, function ($carry, $item) {

                                $opcoes = [];
                                if (!empty($item['descricao'])) {
                                    $partes = explode('/', $item['descricao']);
                                    $opcoes = array_map('trim', $partes);
                                }
                                $carry[$item['id']] = [
                                    'codigo' => $item['codigo'],
                                    'descricao' => $item['descricao'],
                                    'opcoes' => $opcoes
                                ];
                                return $carry;
                            }, [])); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const selectTipoBEM = document.getElementById('novo_tipo_bem_id');
        const selectBEM = document.getElementById('novo_bem');

        // Função para atualizar opçÁµes de BEM baseado no TIPO DE BEM selecionado
        function atualizarOpcoesBEM() {
            const tipoBEMId = selectTipoBEM.value;

            if (!tipoBEMId) {
                // Desabilitar e limpar
                selectBEM.disabled = true;
                selectBEM.innerHTML = '<option value="">-- ESCOLHA O TIPO DE BEM ACIMA --</option>';
                return;
            }

            const opcoes = tiposBensOpcoes[tipoBEMId]?.opcoes || [];

            if (opcoes.length > 1) {
                // Tem múltiplas opções separadas por /
                selectBEM.disabled = false;
                selectBEM.innerHTML = '<option value="">-- SELECIONE --</option>';
                opcoes.forEach(opcao => {
                    const opt = document.createElement('option');
                    opt.value = opcao.toUpperCase();
                    opt.textContent = opcao.toUpperCase();
                    selectBEM.appendChild(opt);
                });
            } else if (opcoes.length === 1) {
                // Apenas uma opção, preencher automaticamente
                selectBEM.disabled = false;
                selectBEM.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = opcoes[0].toUpperCase();
                opt.textContent = opcoes[0].toUpperCase();
                opt.selected = true;
                selectBEM.appendChild(opt);
            } else {
                // Sem opções, campo livre
                selectBEM.disabled = true;
                selectBEM.innerHTML = '<option value="">-- NÃO APLICÁVEL --</option>';
            }
        }

        // Listener para mudança de TIPO DE BEM
        selectTipoBEM.addEventListener('change', atualizarOpcoesBEM);

        // Inicializar estado
        atualizarOpcoesBEM();

        // Converter inputs para uppercase automaticamente
        document.querySelectorAll('.text-uppercase-input').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });

        // Pré-preencher BEM usando o valor já processado pelo controller (editado ou original)
        const bemPrefill = '<?php echo !empty($novo_bem) ? addslashes(mb_strtoupper($novo_bem, 'UTF-8')) : ''; ?>';
        if (bemPrefill) {
            if (selectTipoBEM.value) {
                atualizarOpcoesBEM();
                for (const opt of selectBEM.options) {
                    if (opt.value === bemPrefill) {
                        opt.selected = true;
                        break;
                    }
                }
            } else {
                selectBEM.innerHTML = '<option value="' + bemPrefill + '" selected>' + bemPrefill + '</option>';
                selectBEM.disabled = true;
            }
        }
    });
</script>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($filtro_STATUS); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <!-- TIPO DE BEM -->
            <div class="mb-3">
                <label for="novo_tipo_bem_id" class="form-label">
                    <i class="bi bi-tag me-1"></i>
                    TIPO DE BEM
                </label>
                <select class="form-select" id="novo_tipo_bem_id" name="novo_tipo_bem_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($tipos_bens as $tb): ?>
                        <option value="<?php echo $tb['id']; ?>"
                            <?php echo (isset($novo_tipo_bem_id) && $novo_tipo_bem_id == $tb['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tb['codigo'] . ' - ' . $tb['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Selecione o tipo de bem para desbloquear o campo "BEM"'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- BEM (sempre visível, desabilitado até escolher tipo) -->
            <div class="mb-3" id="div_bem">
                <label for="novo_bem" class="form-label">
                    <i class="bi bi-box me-1"></i>
                    BEM
                </label>
                <select class="form-select text-uppercase-input" id="novo_bem" name="novo_bem" disabled>
                    <option value="">-- Escolha o TIPO DE BEM acima --</option>
                </select>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Fica bloqueado até selecionar o TIPO DE BEM'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- COMPLEMENTO -->
            <div class="mb-3">
                <label for="novo_complemento" class="form-label">
                    <i class="bi bi-card-text me-1"></i>
                    COMPLEMENTO
                </label>
                <textarea class="form-control text-uppercase-input" id="novo_complemento" name="novo_complemento"
                    rows="3" placeholder="<?php echo htmlspecialchars(to_uppercase('Característica + Marca + Medidas'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($novo_complemento ?? ''); ?></textarea>
                <div class="form-text"><?php echo htmlspecialchars(to_uppercase('Deixe em branco para NÃO alterar. Ex: COR PRETA + MARCA XYZ + 1,80M X 0,80M'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <!-- DEPENDÁŠNCIA -->
            <div class="mb-3">
                <label for="nova_dependencia_id" class="form-label">
                    <i class="bi bi-building me-1"></i>
                    DEPENDÊNCIA
                </label>
                <select class="form-select" id="nova_dependencia_id" name="nova_dependencia_id">
                    <option value=""><?php echo htmlspecialchars(to_uppercase('-- Não alterar --'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>"
                            <?php echo (isset($nova_dependencia_id) && $nova_dependencia_id == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        <?php echo to_uppercase('Salvar alterações'); ?>
    </button>
</form>

<div class="mt-3">
    <a href="/produtos/limpar-edicoes?id=<?php echo $comum_id; ?>&comum_id=<?php echo $comum_id; ?>&id_PRODUTO=<?php echo $id_produto; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_STATUS); ?>"
        class="btn btn-outline-danger w-100">
        <i class="bi bi-trash3 me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Limpar Edições'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <div class="form-text mt-1"><?php echo htmlspecialchars(to_uppercase('Remove todos os campos editados e desmarca para impressão.'), ENT_QUOTES, 'UTF-8'); ?></div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_PRODUTO_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app.php';
unlink($tempFile);
?>
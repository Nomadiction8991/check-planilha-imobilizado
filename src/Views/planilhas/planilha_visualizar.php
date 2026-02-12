<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');


// Usar comum_id passado pelo controller ou da sessão
$comum_id = $comum_id ?? (int)($_SESSION['comum_id'] ?? 0);

if ($comum_id <= 0) {
    header('Location: ' . base_url('/comuns'));
    exit;
}

// Dados serão fornecidos pelo controller ou carregados inline
$PRODUTOS = $produtos ?? [];
$erro_PRODUTOS = $erro_produtos ?? '';
$filtro_STATUS = $filtro_status ?? '';


$id_planilha = $comum_id;
$pageTitle = htmlspecialchars($planilha['comum_descricao'] ?? 'VISUALIZAR Planilha');
$backUrl = base_url('/');


if (false && !empty($acesso_bloqueado)) {
    $mensagemBloqueio = $mensagem_bloqueio ?: 'A planilha precisa ser importada novamente para continuar.';


    $pageTitle = 'Importação Desatualizada';
    $backUrl = base_url('/');

    ob_start();
?>

    <style>
        /* Modal fullscreen customizado (95% largura x 80% altura) */
        .modal-fullscreen-custom {
            width: 95vw;
            height: 80vh;
            max-width: 95vw;
            max-height: 80vh;
            margin: 10vh auto;
        }

        .modal-fullscreen-custom .modal-content {
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-fullscreen-custom .modal-body {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Botão X para fechar */
        .btn-close-scanner {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-close-scanner:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.1);
        }

        .btn-close-scanner i {
            color: #333;
            font-size: 24px;
        }

        /* Overlay com moldura e dica */
        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .scanner-frame {
            width: 80%;
            max-width: 400px;
            height: 200px;
            border: 3px solid rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .scanner-frame::before,
        .scanner-frame::after {
            content: '';
            position: absolute;
            background: #fff;
        }

        .scanner-frame::before {
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            transform: translateY(-50%);
            animation: scan 2s ease-in-out infinite;
        }

        @keyframes scan {

            0%,
            100% {
                opacity: 0;
            }

            50% {
                opacity: 1;
            }
        }

        .scanner-hint {
            color: white;
            background: rgba(0, 0, 0, 0.7);
            padding: 12px 24px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            max-width: 80%;
        }

        .scanner-info {
            color: white;
            background: rgba(0, 0, 0, 0.8);
            padding: 8px 16px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 12px;
            text-align: center;
        }

        /* Controles de c³mera e zoom */
        .scanner-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 90%;
            max-width: 400px;
            pointer-events: auto;
        }

        .scanner-controls select {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }

        .zoom-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 15px;
            border-radius: 8px;
        }

        .zoom-control i {
            color: #333;
            font-size: 18px;
        }

        .zoom-control .form-range {
            flex: 1;
            margin: 0;
        }

        /* Container de vídeo do Quagga */
        #scanner-container video,
        #scanner-container canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        /* Estilos para o botão de microfone */
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

        /* Garantir que botões do input-group não se movam */
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

        /* Estilos padrão para todos os dispositivos (mobile-first) */
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

        /* ===== BOTÕES FLUTUANTES - CANTO INFERIOR DIREITO ===== */
        .floating-buttons-container {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 1040 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
            align-items: center !important;
        }

        .floating-btn {
            width: 60px !important;
            height: 60px !important;
            border-radius: 50% !important;
            border: none !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 24px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
            background: white !important;
            color: #333 !important;
            padding: 0 !important;
            line-height: 1 !important;
        }

        .floating-btn:hover {
            transform: scale(1.15) !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35) !important;
        }

        .floating-btn:active {
            transform: scale(0.95) !important;
        }

        .floating-btn.mic {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }

        .floating-btn.cam {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
            color: white !important;
        }

        .floating-btn.listening {
            animation: pulse-float 1.5s infinite !important;
        }

        @keyframes pulse-float {
            0%, 100% {
                transform: scale(1) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
            }
            50% {
                transform: scale(1.1) !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35) !important;
            }
        }

        /* ===== MODAL DE CÂMERA EM TELA INTEIRA ===== */
        .camera-fullscreen-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: #000 !important;
            z-index: 1050 !important;
            display: none !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .camera-fullscreen-modal.show {
            display: flex !important;
        }

        .camera-fullscreen-content {
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            position: relative !important;
        }

        .camera-scanner-container {
            flex: 1 !important;
            position: relative !important;
            overflow: hidden !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
            background: #000 !important;
        }

        .camera-scanner-container video,
        .camera-scanner-container canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
        }

        .camera-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            pointer-events: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .scanner-frame-fullscreen {
            width: 80% !important;
            max-width: 600px !important;
            aspect-ratio: 1 !important;
            border: 3px solid rgba(255, 255, 255, 0.8) !important;
            border-radius: 20px !important;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5) !important;
            transition: all 0.3s ease !important;
        }

        .scanner-frame-fullscreen.detected {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 9999px rgba(40, 167, 69, 0.3) !important;
        }

        .camera-controls {
            background: rgba(0, 0, 0, 0.8) !important;
            padding: 15px 20px !important;
            display: flex !important;
            gap: 15px !important;
            justify-content: space-between !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            width: 100% !important;
            position: relative !important;
            z-index: 1051 !important;
        }

        .camera-controls label {
            color: white !important;
            margin: 0 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            white-space: nowrap !important;
        }

        .camera-controls select {
            padding: 8px 12px !important;
            border-radius: 6px !important;
            border: none !important;
            background: white !important;
            color: #333 !important;
            font-size: 0.9rem !important;
            cursor: pointer !important;
        }

        .camera-controls input[type="range"] {
            cursor: pointer !important;
            min-width: 100px !important;
        }

        .camera-close-btn {
            position: absolute !important;
            top: 20px !important;
            right: 20px !important;
            width: 50px !important;
            height: 50px !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.9) !important;
            border: none !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 24px !important;
            z-index: 1051 !important;
            transition: all 0.3s ease !important;
            color: #333 !important;
            padding: 0 !important;
            line-height: 1 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        }

        .camera-close-btn:hover {
            background: white !important;
            transform: scale(1.1) !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35) !important;
        }

        .camera-close-btn:active {
            transform: scale(0.95) !important;
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

        /* Aes: usar padrão Bootstrap para botões e manter largura proporcional */
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


            /* Estilos para o botão de microfone */
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

            /* Garantir que botões do input-group não se movam */
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

            /* Estilos padrão para todos os dispositivos (mobile-first) */
            .input-group {
                flex-wrap: nowrap !important;
                display: flex !important;
            }

            .input-group .form-control {
                min-width: 0;
                flex: 1 1 auto !important;
                /* Input preenche o espaço restante */
            }

            .input-group>.btn {
                flex: 0 0 15% !important;
                /* Botões ocupam 15% cada */
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

            /* Aviso de tipo não identificado - amarelo ouro forte */
            .tipo-nao-identificado {
                border-left: 4px solid #fdd835 !important;
            }

            /* Aes: usar padrão Bootstrap para botões e manter largura proporcional */
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

            /* Cores dos botões (paleta coerente com tema) */
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

            /* Aparncia quando o botão de imprimir estiver bloqueado (produto editado) */
            .acao-container .action-imprimir button[disabled] {
                opacity: 0.45;
                cursor: not-allowed;
                filter: grayscale(20%);
            }

            .acao-container .action-observacao {
                border-color: #D4AF37 !important;
                color: #D4AF37 !important;
            }

            .acao-container .action-observacao:hover {
                background: #D4AF37 !important;
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
                border-color: #6F42C1 !important;
                color: #6F42C1 !important;
            }

            .acao-container .action-editar:hover {
                background: #6F42C1 !important;
                color: #fff !important;
            }

            /* Indicadores de estado (para botões com toggle) */
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
                background-color: #FFF8E1;
                border-left: 4px solid #D4AF37;
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

            /* Cores dos botões (paleta coerente com tema) */
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

            /* Aparncia quando o botão de imprimir estiver bloqueado (produto editado) */
            .acao-container .action-imprimir button[disabled] {
                opacity: 0.45;
                cursor: not-allowed;
                filter: grayscale(20%);
            }

            .acao-container .action-observacao {
                border-color: #D4AF37 !important;
                color: #D4AF37 !important;
            }

            .acao-container .action-observacao.active,
            .acao-container .action-observacao:hover {
                background: #D4AF37;
                color: #fff !important;
            }

            /* Garantir que o cone dentro do botão de observao fique branco quando ativo */
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

            .acao-container .action-imprimir button.active i,
            .acao-container .action-imprimir button:hover i {
                color: #fff !important;
            }

            /* Garantir que o cone dentro do botão tambm fique branco quando ativo */
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

            /* Forcar cores dos botoes de acao e icones quando ativos */
            .acao-container .action-imprimir button {
                border-color: #0D6EFD !important;
                color: #0D6EFD !important;
            }

            .acao-container .action-imprimir button.active,
            .acao-container .action-imprimir button:hover {
                background: #0D6EFD !important;
                color: #fff !important;
            }

            .acao-container .action-imprimir button.active i,
            .acao-container .action-imprimir button:hover i {
                color: #fff !important;
            }

            .acao-container .action-observacao {
                border-color: #D4AF37 !important;
                color: #D4AF37 !important;
            }

            .acao-container .action-observacao.active,
            .acao-container .action-observacao:hover {
                background: #D4AF37 !important;
                color: #fff !important;
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
                background: #6F42C1 !important;
                color: #fff !important;
            }

            .acao-container .action-editar.active i,
            .acao-container .action-editar:hover i {
                color: #fff !important;
            }
    </style>

    <!-- Conteúdo vazio - apenas o modal ser exibido -->
    <div class="text-center py-5">
        <div class="text-muted">
            <i class="bi bi-arrow-clockwise fs-1"></i>
            <p class="mt-3">Verificando importação...</p>
        </div>
    </div>

    <!-- Modal importação desatualizada (mesmo estilo do index) -->
    <div class="modal fade" id="importacaoDesatualizadaModal" tabindex="-1" aria-labelledby="importacaoDesatualizadaLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase" id="importacaoDesatualizadaLabel">IMPORTAÇÃO DESATUALIZADA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar" onclick="window.location.href='/'"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3 text-warning">
                        <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                    </div>
                    <p class="text-uppercase"><?php echo htmlspecialchars($mensagemBloqueio, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="modal-footer">
                    <a href="/" class="btn btn-outline-secondary w-47">
                        <i class="bi bi-arrow-left me-1"></i>VOLTAR
                    </a>
                    <a href="/planilhas/importar" class="btn btn-primary w-47">
                        <i class="bi bi-upload me-1"></i>IMPORTAR
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modalEl = document.getElementById('importacaoDesatualizadaModal');
            if (modalEl) {
                var modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            }
        });
    </script>
<?php
    $contentHtml = ob_get_clean();
    $contentFile = __DIR__ . '/../../../temp_bloqueio_' . uniqid() . '.php';
    file_put_contents($contentFile, $contentHtml);
    include_once __DIR__ . '/../layouts/app.php';
    @unlink($contentFile);
    exit;
}


ob_start();
?>

<style>
    /* ===== BOTÕES FLUTUANTES - CANTO INFERIOR DIREITO ===== */
    .floating-buttons-container {
        position: fixed !important;
        bottom: 20px !important;
        right: 20px !important;
        z-index: 1040 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 15px !important;
        align-items: center !important;
    }

    .floating-btn {
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        border: none !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 24px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        background: white !important;
        color: #333 !important;
        padding: 0 !important;
        line-height: 1 !important;
    }

    .floating-btn:hover {
        transform: scale(1.15) !important;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35) !important;
    }

    .floating-btn:active {
        transform: scale(0.95) !important;
    }

    .floating-btn.mic {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
    }

    .floating-btn.cam {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        color: white !important;
    }

    .floating-btn.listening {
        animation: pulse-float 1.5s infinite !important;
    }

    @keyframes pulse-float {
        0%, 100% {
            transform: scale(1) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        }
        50% {
            transform: scale(1.1) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35) !important;
        }
    }

    /* ===== MODAL DE CÂMERA EM TELA INTEIRA ===== */
    .camera-fullscreen-modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: #000 !important;
        z-index: 1050 !important;
        display: none !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .camera-fullscreen-modal.show {
        display: flex !important;
    }

    .camera-fullscreen-content {
        width: 100% !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        position: relative !important;
    }

    .camera-scanner-container {
        flex: 1 !important;
        position: relative !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
        background: #000 !important;
    }

    .camera-scanner-container video,
    .camera-scanner-container canvas {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
    }

    .camera-overlay {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        pointer-events: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .scanner-frame-fullscreen {
        width: 80% !important;
        max-width: 600px !important;
        aspect-ratio: 1 !important;
        border: 3px solid rgba(255, 255, 255, 0.8) !important;
        border-radius: 20px !important;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5) !important;
        transition: all 0.3s ease !important;
    }

    .scanner-frame-fullscreen.detected {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 9999px rgba(40, 167, 69, 0.3) !important;
    }

    .camera-controls {
        background: rgba(0, 0, 0, 0.8) !important;
        padding: 15px 20px !important;
        display: flex !important;
        gap: 15px !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        width: 100% !important;
        position: relative !important;
        z-index: 1051 !important;
    }

    .camera-controls label {
        color: white !important;
        margin: 0 !important;
        font-size: 0.9rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        white-space: nowrap !important;
    }

    .camera-controls select {
        padding: 8px 12px !important;
        border-radius: 6px !important;
        border: none !important;
        background: white !important;
        color: #333 !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
    }

    .camera-controls input[type="range"] {
        cursor: pointer !important;
        min-width: 100px !important;
    }

    .camera-close-btn {
        position: absolute !important;
        top: 20px !important;
        right: 20px !important;
        width: 50px !important;
        height: 50px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border: none !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 24px !important;
        z-index: 1051 !important;
        transition: all 0.3s ease !important;
        color: #333 !important;
        padding: 0 !important;
        line-height: 1 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    }

    .camera-close-btn:hover {
        background: white !important;
        transform: scale(1.1) !important;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35) !important;
    }

    .camera-close-btn:active {
        transform: scale(0.95) !important;
    }

    /* Ajuste de cores dos botões de ação na visualização */
    .acao-container .action-imprimir .btn {
        border-color: #0D6EFD !important;
        color: #0D6EFD !important;
    }

    .acao-container .action-imprimir .btn.active,
    .acao-container .action-imprimir .btn:hover {
        background: linear-gradient(135deg, #0D6EFD 0%, #0A58CA 100%) !important;
        border-color: #0A58CA !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(13, 110, 253, 0.3);
    }

    .acao-container .action-imprimir .btn.active i,
    .acao-container .action-imprimir .btn:hover i {
        color: #fff !important;
    }

    .acao-container .action-observacao {
        border-color: #F59E0B !important;
        color: #F59E0B !important;
    }

    .acao-container .action-observacao.active,
    .acao-container .action-observacao:hover {
        background: linear-gradient(135deg, #F59E0B 0%, #F2C94C 100%) !important;
        border-color: #E7B93D !important;
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
        border-color: #4B2A7D !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(111, 66, 193, 0.3);
    }

    .acao-container .action-editar.active i,
    .acao-container .action-editar:hover i {
        color: #fff !important;
    }

    .edicao-pendente {
        background: linear-gradient(135deg, #6F42C1 0%, #4B2A7D 100%) !important;
        color: #fff !important;
        padding: 0.5rem 0.6rem;
        border-radius: 10px;
        margin: 3px 3px 0.5rem;
        border: 1px solid #4B2A7D;
        box-shadow: 0 8px 16px rgba(111, 66, 193, 0.25);
    }

    .observacao-PRODUTO {
        background: linear-gradient(135deg, #F59E0B 0%, #F2C94C 100%) !important;
        color: #fff !important;
        padding: 0.5rem 0.6rem;
        border-radius: 10px;
        margin: 3px 3px 0.5rem;
        border: 1px solid #E7B93D;
        box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25);
    }
</style>


<!-- Link para Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<?php if (!empty($_GET['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars(to_uppercase($_GET['sucesso']), ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($erro_PRODUTOS)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Erro ao carregar PRODUTOS: <?php echo htmlspecialchars($erro_PRODUTOS); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Filtros'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="id" value="<?php echo $comum_id; ?>">
            <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">

            <div class="mb-3">
                <label class="form-label" for="codigo">
                    <i class="bi bi-upc-scan me-1"></i>
                    <?php echo htmlspecialchars(to_uppercase('Código do Produto'), ENT_QUOTES, 'UTF-8'); ?>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo"
                        value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars(to_uppercase('Digite, fale ou escaneie o código...'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button id="btnMic" class="btn btn-primary mic-btn" type="button" style="display:none;" title="<?php echo htmlspecialchars(to_uppercase('Falar código (Ctrl+M)'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(to_uppercase('Falar código'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="false">
                        <span class="material-icons-round" aria-hidden="true">mic</span>
                    </button>
                    <button id="btnCam" class="btn btn-primary" type="button" style="display:none;" title="<?php echo htmlspecialchars(to_uppercase('Escanear código de barras'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(to_uppercase('Escanear código de barras'), ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            <?php echo htmlspecialchars(to_uppercase('Filtros Avançados'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse collapse" data-bs-parent="#filtrosAvancados">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label" for="nome"><?php echo htmlspecialchars(to_uppercase('Nome'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>"
                                    placeholder="<?php echo htmlspecialchars(to_uppercase('Pesquisar nome...'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="dependencia"><?php echo htmlspecialchars(to_uppercase('Dependncia'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <select class="form-select" id="dependencia" name="dependencia">
                                    <option value=""><?php echo htmlspecialchars(to_uppercase('Todas'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php foreach ($dependencia_options as $dep): ?>
                                        <?php
                                        $depId = $dep['id'] ?? '';
                                        $depDesc = $dep['descricao'] ?? $depId;
                                        ?>
                                        <option value="<?php echo htmlspecialchars($depId); ?>"
                                            <?php echo ($filtro_dependencia ?? '') == $depId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(to_uppercase($depDesc), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="status">STATUS</label>
                                <select class="form-select" id="status" name="status">
                                    <option value=""><?php echo htmlspecialchars(to_uppercase('Todos'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="checado" <?php echo ($filtro_STATUS ?? '') === 'checado' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Checados'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="observacao" <?php echo ($filtro_STATUS ?? '') === 'observacao' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Com observação'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="etiqueta" <?php echo ($filtro_STATUS ?? '') === 'etiqueta' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Etiqueta para Imprimir'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="pendente" <?php echo ($filtro_STATUS ?? '') === 'pendente' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Pendentes'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="editado" <?php echo ($filtro_STATUS ?? '') === 'editado' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Editados'), ENT_QUOTES, 'UTF-8'); ?></option>
                                </select>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">
                <i class="bi bi-search me-2"></i>
                <?php echo htmlspecialchars(to_uppercase('Filtrar'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>
    </div>
    <div class="card-footer text-muted small">
        <?php echo htmlspecialchars(to_uppercase(($total_registros ?? 0) . ' registros encontrados no total'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<!-- BOTÕES FLUTUANTES - CÂMERA E MICROFONE -->
<div class="floating-buttons-container">
    <button id="btnFloatingMic" class="floating-btn mic" type="button" title="Falar código" aria-label="Falar código">
        <i class="bi bi-mic-fill"></i>
    </button>
    <button id="btnFloatingCam" class="floating-btn cam" type="button" title="Escanear código de barras" aria-label="Escanear código de barras">
        <i class="bi bi-camera-video-fill"></i>
    </button>
</div>

<!-- MODAL DE CÂMERA EM TELA INTEIRA -->
<div class="camera-fullscreen-modal" id="cameraFullscreenModal">
    <button class="camera-close-btn" id="cameraCloseBtn" type="button" aria-label="Fechar câmera">
        <i class="bi bi-x-lg"></i>
    </button>
    
    <div class="camera-fullscreen-content">
        <div class="camera-scanner-container" id="cameraFullscreenContainer">
            <div class="camera-overlay">
                <div class="scanner-frame-fullscreen" id="scannerFrameFullscreen"></div>
            </div>
        </div>
        
        <div class="camera-controls">
            <label for="cameraSelectFloating">Câmera:</label>
            <select id="cameraSelectFloating" class="form-select form-select-sm" style="max-width: 200px;">
                <option value="">Câmera padrão</option>
            </select>
            
            <label for="zoomSliderFloating" style="flex: 1; max-width: 200px; margin-left: auto;">
                Zoom: <span id="zoomLevelFloating">1.0x</span>
            </label>
            <input type="range" id="zoomSliderFloating" class="form-range" min="1" max="10" step="0.5" value="1" style="max-width: 150px;">
        </div>
    </div>
</div>

<!-- Listagem de PRODUTOS -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-box-seam me-2"></i>
            PRODUTOS
        </span>
        <span class="badge bg-white text-dark"><?php echo htmlspecialchars(to_uppercase(count($PRODUTOS ?? []) . ' itens'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="list-group list-group-flush">
        <?php if ($PRODUTOS): ?>
            <?php foreach ($PRODUTOS as $p):

                $classe = '';
                $tem_edicao = $p['editado'] == 1;

                if ($p['ativo'] == 0) {
                    $classe = 'linha-dr';
                } elseif (($p['imprimir_etiqueta'] ?? 0) == 1) {
                    $classe = 'linha-imprimir';
                } elseif ($p['checado'] == 1) {
                    $classe = 'linha-checado';
                } elseif (!empty($p['observacao'])) {
                    $classe = 'linha-observacao';
                } elseif ($tem_edicao) {
                    $classe = 'linha-editado';
                } else {
                    $classe = 'linha-pendente';
                }



                if ($p['ativo'] == 0) {

                    $show_check = false;
                    $show_imprimir = true;
                    $show_obs = true;
                    $show_edit = true;
                } else {

                    $show_check = true;
                    $show_imprimir = true;
                    $show_obs = true;
                    $show_edit = true;
                }

                $checkDisabled = !$show_check;
                $imprimirDisabled = !$show_imprimir;
                $obsDisabled = !$show_obs;
                $editDisabled = !$show_edit;

                $tipo_invalido = (!isset($p['tipo_bem_id']) || $p['tipo_bem_id'] == 0 || empty($p['tipo_bem_id']));
            ?>
                <?php
                $produtoId = $p['id_PRODUTO'] ?? $p['id_produto'] ?? $p['ID_PRODUTO'] ?? ($p['ID_PRODUTO'] ?? '');
                $produtoId = intval($produtoId);
                ?>
                <div
                    class="list-group-item <?php echo $classe; ?><?php echo $tipo_invalido ? ' tipo-nao-identificado' : ''; ?>"
                    data-produto-id="<?php echo $produtoId; ?>"
                    data-ativo="<?php echo (int) $p['ativo']; ?>"
                    data-checado="<?php echo (int) $p['checado']; ?>"
                    data-imprimir="<?php echo (int) ($p['imprimir_etiqueta'] ?? 0); ?>"
                    data-observacao="<?php echo htmlspecialchars($p['observacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-editado="<?php echo (int) $p['editado']; ?>"
                    <?php echo $tipo_invalido ? 'title="Tipo de bem não identificado"' : ''; ?>>
                    <!-- Código -->
                    <div class="codigo-PRODUTO">
                        <?php echo htmlspecialchars($p['codigo']); ?>
                    </div>

                    <!-- Edição Pendente -->
                    <?php if ($tem_edicao): ?>
                        <div class="edicao-pendente">
                            <strong><?php echo mb_strtoupper('EDITAR:', 'UTF-8'); ?></strong><br>
                            <?php

                            $desc_editada_visivel = trim($p['editado_descricao_completa'] ?? '');
                            if ($desc_editada_visivel === '') {

                                $tipo_codigo_final = $p['tipo_codigo'];
                                $tipo_desc_final = $p['tipo_desc'];
                                $ben_final = ($p['editado_bem'] !== '' ? $p['editado_bem'] : $p['bem']);
                                $comp_final = ($p['editado_complemento'] !== '' ? $p['editado_complemento'] : $p['complemento']);
                                $dep_final = ($p['editado_dependencia_desc'] ?: $p['dependencia_desc']);

                                $partes = [];
                                if ($tipo_codigo_final && $tipo_desc_final) {
                                    $partes[] = mb_strtoupper($tipo_codigo_final . ' - ' . $tipo_desc_final, 'UTF-8');
                                }
                                if ($ben_final !== '') {
                                    $partes[] = mb_strtoupper($ben_final, 'UTF-8');
                                }
                                if ($comp_final !== '') {

                                    $comp_tmp = mb_strtoupper($comp_final, 'UTF-8');
                                    if ($ben_final !== '' && strpos($comp_tmp, strtoupper($ben_final)) === 0) {
                                        $comp_tmp = trim(substr($comp_tmp, strlen($ben_final)));
                                        $comp_tmp = preg_replace('/^[\s\-\/]+/', '', $comp_tmp);
                                    }
                                    if ($comp_tmp !== '') $partes[] = $comp_tmp;
                                }
                                $desc_editada_visivel = implode(' - ', $partes);
                                if ($dep_final) {
                                    $desc_editada_visivel .= ' (' . mb_strtoupper($dep_final, 'UTF-8') . ')';
                                }
                                if ($desc_editada_visivel === '') {
                                    $desc_editada_visivel = 'EDICAO SEM DESCRICAO';
                                }
                            }
                            echo htmlspecialchars($desc_editada_visivel);
                            ?><br>
                        </div>
                    <?php endif; ?>

                    <!-- Observacao -->
                    <?php if (!empty($p['observacao'])): ?>
                        <div class="observacao-PRODUTO">
                            <strong><?php echo htmlspecialchars(to_uppercase('observação'), ENT_QUOTES, 'UTF-8'); ?>:</strong><br>
                            <?php echo htmlspecialchars(to_uppercase($p['observacao']), ENT_QUOTES, 'UTF-8'); ?><br>
                        </div>
                    <?php endif; ?>

                    <!-- Informações -->
                    <div class="info-PRODUTO">
                        <?php echo htmlspecialchars($p['descricao_completa']); ?><br>
                        <?php if (!empty($p['descricao_velha'])): ?>
                            <small style="font-size: 0.7em; color: #666; opacity: 0.8;">
                                <?php echo htmlspecialchars($p['descricao_velha']); ?>
                            </small><br>
                        <?php endif; ?>
                    </div>

                    <!-- Ações -->
                    <?php
                    $paginaAtual = $pagina ?? 1;
                    $filtroNomeParam = urlencode($filtro_nome ?? '');
                    $filtroDependenciaParam = urlencode($filtro_dependencia ?? '');
                    $filtroCodigoParam = urlencode($filtro_codigo ?? '');
                    $filtroStatusParam = urlencode($filtro_STATUS ?? '');
                    $observacaoUrl = '/produtos/observacao?id_produto=' . $produtoId . '&comum_id=' . $comum_id . '&pagina=' . $paginaAtual . '&nome=' . $filtroNomeParam . '&dependencia=' . $filtroDependenciaParam . '&filtro_codigo=' . $filtroCodigoParam . '&status=' . $filtroStatusParam;
                    $editarUrl = '/produtos/editar?id_produto=' . $produtoId . '&comum_id=' . $comum_id . '&pagina=' . $paginaAtual . '&nome=' . $filtroNomeParam . '&dependencia=' . $filtroDependenciaParam . '&filtro_codigo=' . $filtroCodigoParam . '&status=' . $filtroStatusParam;
                    ?>
                    <div class="acao-container">
                        <!-- Check -->
                        <form method="POST" action="/produtos/check" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="<?php echo $produtoId; ?>">
                            <input type="hidden" name="produto_id" value="<?php echo $produtoId; ?>">
                            <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                            <button type="submit" class="btn btn-outline-success btn-sm <?php echo $p['checado'] == 1 ? 'active' : ''; ?>" title="<?php echo $p['checado'] ? 'Desmarcar checado' : 'Marcar como checado'; ?>" <?php echo $checkDisabled ? 'disabled' : ''; ?>>
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                        </form>

                        <!-- Etiqueta -->
                        <form method="POST" action="/produtos/etiqueta" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="<?php echo $produtoId; ?>">
                            <input type="hidden" name="produto_id" value="<?php echo $produtoId; ?>">
                            <input type="hidden" name="produto_id" value="<?php echo $produtoId; ?>">
                            <input type="hidden" name="imprimir" value="<?php echo ($p['imprimir_etiqueta'] ?? 0) ? '0' : '1'; ?>">
                            <button type="submit" class="btn btn-outline-info btn-sm <?php echo ($p['imprimir_etiqueta'] ?? 0) == 1 ? 'active' : ''; ?>" title="Etiqueta" <?php echo $imprimirDisabled ? 'disabled' : ''; ?>">
                                <i class="bi bi-printer-fill"></i>
                            </button>
                        </form>

                        <!-- Observacao -->
                        <a href="<?php echo $obsDisabled ? '#' : $observacaoUrl; ?>"
                            class="btn btn-outline-warning btn-sm action-observacao <?php echo !empty($p['observacao']) ? 'active' : ''; ?> <?php echo $obsDisabled ? 'disabled' : ''; ?>"
                            data-produto-id="<?php echo $produtoId; ?>"
                            data-comum-id="<?php echo htmlspecialchars($comum_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            title="<?php echo htmlspecialchars(to_uppercase('observação'), ENT_QUOTES, 'UTF-8'); ?>"
                            <?php if ($obsDisabled): ?>tabindex="-1" aria-disabled="true" onclick="event.preventDefault();" <?php endif; ?>>
                            <i class="bi bi-chat-square-text-fill"></i>
                        </a>

                        <!-- EDITAR -->
                        <a href="<?php echo $editDisabled ? '#' : $editarUrl; ?>"
                            class="btn btn-outline-primary btn-sm action-editar <?php echo $tem_edicao ? 'active' : ''; ?> <?php echo $editDisabled ? 'disabled' : ''; ?>"
                            title="EDITAR"
                            <?php if ($editDisabled): ?>tabindex="-1" aria-disabled="true" onclick="event.preventDefault();" <?php endif; ?>>
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="list-group-item text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                <span class="text-muted">Nenhum PRODUTO encontrado</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Paginação -->
<?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <nav aria-label="Navegao de página" class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $pagina - 1])); ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina - 2);
            $fim = min($total_paginas, $pagina + 2);
            for ($i = $inicio; $i <= $fim; $i++):
            ?>
                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($pagina < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $pagina + 1])); ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<script>
    // ======== AÇÕES AJAX (check/etiqueta) ========
    document.addEventListener('DOMContentLoaded', () => {
        // Fechar qualquer modal aberto que possa estar bloqueando a interação
        document.querySelectorAll('.modal.show').forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            } else {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        });
        // Remover backdrops persistentes
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.remove();
        });
        // Remover qualquer overlay que possa estar bloqueando
        document.querySelectorAll('.scanner-overlay').forEach(overlay => {
            overlay.remove();
        });

        const alertHost = document.createElement('div');
        alertHost.id = 'ajaxAlerts';
        alertHost.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
        alertHost.style.zIndex = '1100';
        document.body.appendChild(alertHost);

        const showAlert = (type, message) => {
            const wrapper = document.createElement('div');
            wrapper.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
            wrapper.role = 'alert';
            wrapper.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="FECHAR"></button>
        `;
            alertHost.appendChild(wrapper);
            setTimeout(() => {
                wrapper.classList.remove('show');
                wrapper.addEventListener('transitionend', () => wrapper.remove(), {
                    once: true
                });
            }, 3000);
        };

        const linhaClasses = ['linha-dr', 'linha-imprimir', 'linha-checado', 'linha-observacao', 'linha-editado', 'linha-pendente'];
        const computeRowClass = (state) => {
            if (state.ativo === 0) return 'linha-dr';
            if (state.imprimir === 1) return 'linha-imprimir';
            if (state.checado === 1) return 'linha-checado';
            if ((state.observacao || '').trim() !== '') return 'linha-observacao';
            if (state.editado === 1) return 'linha-editado';
            return 'linha-pendente';
        };

        const getRowState = (row) => ({
            ativo: Number(row.dataset.ativo || 0),
            checado: Number(row.dataset.checado || 0),
            imprimir: Number(row.dataset.imprimir || 0),
            observacao: row.dataset.observacao || '',
            editado: Number(row.dataset.editado || 0)
        });

        const updateActionButtons = (row, state) => {
            // Todos os botes funcionam de forma INDEPENDENTE
            // Apenas bloquear quando produto estiver em DR (ativo=0)
            const active = state.ativo === 1;

            const checkActive = state.checado === 1;
            const checkDisabled = !active; // S bloqueia se DR

            const imprimirActive = state.imprimir === 1;
            const imprimirDisabled = !active; // S bloqueia se DR

            // Check
            row.querySelectorAll('.action-check').forEach(el => {
                el.style.display = 'inline-block';
                const btn = el.querySelector('button');
                const checkForm = row.querySelector('.PRODUTO-action-form.action-check');
                const checkInput = checkForm ? checkForm.querySelector('input[name="checado"]') : null;
                if (btn) {
                    btn.disabled = checkDisabled;
                    btn.classList.toggle('active', checkActive);
                    if (checkDisabled) {
                        btn.setAttribute('aria-disabled', 'true');
                    } else {
                        btn.removeAttribute('aria-disabled');
                    }
                    btn.title = checkActive ? 'Desmarcar checado' : 'Marcar como checado';
                }
                if (checkInput) {
                    checkInput.value = checkActive ? '0' : '1';
                }
            });

            // Imprimir
            row.querySelectorAll('.action-imprimir').forEach(el => {
                el.style.display = 'inline-block';
                const btn = el.querySelector('button');
                const imprimirFormEl = row.querySelector('.PRODUTO-action-form.action-imprimir');
                const imprimirInput = imprimirFormEl ? imprimirFormEl.querySelector('input[name="imprimir"]') : null;
                if (btn) {
                    btn.disabled = imprimirDisabled;
                    btn.classList.toggle('active', imprimirActive);
                    btn.classList.remove('disabled-visually');
                    if (imprimirDisabled) {
                        btn.setAttribute('aria-disabled', 'true');
                    } else {
                        btn.removeAttribute('aria-disabled');
                    }
                    btn.title = imprimirActive ? 'Remover etiqueta' : 'Marcar para etiqueta';
                }
                if (imprimirInput) {
                    imprimirInput.value = imprimirActive ? '0' : '1';
                }
            });

            // Observação - sempre disponvel
            row.querySelectorAll('.btn-outline-warning').forEach(el => {
                el.style.display = 'inline-block';
                el.classList.remove('disabled');
                el.removeAttribute('aria-disabled');
            });

            // Editar - sempre disponvel
            row.querySelectorAll('.btn-outline-primary').forEach(el => {
                el.style.display = 'inline-block';
                el.classList.remove('disabled-visually');
                el.removeAttribute('aria-disabled');
            });
        };

        const applyState = (row, updates = {}) => {
            const state = {
                ...getRowState(row),
                ...updates
            };
            // NO forar nenhum estado - cada botão  independente
            row.dataset.ativo = state.ativo;
            row.dataset.checado = state.checado;
            row.dataset.imprimir = state.imprimir;
            row.dataset.observacao = state.observacao ?? '';
            row.dataset.editado = state.editado ?? row.dataset.editado;

            linhaClasses.forEach(c => row.classList.remove(c));
            row.classList.add(computeRowClass(state));
            updateActionButtons(row, state);
        };

        document.querySelectorAll('.list-group-item[data-produto-id]').forEach(row => {
            updateActionButtons(row, getRowState(row));
        });


        // Clique em EDITAR: no marcar como checado automaticamente  permitir que a edição seja feita e s marcar ao salvar
        document.addEventListener('click', function(ev) {
            const a = ev.target.closest && ev.target.closest('.action-editar');
            if (!a) return;
            // Se estiver visualmente desabilitado, ignorar
            if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') return;
            // Permitir comportamento padrão (navegação para a página de edição)
            // A marcao como 'checado' ser tratada ao salvar as alteraes no servidor (ProdutoUpdateController)
        });

        // Observer removido - cada botão funciona de forma independente
        document.querySelectorAll('.PRODUTO-action-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const action = form.dataset.action;
                const PRODUTOId = form.dataset.produtoId;
                const confirmMsg = form.dataset.confirm;
                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                const formData = new FormData(form);

                // Sincronizar o valor dos inputs escondidos antes do submit (redundante, mas garante consistncia)
                if (action === 'imprimir') {
                    const imprimirInput = form.querySelector('input[name="imprimir"]');
                    if (imprimirInput) {
                        // garantir valor coerente (j  atualizado em outros pontos do script)
                        imprimirInput.value = imprimirInput.value;
                    }
                }
                if (action === 'check') {
                    const checkInput = form.querySelector('input[name="checado"]');
                    if (checkInput) checkInput.value = checkInput.value;
                }

                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        let data = {};
                        try {
                            data = await response.json();
                        } catch (e) {
                            // resposta n£o era JSON
                        }
                        if (!response.ok || data.success === false) {
                            throw new Error(data.message || 'NO FOI POSSVEL ATUALIZAR.');
                        }
                        return data;
                    })
                    .then(data => {

                        const row = document.querySelector(`.list-group-item[data-produto-id="${PRODUTOId}"]`);
                        const stateUpdates = {};

                        if (action === 'check') {
                            const newVal = Number(formData.get('checado') || 0);
                            stateUpdates.checado = newVal;
                            const input = form.querySelector('input[name=\"checado\"]');
                            if (input) {
                                input.value = newVal ? '0' : '1';
                            }
                            const btn = form.querySelector('button');
                            if (btn) {
                                btn.classList.toggle('active', newVal === 1);
                            }
                        } else if (action === 'imprimir') {
                            const newVal = Number(formData.get('imprimir') || 0);
                            stateUpdates.imprimir = newVal;
                            const input = form.querySelector('input[name="imprimir"]');
                            if (input) {
                                input.value = newVal ? '0' : '1';
                            }
                            const btn = form.querySelector('button');
                            if (btn) {
                                btn.classList.toggle('active', newVal === 1);
                            }
                        }

                        if (row) {
                            applyState(row, stateUpdates);
                        }

                        showAlert('success', (data.message || 'STATUS ATUALIZADO COM SUCESSO').toUpperCase());
                    })
                    .catch(err => {
                        showAlert('danger', (err.message || 'ERRO AO PROCESSAR AO').toUpperCase());
                    });
            });
        });

        // Observação via modal + AJAX
        (function setupObservacao() {
            const modalEl = document.getElementById('observacaoModal');
            if (!modalEl) return;
            const obsModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
            const ta = modalEl.querySelector('#observacaoText');
            const saveBtn = modalEl.querySelector('#observacaoSaveBtn');
            let current = null; // {row, prodId, comumId, anchor}

            function openModalFor(anchor) {
                if (!anchor) return;
                if (anchor.classList.contains('disabled') || anchor.getAttribute('aria-disabled') === 'true') return;
                const prodId = anchor.dataset.produtoId || anchor.closest('.list-group-item')?.dataset.produtoId;
                const comumId = anchor.dataset.comumId || <?php echo json_encode($comum_id ?? ''); ?>;
                const row = document.querySelector(`.list-group-item[data-produto-id="${prodId}"]`);
                const curObs = row ? (row.dataset.observacao || '') : '';
                ta.value = curObs;
                current = {
                    row,
                    prodId,
                    comumId,
                    anchor
                };
                obsModal.show();
                ta.focus();
            }

            // Restaurar comportamento original: clique em Observação navega para a página de observação (no abrir modal).
            // Se o link estiver desabilitado, impedir a navegação.
            document.querySelectorAll('.action-observacao').forEach(a => {
                a.addEventListener('click', function(ev) {
                    if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') {
                        ev.preventDefault();
                        return;
                    }
                    // Permitir comportamento padrão: navegador seguir o href para a página de observação.
                });
            });

            saveBtn.addEventListener('click', function() {
                if (!current) return;
                saveBtn.disabled = true;
                const formData = new FormData();
                formData.set('id_produto', current.prodId);
                formData.set('comum_id', current.comumId);
                formData.set('observacoes', ta.value.trim()); // controller expects 'observacoes'

                fetch('/produtos/observacao', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).then(async resp => {
                    let data = {};
                    try {
                        data = await resp.json();
                    } catch (e) {}
                    if (!resp.ok || data.success === false) throw new Error(data.message || 'Falha ao salvar observação');
                    // Atualizar UI
                    const newObs = ta.value.trim();
                    if (current.row) {
                        applyState(current.row, {
                            observacao: newObs
                        });
                    }
                    current.anchor.classList.toggle('active', newObs !== '');
                    showAlert('success', data.message || 'Observação atualizada');
                    obsModal.hide();
                }).catch(err => {
                    showAlert('danger', (err.message || 'Erro ao salvar observação').toUpperCase());
                }).finally(() => {
                    saveBtn.disabled = false;
                });
            });
        })();
    });

    // ======== RECONHECIMENTO DE VOZ ========
    (() => {
        const POSSIVEIS_IDS_INPUT = ["cod", "codigo", "code", "productCode", "busca", "search", "q"];

        function encontraInputCodigo() {
            for (const id of POSSIVEIS_IDS_INPUT) {
                const el = document.getElementById(id);
                if (el) return el;
            }
            for (const name of ["cod", "codigo", "code", "productCode", "q", "busca", "search"]) {
                const el = document.querySelector(`input[name="${name}"]`);
                if (el) return el;
            }
            const el = document.querySelector('input[placeholder*="código" i],input[placeholder*="codigo" i]');
            return el || null;
        }

        function encontraBotaoPesquisar(input) {
            if (input && input.form) {
                const b = input.form.querySelector('button[type="submit"],input[type="submit"]');
                if (b) return b;
            }
            return document.querySelector('button[type="submit"],input[type="submit"]');
        }

        let micBtn = document.getElementById('btnMic');
        if (!micBtn) return;

        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) {
            micBtn.setAttribute('aria-disabled', 'true');
            micBtn.title = 'Reconhecimento de voz não suportado neste navegador';
            const iconNF = micBtn.querySelector('.material-icons-round');
            if (iconNF) {
                iconNF.textContent = 'mic_off';
            }
            micBtn.addEventListener('click', () => {
                alert('Reconhecimento de voz não suportado neste navegador. Use o botão de câmera ou digite o código.');
            });
            return;
        }

        const DIGITOS = {
            "zero": "0",
            "um": "1",
            "uma": "1",
            "dois": "2",
            "duas": "2",
            "três": "3",
            "tres": "3",
            "quatro": "4",
            "cinco": "5",
            "seis": "6",
            "meia": "6",
            "sete": "7",
            "oito": "8",
            "nove": "9"
        };
        const SINAIS = {
            "tracinho": "-",
            "hífen": "-",
            "hifen": "-",
            "menos": "-",
            "barra": "/",
            "barra invertida": "\\",
            "contrabarra": "\\",
            "invertida": "\\",
            "ponto": ".",
            "vírgula": ",",
            "virgula": ",",
            "espaço": " "
        };

        function extraiCodigoFalado(trans) {
            let direto = trans.replace(/[^\d\-./,\\ ]+/g, '').trim();
            direto = direto.replace(/\s+/g, '');
            if (/\d/.test(direto)) return direto;

            const out = [];
            for (const raw of trans.toLowerCase().split(/\s+/)) {
                const w = raw.normalize('NFD').replace(/\p{Diacritic}/gu, '');
                if (DIGITOS[w]) out.push(DIGITOS[w]);
                else if (SINAIS[w]) out.push(SINAIS[w]);
                else if (/^\d+$/.test(w)) out.push(w);
            }
            return out.join('');
        }

        async function preencherEEnviar(codigo) {
            const input = encontraInputCodigo();
            if (!input) {
                alert('Campo de código não encontrado.');
                return;
            }
            input.focus();
            input.value = codigo;
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));

            const btn = encontraBotaoPesquisar(input);
            if (btn) {
                btn.click();
                return;
            }
            if (input.form) {
                input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
                return;
            }
            const ev = new KeyboardEvent('keydown', {
                key: 'Enter',
                code: 'Enter',
                bubbles: true
            });
            input.dispatchEvent(ev);
        }

        const rec = new SR();
        rec.lang = 'pt-BR';
        rec.continuous = false;
        rec.interimResults = false;
        rec.maxAlternatives = 3;

        function setMicIcon(listening) {
            const icon = micBtn.querySelector('.material-icons-round');
            if (icon) {
                icon.textContent = listening ? 'graphic_eq' : 'mic';
            }
        }

        function startListening() {
            try {
                rec.start();
                micBtn.classList.add('listening');
                micBtn.setAttribute('aria-pressed', 'true');
                setMicIcon(true);
            } catch (e) {}
        }

        function stopListening() {
            try {
                rec.stop();
            } catch (e) {}
            micBtn.classList.remove('listening');
            micBtn.setAttribute('aria-pressed', 'false');
            setMicIcon(false);
        }

        rec.onresult = (e) => {
            const best = e.results[0][0].transcript || '';
            const codigo = extraiCodigoFalado(best);
            stopListening();
            if (!codigo) {
                alert('No entendi o código. Tente soletrar: "um dois trs"');
                return;
            }
            preencherEEnviar(codigo);
        };

        rec.onerror = (e) => {
            stopListening();
            if (e.error === 'not-allowed') alert('Permita o acesso ao microfone para usar a busca por voz.');
        };

        rec.onend = () => micBtn.classList.remove('listening');

        micBtn.addEventListener('click', () => {
            if (micBtn.classList.contains('listening')) stopListening();
            else startListening();
        });

        document.addEventListener('keydown', (ev) => {
            if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'm') {
                ev.preventDefault();
                micBtn.click();
            }
        });
    })();
</script>

<!-- Modal para escanear código de barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <div id="scanner-container" style="width:100%; height:100%; background:#000; position:relative; overflow:hidden;"></div>

                <!-- Botão X para fechar -->
                <button type="button" class="btn-close-scanner" aria-label="FECHAR scanner">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Controles de câmera e zoom -->
                <div class="scanner-controls">
                    <select id="cameraSelect" class="form-select form-select-sm">
                        <option value="">Carregando câmeras...</option>
                    </select>
                    <div class="zoom-control">
                        <i class="bi bi-zoom-out"></i>
                        <input type="range" id="zoomSlider" min="1" max="3" step="0.1" value="1" class="form-range">
                        <i class="bi bi-zoom-in"></i>
                    </div>
                </div>

                <!-- Overlay com moldura e dica -->
                <div class="scanner-overlay">
                    <div class="scanner-frame"></div>
                    <div class="scanner-hint">Posicione o código de barras dentro da moldura</div>
                    <div class="scanner-info" id="scannerInfo">Inicializando câmera...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Observação (edição rpida via AJAX) -->
<div class="modal fade" id="observacaoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Observação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="observacaoText" class="form-label">Observação</label>
                    <textarea id="observacaoText" class="form-control" rows="4" placeholder="Digite a observação..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="observacaoSaveBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>



<!-- Quagga2 para leitura de códigos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
    // Aguardar TUDO carregar (DOM + Bootstrap + Quagga)
    document.addEventListener('DOMContentLoaded', function() {
        // Aguardar mais um pouco para garantir que Bootstrap está pronto
        setTimeout(initFloatingButtons, 100);
        setTimeout(initBarcodeScanner, 500);
    });

    // ===== INICIALIZAÇÃO DOS BOTÕES FLUTUANTES =====
    function initFloatingButtons() {
        console.log('===== INICIALIZANDO BOTÕES FLUTUANTES =====');
        
        const btnFloatingMic = document.getElementById('btnFloatingMic');
        const btnFloatingCam = document.getElementById('btnFloatingCam');
        const btnMicOriginal = document.getElementById('btnMic');
        const btnCamOriginal = document.getElementById('btnCam');
        const cameraFullscreenModal = document.getElementById('cameraFullscreenModal');
        const cameraCloseBtn = document.getElementById('cameraCloseBtn');
        
        if (!btnFloatingMic || !btnFloatingCam) {
            console.error('Botões flutuantes não encontrados!');
            return;
        }
        
        // Conectar botão flutuante de microfone ao botão original
        if (btnMicOriginal) {
            btnFloatingMic.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Clicado botão flutuante de microfone');
                btnMicOriginal.click();
            });
            console.log('✓ Botão flutuante de microfone conectado');
        }
        
        // Conectar botão flutuante de câmera para abrir modal fullscreen
        if (btnFloatingCam && cameraFullscreenModal) {
            btnFloatingCam.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Clicado botão flutuante de câmera');
                cameraFullscreenModal.classList.add('show');
                
                // Aguardar ser visível antes de iniciar câmera
                setTimeout(initFullscreenCamera, 300);
            });
            console.log('✓ Botão flutuante de câmera conectado');
        }
        
        // Botão de fechar da modal fullscreen
        if (cameraCloseBtn && cameraFullscreenModal) {
            cameraCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Clicado botão de fechar câmera');
                stopFullscreenCamera();
                cameraFullscreenModal.classList.remove('show');
            });
        }
        
        // Fechar ao clicar fora (backdrop)
        cameraFullscreenModal.addEventListener('click', function(e) {
            if (e.target === cameraFullscreenModal) {
                stopFullscreenCamera();
                cameraFullscreenModal.classList.remove('show');
            }
        });
        
        // ESC para fechar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && cameraFullscreenModal.classList.contains('show')) {
                stopFullscreenCamera();
                cameraFullscreenModal.classList.remove('show');
            }
        });
        
        console.log('✓ Botões flutuantes inicializados com sucesso!');
    }

    // ===== VARIÁVEIS GLOBAIS DA CÂMERA FULLSCREEN =====
    let fullscreenScanning = false;
    let fullscreenCurrentStream = null;
    let fullscreenCurrentTrack = null;
    let fullscreenSelectedDeviceId = '';
    let fullscreenAvailableCameras = [];
    let fullscreenLastCode = '';

    async function initFullscreenCamera() {
        console.log('Inicializando câmera fullscreen...');
        
        await enumerateFullscreenCameras();
        startFullscreenScanner();
    }

    async function enumerateFullscreenCameras() {
        const cameraSelectFloating = document.getElementById('cameraSelectFloating');
        if (!cameraSelectFloating) return;
        
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            fullscreenAvailableCameras = devices.filter(device => device.kind === 'videoinput');
            
            console.log(`√ ${fullscreenAvailableCameras.length} câmera(s) encontrada(s)`);
            
            cameraSelectFloating.innerHTML = '<option value="">Câmera padrão</option>';
            fullscreenAvailableCameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Câmera ${index + 1}`;
                cameraSelectFloating.appendChild(option);
            });
        } catch (err) {
            console.error('Erro ao enumerar câmeras:', err);
        }
    }

    function startFullscreenScanner() {
        if (fullscreenScanning) return;
        fullscreenScanning = true;
        fullscreenLastCode = '';
        
        const container = document.getElementById('cameraFullscreenContainer');
        if (!container) return;
        
        const constraints = {
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        };
        
        if (fullscreenSelectedDeviceId) {
            constraints.deviceId = { exact: fullscreenSelectedDeviceId };
        } else {
            constraints.facingMode = 'environment';
        }
        
        Quagga.init({
            inputStream: {
                type: 'LiveStream',
                target: container,
                constraints: constraints
            },
            decoder: {
                readers: [
                    'ean_reader',
                    'code_128_reader',
                    'ean_8_reader',
                    'upc_reader',
                    'upc_e_reader'
                ],
                multiple: false
            },
            locate: true,
            locator: { patchSize: 'large', halfSample: true },
            frequency: 10,
            numOfWorkers: navigator.hardwareConcurrency || 4
        }, function(err) {
            if (err) {
                console.error('Erro ao iniciar câmera:', err);
                alert('Não foi possível acessar a câmera:\n\n' + err.message);
                fullscreenScanning = false;
                return;
            }
            
            console.log('√ Câmera fullscreen iniciada!');
            Quagga.start();
            
            // Capturar stream
            const videoElement = container.querySelector('video');
            if (videoElement && videoElement.srcObject) {
                fullscreenCurrentStream = videoElement.srcObject;
                const videoTracks = fullscreenCurrentStream.getVideoTracks();
                if (videoTracks.length > 0) {
                    fullscreenCurrentTrack = videoTracks[0];
                }
            }
        });
        
        Quagga.offDetected();
        Quagga.onDetected(function(result) {
            if (!result || !result.codeResult || !result.codeResult.code) return;
            
            const rawCode = result.codeResult.code.trim();
            if (!rawCode || rawCode === fullscreenLastCode) return;
            
            fullscreenLastCode = rawCode;
            const code = normalizeBarcodeFullscreen(rawCode);
            
            console.log('√ Código detectado:', code);
            
            // Feedback visual
            const frame = document.getElementById('scannerFrameFullscreen');
            if (frame) {
                frame.classList.add('detected');
                setTimeout(() => frame.classList.remove('detected'), 300);
            }
            
            stopFullscreenScanner();
            const cameraModal = document.getElementById('cameraFullscreenModal');
            if (cameraModal) cameraModal.classList.remove('show');
            
            // Preencher o input original e enviar
            const codigoInput = document.getElementById('codigo');
            if (codigoInput) {
                codigoInput.value = code;
                codigoInput.dispatchEvent(new Event('input', { bubbles: true }));
                codigoInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                const form = codigoInput.form || document.querySelector('form');
                if (form) {
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            }
        });
    }

    function stopFullscreenScanner() {
        console.log('Parando câmera fullscreen...');
        try {
            Quagga.stop();
            if (fullscreenCurrentStream) {
                fullscreenCurrentStream.getTracks().forEach(track => track.stop());
                fullscreenCurrentStream = null;
            }
            fullscreenCurrentTrack = null;
            
            const container = document.getElementById('cameraFullscreenContainer');
            if (container) {
                while (container.firstChild) {
                    container.removeChild(container.firstChild);
                }
            }
        } catch (e) {
            console.error('Erro ao parar câmera:', e);
        }
        fullscreenScanning = false;
    }

    function normalizeBarcodeFullscreen(code) {
        return code.trim().replace(/\s+/g, '');
    }

    // Eventos de câmera
    document.getElementById('cameraSelectFloating')?.addEventListener('change', function(e) {
        fullscreenSelectedDeviceId = e.target.value;
        if (fullscreenScanning) {
            stopFullscreenScanner();
            setTimeout(() => startFullscreenScanner(), 300);
        }
    });

    document.getElementById('zoomSliderFloating')?.addEventListener('input', function(e) {
        const zoomLevel = parseFloat(e.target.value);
        document.getElementById('zoomLevelFloating').textContent = zoomLevel.toFixed(1) + 'x';
        if (fullscreenCurrentTrack && fullscreenCurrentTrack.getCapabilities().zoom) {
            fullscreenCurrentTrack.applyConstraints({ advanced: [{ zoom: zoomLevel }] });
        }
    });

    function stopFullscreenCamera() {
        stopFullscreenScanner();
    }

    // ===== FUNÇÃO DUMMY PARA COMPATIBILIDADE =====
    function initBarcodeScanner() {
        console.log('initBarcodeScanner() chamada - usando câmera fullscreen');
        // Compatibilidade com código antigo - não fazer nada
        // Os botões flutuantes já controlam tudo
    }
</script>

<!-- Modal para escanear código de barras (ORIGINAL - ESCONDIDA) -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display:none;">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
        </div>
    </div>
</div>

<?php

$contentHtml = ob_get_clean();


$tempFile = __DIR__ . '/../../../temp_view_planilha_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;


include __DIR__ . '/../layouts/app.php';


unlink($tempFile);
?>
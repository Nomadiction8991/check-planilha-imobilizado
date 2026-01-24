
<style>
    /* Estilos para o botÃ¢â€Å“ÃƒÂºo de microfone */
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

    /* Garantir que botÃ¢â€Å“ÃƒÂes do input-group nÃ¢â€Å“ÃƒÂºo se movam */
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

    /* Estilos padrÃ¢â€Å“ÃƒÂºo para todos os dispositivos (mobile-first) */
    .input-group {
        flex-wrap: nowrap !important;
        display: flex !important;
    }

    .input-group .form-control {
        min-width: 0;
        flex: 1 1 auto !important;
        /* Input preenche o espaÃ¢â€Å“Ã‚Âºo restante */
    }

    .input-group>.btn {
        flex: 0 0 15% !important;
        /* BotÃ¢â€Å“ÃƒÂes ocupam 15% cada */
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

    /* Aviso de tipo nÃ¢â€Å“ÃƒÂºo identificado - amarelo ouro forte */
    .tipo-nao-identificado {
        border-left: 4px solid #fdd835 !important;
    }

    /* Ações: usar padrão Bootstrap para botões e manter largura proporcional */
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

    .acao-container .btn[disabled],
    .acao-container .btn.disabled {
        pointer-events: none;
        opacity: 0.55;
    }

    /* Botão visualmente desabilitado (mas clicável quando necessário, ex: imprimir que autocheca) */
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

    /* Aparência quando o botão de imprimir estiver bloqueado (produto editado) */
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

    /* Garantir que o ícone dentro do botão de observação fique branco quando ativo */
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

    /* Garantir que o ícone dentro do botão também fique branco quando ativo */
    .acao-container .action-editar.active i,
    .acao-container .action-editar:hover i {
        color: #fff !important;
    }

    .acao-container form,
    .acao-container a {
        margin: 0;
    }

    .edicao-pendente {
        background: #f3e5f5;
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid #9c27b0;
    }

    .observacao-PRODUTO {
        background: #fff3e0;
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid #ff9800;
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
        background-color: #FB8C00;
    }

    .legend-imprimir {
        background-color: #0D6EFD;
    }

    .legend-editado {
        background-color: #8E24AA;
    }
</style>

<!-- Link para Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">


<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        FILTROS    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="id" value="7">
            <input type="hidden" name="comum_id" value="7">

            <div class="mb-3">
                <label class="form-label" for="codigo">
                    <i class="bi bi-upc-scan me-1"></i>
                    CÓDIGO DO PRODUTO                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo"
                        value=""
                        placeholder="DIGITE, FALE OU ESCANEIE O CÓDIGO...">
                    <button id="btnMic" class="btn btn-primary mic-btn" type="button" title="FALAR CÓDIGO (CTRL+M)" aria-label="FALAR CÓDIGO" aria-pressed="false">
                        <span class="material-icons-round" aria-hidden="true">mic</span>
                    </button>
                    <button id="btnCam" class="btn btn-primary" type="button" title="ESCANEAR CÓDIGO DE BARRAS" aria-label="ESCANEAR CÓDIGO DE BARRAS">
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            FILTROS AVANÇADOS                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse collapse" data-bs-parent="#filtrosAvancados">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label" for="nome">NOME</label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value=""
                                    placeholder="PESQUISAR NOME...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="dependencia">DEPENDÊNCIA</label>
                                <select class="form-select" id="dependencia" name="dependencia">
                                    <option value="">TODAS</option>
                                                                                                                    <option value="7"
                                            >
                                            ALMOXARIFADO                                        </option>
                                                                                                                    <option value="9"
                                            >
                                            ATRIO DIREITO                                        </option>
                                                                                                                    <option value="4"
                                            >
                                            ATRIO ESQUERDO                                        </option>
                                                                                                                    <option value="10"
                                            >
                                            ESPACO INFANTIL                                        </option>
                                                                                                                    <option value="17"
                                            >
                                            ESTACIONAMENTO                                        </option>
                                                                                                                    <option value="2"
                                            >
                                            SALAO DE CULTO                                        </option>
                                                                                                                    <option value="8"
                                            >
                                            SANITARIO FEMININO                                        </option>
                                                                                                                    <option value="6"
                                            >
                                            SANITARIO MASCULINO                                        </option>
                                                                                                                    <option value="12"
                                            >
                                            SECRETARIA                                        </option>
                                                                                                                    <option value="1"
                                            >
                                            TEMPLO                                        </option>
                                                                    </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="status">STATUS</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">TODOS</option>
                                    <option value="checado" >CHECADOS</option>
                                    <option value="observacao" >COM OBSERVACAO</option>
                                    <option value="etiqueta" >ETIQUETA PARA IMPRIMIR</option>
                                    <option value="pendente" >PENDENTES</option>
                                    <option value="editado" >EDITADOS</option>
                                </select>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">
                <i class="bi bi-search me-2"></i>
                FILTRAR            </button>
        </form>
    </div>
    <div class="card-footer text-muted small">
        220 REGISTROS ENCONTRADOS NO TOTAL    </div>
</div>

<!-- Legenda -->
<div class="card mb-3">
    <div class="card-body p-3">
        <div class="legend-grid">
            <div class="legend-item">
                <span class="legend-color legend-checked"></span>
                CHECADO            </div>
            <div class="legend-item">
                <span class="legend-color legend-observacao"></span>
                OBSERVAÇÃO            </div>
            <div class="legend-item">
                <span class="legend-color legend-imprimir"></span>
                IMPRIMIR ETIQUETA            </div>
            <div class="legend-item">
                <span class="legend-color legend-editado"></span>
                EDITADO            </div>
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
        <span class="badge bg-white text-dark">20 ITENS</span>
    </div>
    <div class="list-group list-group-flush">
                                                    <div
                    class="list-group-item linha-pendente"
                    data-produto-id="16555"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000123                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [1 - BANCO DE MADEIRA/GENUFLEXORIO] GENUFLEXÓRIO - BANCOS 2,10 M (ÁTRIO DIREITO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="16555">
                                <input type="hidden" name="produto_id" value="16555">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="16555">
                                <input type="hidden" name="produto_id" value="16555">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=16555&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="16555"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=16555&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="16554"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000115                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [1 - BANCO DE MADEIRA/GENUFLEXORIO] GENUFLEXÓRIO - 2,10 M (ÁTRIO ESQUERDO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="16554">
                                <input type="hidden" name="produto_id" value="16554">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="16554">
                                <input type="hidden" name="produto_id" value="16554">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=16554&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="16554"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=16554&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="16553"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000114                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [1 - BANCO DE MADEIRA/GENUFLEXORIO] GENUFLEXÓRIO - 2,10 M (ÁTRIO ESQUERDO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="16553">
                                <input type="hidden" name="produto_id" value="16553">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="16553">
                                <input type="hidden" name="produto_id" value="16553">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=16553&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="16553"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=16553&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="16552"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000008                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [56 - EXTINTOR] EXTINTOR - DE PO QUIMICO 10 KG (ÁTRIO DIREITO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="16552">
                                <input type="hidden" name="produto_id" value="16552">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="16552">
                                <input type="hidden" name="produto_id" value="16552">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=16552&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="16552"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=16552&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="16551"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000007                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [56 - EXTINTOR] EXTINTOR - DE AGUA 10 L (ÁTRIO ESQUERDO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="16551">
                                <input type="hidden" name="produto_id" value="16551">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="16551">
                                <input type="hidden" name="produto_id" value="16551">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=16551&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="16551"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=16551&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente tipo-nao-identificado"
                    data-produto-id="12304"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    title="Tipo de bem nÃ¢â€Å“ÃƒÂºo identificado">
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000300                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [?] CR-1054 - SISTEMA CFTV<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12304">
                                <input type="hidden" name="produto_id" value="12304">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12304">
                                <input type="hidden" name="produto_id" value="12304">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12304&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12304"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12304&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="12303"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000299                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [16 - PAINEL DE CONTROLE DE SOM] PAINEL DE CONTROLE DE SOM<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12303">
                                <input type="hidden" name="produto_id" value="12303">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12303">
                                <input type="hidden" name="produto_id" value="12303">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12303&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12303"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12303&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="12302"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000298                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [19 - COMPUTADOR (CPU+MOUSE+TECLADO) / NOTEBOOK] COMPUTADOR (CPU+MOUSE+TECLADO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12302">
                                <input type="hidden" name="produto_id" value="12302">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12302">
                                <input type="hidden" name="produto_id" value="12302">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12302&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12302"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12302&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="12301"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000297                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [16 - PAINEL DE CONTROLE DE SOM] PAINEL DE CONTROLE DE SOM<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12301">
                                <input type="hidden" name="produto_id" value="12301">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12301">
                                <input type="hidden" name="produto_id" value="12301">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12301&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12301"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12301&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="12300"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000296                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [68 - EQUIPAMENTOS DE CLIMATIZAÇÃO] EQUIPAMENTOS DE CLIMATIZAÇÃO (SALAO DE CULTO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12300">
                                <input type="hidden" name="produto_id" value="12300">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12300">
                                <input type="hidden" name="produto_id" value="12300">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12300&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12300"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12300&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12299"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000295                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [15 - RELOGIO DE PAREDE] RELÓGIO DE PAREDE - PRETO (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12299">
                                <input type="hidden" name="produto_id" value="12299">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12299">
                                <input type="hidden" name="produto_id" value="12299">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12299&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12299"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12299&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-pendente"
                    data-produto-id="12298"
                    data-ativo="1"
                    data-checado="0"
                    data-imprimir="0"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000294                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [18 - MICROFONE] MICROFONE - SEM FIO (SALAO DE CULTO)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12298">
                                <input type="hidden" name="produto_id" value="12298">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm " title="Marcar como checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12298">
                                <input type="hidden" name="produto_id" value="12298">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="1">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm " title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12298&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12298"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12298&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12297"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000279                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12297">
                                <input type="hidden" name="produto_id" value="12297">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12297">
                                <input type="hidden" name="produto_id" value="12297">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12297&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12297"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12297&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12296"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000278                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12296">
                                <input type="hidden" name="produto_id" value="12296">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12296">
                                <input type="hidden" name="produto_id" value="12296">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12296&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12296"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12296&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12295"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000277                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12295">
                                <input type="hidden" name="produto_id" value="12295">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12295">
                                <input type="hidden" name="produto_id" value="12295">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12295&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12295"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12295&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12294"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000276                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12294">
                                <input type="hidden" name="produto_id" value="12294">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12294">
                                <input type="hidden" name="produto_id" value="12294">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12294&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12294"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12294&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12293"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000275                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12293">
                                <input type="hidden" name="produto_id" value="12293">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12293">
                                <input type="hidden" name="produto_id" value="12293">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12293&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12293"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12293&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12292"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000274                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12292">
                                <input type="hidden" name="produto_id" value="12292">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12292">
                                <input type="hidden" name="produto_id" value="12292">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12292&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12292"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12292&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12291"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000273                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12291">
                                <input type="hidden" name="produto_id" value="12291">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12291">
                                <input type="hidden" name="produto_id" value="12291">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12291&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12291"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12291&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                                            <div
                    class="list-group-item linha-imprimir"
                    data-produto-id="12290"
                    data-ativo="1"
                    data-checado="1"
                    data-imprimir="1"
                    data-observacao=""
                    data-editado="0"
                    >
                    <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                    <div class="codigo-PRODUTO">
                        09-0355 / 000272                    </div>

                    <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                    
                    <!-- Observacao -->
                    
                    <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                    <div class="info-PRODUTO">
                        1X [4 - CADEIRA] CADEIRA - UNIVERSITÁRIA (ESPAÇO INFANTIL)<br>
                    </div>

                    <!-- Ações - Apenas para Administrador/Acessor -->
                                                                    <div class="acao-container">
                            <!-- Check -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="12290">
                                <input type="hidden" name="produto_id" value="12290">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="checado" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-success btn-sm active" title="Desmarcar checado" >
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            </form>

                            <!-- Etiqueta -->
                            <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="12290">
                                <input type="hidden" name="produto_id" value="12290">
                                <input type="hidden" name="comum_id" value="7">
                                <input type="hidden" name="imprimir" value="0">
                                <input type="hidden" name="pagina" value="1">
                                <input type="hidden" name="nome" value="">
                                <input type="hidden" name="dependencia" value="">
                                <input type="hidden" name="codigo" value="">
                                <input type="hidden" name="status" value="">
                                <button type="submit" class="btn btn-outline-info btn-sm active" title="Etiqueta" >
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                            </form>

                            <!-- Observacao -->
                            <a href="../produtos/produto_observacao.php?id_produto=12290&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-warning btn-sm action-observacao  "
                                data-produto-id="12290"
                                data-comum-id="7"
                                title="OBSERVACAO"
                                >
                                <i class="bi bi-chat-square-text-fill"></i>
                            </a>

                            <!-- EDITAR -->
                            <a href="../produtos/produto_editar.php?id_produto=12290&comum_id=7&pagina=1&nome=&dependencia=&filtro_codigo=&status="
                                class="btn btn-outline-primary btn-sm action-editar  "
                                title="EDITAR"
                                >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                                    </div>
                        </div>
</div>

<!-- PaginaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo -->
    <nav aria-label="Navegação de página" class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            
                            <li class="page-item active">
                    <a class="page-link" href="?id=7&comum_id=7&pagina=1">
                        1                    </a>
                </li>
                            <li class="page-item ">
                    <a class="page-link" href="?id=7&comum_id=7&pagina=2">
                        2                    </a>
                </li>
                            <li class="page-item ">
                    <a class="page-link" href="?id=7&comum_id=7&pagina=3">
                        3                    </a>
                </li>
            
                            <li class="page-item">
                    <a class="page-link" href="?id=7&comum_id=7&pagina=2">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                    </ul>
    </nav>

<script>
    // ======== AÃ¢â€Å“ÃƒÂ§Ã¢â€Å“ÃƒÂºES AJAX (check/etiqueta) ========
    document.addEventListener('DOMContentLoaded', () => {
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
            // Todos os botões funcionam de forma INDEPENDENTE
            // Apenas bloquear quando produto estiver em DR (ativo=0)
            const active = state.ativo === 1;

            const checkActive = state.checado === 1;
            const checkDisabled = !active; // Só bloqueia se DR

            const imprimirActive = state.imprimir === 1;
            const imprimirDisabled = !active; // Só bloqueia se DR

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

            // Observação - sempre disponível
            row.querySelectorAll('.btn-outline-warning').forEach(el => {
                el.style.display = 'inline-block';
                el.classList.remove('disabled');
                el.removeAttribute('aria-disabled');
            });

            // Editar - sempre disponível
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
            // NÃO forçar nenhum estado - cada botão é independente
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


        // Clique em EDITAR: não marcar como checado automaticamente — permitir que a edição seja feita e só marcar ao salvar
        document.addEventListener('click', function(ev) {
            const a = ev.target.closest && ev.target.closest('.action-editar');
            if (!a) return;
            // Se estiver visualmente desabilitado, ignorar
            if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') return;
            // Permitir comportamento padrão (navegação para a página de edição)
            // A marcação como 'checado' será tratada ao salvar as alterações no servidor (ProdutoUpdateController)
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

                // Sincronizar o valor dos inputs escondidos antes do submit (redundante, mas garante consistência)
                if (action === 'imprimir') {
                    const imprimirInput = form.querySelector('input[name="imprimir"]');
                    if (imprimirInput) {
                        // garantir valor coerente (já é atualizado em outros pontos do script)
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
                            // resposta nÃƒÂ£o era JSON
                        }
                        if (!response.ok || data.success === false) {
                            throw new Error(data.message || 'NÃO FOI POSSÍVEL ATUALIZAR.');
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
                        showAlert('danger', (err.message || 'ERRO AO PROCESSAR AÇÃO').toUpperCase());
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
                const comumId = anchor.dataset.comumId || 7;
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

            // Restaurar comportamento original: clique em Observação navega para a página de observação (não abrir modal).
            // Se o link estiver desabilitado, impedir a navegação.
            document.querySelectorAll('.action-observacao').forEach(a => {
                a.addEventListener('click', function(ev) {
                    if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') {
                        ev.preventDefault();
                        return;
                    }
                    // Permitir comportamento padrão: navegador seguirá o href para a página de observação.
                });
            });

            saveBtn.addEventListener('click', function() {
                if (!current) return;
                saveBtn.disabled = true;
                const formData = new FormData();
                formData.set('id_produto', current.prodId);
                formData.set('comum_id', current.comumId);
                formData.set('observacoes', ta.value.trim()); // controller expects 'observacoes'

                fetch('../../../app/controllers/update/ProdutoObservacaoController.php', {
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
            "trÃ¢â€Å“Ã‚Â¬s": "3",
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
            "hÃ¢â€Å“Ã‚Â¡fen": "-",
            "hifen": "-",
            "menos": "-",
            "barra": "/",
            "barra invertida": "\\",
            "contrabarra": "\\",
            "invertida": "\\",
            "ponto": ".",
            "vÃ¢â€Å“Ã‚Â¡rgula": ",",
            "virgula": ",",
            "espaÃ¢â€Å“Ã‚Âºo": " "
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
                alert('Campo de cÃ¢â€Å“Ã¢â€â€šdigo nÃ¢â€Å“ÃƒÂºo encontrado.');
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
                alert('Não entendi o código. Tente soletrar: "um dois três"');
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

<!-- Modal para escanear cÃ¢â€Å“Ã¢â€â€šdigo de barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <div id="scanner-container" style="width:100%; height:100%; background:#000; position:relative; overflow:hidden;"></div>

                <!-- BotÃ¢â€Å“ÃƒÂºo X para fechar -->
                <button type="button" class="btn-close-scanner" aria-label="FECHAR scanner">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Controles de cÃ¢â€Å“ÃƒÂ³mera e zoom -->
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

<!-- Modal de Observação (edição rápida via AJAX) -->
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

    /* BotÃ¢â€Å“ÃƒÂºo X para fechar */
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

    /* Controles de cÃ¢â€Å“ÃƒÂ³mera e zoom */
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

    /* Container de vÃ¢â€Å“Ã‚Â¡deo do Quagga */
    #scanner-container video,
    #scanner-container canvas {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
    }
</style>

<!-- Quagga2 para leitura de cÃ¢â€Å“Ã¢â€â€šdigos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
    // Aguardar TUDO carregar (DOM + Bootstrap + Quagga)
    document.addEventListener('DOMContentLoaded', function() {
        // Aguardar mais um pouco para garantir que Bootstrap estÃ¢â€Å“ÃƒÂ­ pronto
        setTimeout(initBarcodeScanner, 500);
    });

    function initBarcodeScanner() {
        console.log('=== INICIANDO BARCODE SCANNER ===');

        const camBtn = document.getElementById('btnCam');
        const modalEl = document.getElementById('barcodeModal');

        console.log('Elementos encontrados:', {
            camBtn: !!camBtn,
            modalEl: !!modalEl,
            bootstrap: !!window.bootstrap,
            Quagga: typeof Quagga
        });

        if (!camBtn) {
            console.error('ERRO: BotÃ¢â€Å“ÃƒÂºo btnCam nÃ¢â€Å“ÃƒÂºo encontrado!');
            return;
        }

        if (!modalEl) {
            console.error('ERRO: Modal barcodeModal nÃ¢â€Å“ÃƒÂºo encontrado!');
            return;
        }

        if (!window.bootstrap) {
            console.error('ERRO: Bootstrap nÃ¢â€Å“ÃƒÂºo carregado!');
            return;
        }

        if (typeof Quagga === 'undefined') {
            console.error('ERRO: Quagga nÃ¢â€Å“ÃƒÂºo carregado!');
            return;
        }

        const codigoInput = document.getElementById('codigo');
        const form = codigoInput ? (codigoInput.form || document.querySelector('form')) : document.querySelector('form');
        const scannerContainer = document.getElementById('scanner-container');
        const btnCloseScanner = document.querySelector('.btn-close-scanner');
        const cameraSelect = document.getElementById('cameraSelect');
        const zoomSlider = document.getElementById('zoomSlider');
        const scannerInfo = document.querySelector('.scanner-info');
        const bsModal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });

        let scanning = false;
        let lastCode = '';
        let currentStream = null;
        let currentTrack = null;
        let availableCameras = [];
        let selectedDeviceId = null;

        // FunÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo para normalizar cÃ¢â€Å“Ã¢â€â€šdigos (remover espaÃ¢â€Å“Ã‚Âºos, traÃ¢â€Å“Ã‚Âºos, barras)
        function normalizeCode(code) {
            return code.replace(/[\s\-\/]/g, '');
        }

        // Enumerar cÃ¢â€Å“ÃƒÂ³meras disponÃ¢â€Å“Ã‚Â¡veis
        async function enumerateCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                availableCameras = devices.filter(device => device.kind === 'videoinput');

                console.log(`Ã‚Â­Ã†â€™ÃƒÂ´Ã¢â€¢Â£ ${availableCameras.length} cÃ¢â€Å“ÃƒÂ³mera(s) encontrada(s)`);

                // LIMPAR e popular dropdown
                cameraSelect.innerHTML = '';
                availableCameras.forEach((camera, index) => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    option.textContent = camera.label || `Câmera ${index + 1}`;
                    cameraSelect.appendChild(option);
                });

                // Tentar selecionar cÃ¢â€Å“ÃƒÂ³mera traseira como padrÃ¢â€Å“ÃƒÂºo
                const backCamera = availableCameras.find(cam =>
                    cam.label.toLowerCase().includes('back') ||
                    cam.label.toLowerCase().includes('traseira') ||
                    cam.label.toLowerCase().includes('rear')
                );

                if (backCamera) {
                    selectedDeviceId = backCamera.deviceId;
                    cameraSelect.value = selectedDeviceId;
                } else if (availableCameras.length > 0) {
                    selectedDeviceId = availableCameras[0].deviceId;
                }

            } catch (error) {
                console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao enumerar cÃ¢â€Å“ÃƒÂ³meras:', error);
            }
        }

        // Aplicar zoom
        function applyZoom(zoomLevel) {
            if (!currentTrack) return;

            const capabilities = currentTrack.getCapabilities();
            if (capabilities.zoom) {
                const settings = currentTrack.getSettings();
                const maxZoom = capabilities.zoom.max;
                const minZoom = capabilities.zoom.min;

                // Mapear slider (1-3) para range da cÃ¢â€Å“ÃƒÂ³mera
                const zoom = minZoom + ((zoomLevel - 1) / 2) * (maxZoom - minZoom);

                currentTrack.applyConstraints({
                    advanced: [{
                        zoom: zoom
                    }]
                }).then(() => {
                    if (scannerInfo) {
                        scannerInfo.textContent = `Zoom: ${zoomLevel.toFixed(1)}x`;
                    }
                }).catch(err => {
                    console.warn('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ Zoom nÃ¢â€Å“ÃƒÂºo suportado:', err);
                });
            } else {
                console.warn('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ CÃ¢â€Å“ÃƒÂ³mera nÃ¢â€Å“ÃƒÂºo suporta zoom');
                if (scannerInfo) {
                    scannerInfo.textContent = 'Zoom nÃ¢â€Å“ÃƒÂºo disponÃ¢â€Å“Ã‚Â¡vel nesta cÃ¢â€Å“ÃƒÂ³mera';
                }
            }
        }

        function stopScanner() {
            console.log('Ã‚Â­Ã†â€™ÃƒÂ¸ÃƒÂ¦ Parando scanner...');
            try {
                Quagga.stop();

                // Parar stream de vÃ¢â€Å“Ã‚Â¡deo
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                    currentStream = null;
                }
                currentTrack = null;

                // LIMPAR canvas/video elements
                if (scannerContainer) {
                    while (scannerContainer.firstChild) {
                        scannerContainer.removeChild(scannerContainer.firstChild);
                    }
                }
                console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Scanner parado');
            } catch (e) {
                console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao parar scanner:', e);
            }
            scanning = false;
        }

        function startScanner() {
            if (scanning) {
                console.log('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ Scanner jÃ¢â€Å“ÃƒÂ­ estÃ¢â€Å“ÃƒÂ­ ativo');
                return;
            }
            console.log('Ãƒâ€ÃƒÂ»Ãƒâ€šÃ‚Â´Ã‚Â©Ãƒâ€¦ Iniciando scanner...');
            scanning = true;

            // Configurar constraints baseado na cÃ¢â€Å“ÃƒÂ³mera selecionada
            const constraints = {
                width: {
                    ideal: 1920
                },
                height: {
                    ideal: 1080
                }
            };

            if (selectedDeviceId) {
                constraints.deviceId = {
                    exact: selectedDeviceId
                };
            } else {
                constraints.facingMode = 'environment';
            }

            Quagga.init({
                inputStream: {
                    type: 'LiveStream',
                    target: scannerContainer,
                    constraints: constraints
                },
                decoder: {
                    readers: [
                        'ean_reader', // EAN-13 (mais comum)
                        'code_128_reader', // CODE-128
                        'ean_8_reader', // EAN-8
                        'upc_reader', // UPC-A
                        'upc_e_reader' // UPC-E
                    ],
                    multiple: false
                },
                locate: true,
                locator: {
                    patchSize: 'large', // Maior = mais rÃ¢â€Å“ÃƒÂ­pido, menos preciso
                    halfSample: true // Processar imagem menor = mais rÃ¢â€Å“ÃƒÂ­pido
                },
                frequency: 10, // Reduzir frequÃ¢â€Å“Ã‚Â¬ncia de localizaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo = mais rÃ¢â€Å“ÃƒÂ­pido
                numOfWorkers: navigator.hardwareConcurrency || 4
            }, function(err) {
                if (err) {
                    console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao iniciar scanner:', err);
                    alert('NÃ¢â€Å“ÃƒÂºo foi possÃ¢â€Å“Ã‚Â¡vel acessar a cÃ¢â€Å“ÃƒÂ³mera:\n\n' + err.message + '\n\nVerifique se:\nÃƒâ€Ã‚Â£ÃƒÂ´ VocÃ¢â€Å“Ã‚Â¬ deu permissÃ¢â€Å“ÃƒÂºo para usar a cÃ¢â€Å“ÃƒÂ³mera\nÃƒâ€Ã‚Â£ÃƒÂ´ O site estÃ¢â€Å“ÃƒÂ­ em HTTPS (ou localhost)\nÃƒâ€Ã‚Â£ÃƒÂ´ A cÃ¢â€Å“ÃƒÂ³mera nÃ¢â€Å“ÃƒÂºo estÃ¢â€Å“ÃƒÂ­ sendo usada por outro app');
                    scanning = false;
                    bsModal.hide();
                    return;
                }
                console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Scanner iniciado com sucesso!');
                Quagga.start();

                // Capturar stream para controle de zoom
                const videoElement = scannerContainer.querySelector('video');
                if (videoElement && videoElement.srcObject) {
                    currentStream = videoElement.srcObject;
                    const videoTracks = currentStream.getVideoTracks();
                    if (videoTracks.length > 0) {
                        currentTrack = videoTracks[0];

                        // Aplicar zoom inicial
                        applyZoom(parseFloat(zoomSlider.value));
                    }
                }
            });

            Quagga.offDetected();
            Quagga.onDetected(function(result) {
                if (!result || !result.codeResult || !result.codeResult.code) return;
                const rawCode = result.codeResult.code.trim();
                if (!rawCode || rawCode === lastCode) return;

                // Verificar qualidade da leitura (evitar falsos positivos)
                if (result.codeResult.decodedCodes && result.codeResult.decodedCodes.length > 0) {
                    const avgError = result.codeResult.decodedCodes.reduce((sum, code) => {
                        return sum + (code.error || 0);
                    }, 0) / result.codeResult.decodedCodes.length;

                    // Se erro mÃ¢â€Å“Ã‚Â®dio muito alto, ignorar
                    if (avgError > 0.12) return; // Limiar mais rigoroso para velocidade
                }

                // Normalizar cÃ¢â€Å“Ã¢â€â€šdigo (remover espaÃ¢â€Å“Ã‚Âºos, traÃ¢â€Å“Ã‚Âºos, barras)
                const code = normalizeCode(rawCode);

                console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ãƒâ‚¬ CÃ¢â€Å“Ã¢â€â€šdigo detectado:', rawCode, 'Ãƒâ€ÃƒÂ¥Ãƒâ€  normalizado:', code);
                lastCode = rawCode;

                // Feedback visual (borda verde)
                const frame = document.querySelector('.scanner-frame');
                if (frame) {
                    frame.style.borderColor = '#28a745';
                    frame.style.boxShadow = '0 0 0 9999px rgba(40, 167, 69, 0.3)';
                }

                // Pequeno delay para dar feedback visual
                setTimeout(() => {
                    stopScanner();
                    bsModal.hide();

                    if (codigoInput) {
                        codigoInput.value = code;
                        codigoInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        codigoInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                    if (form) {
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    }
                }, 200); // Reduzido de 300ms para 200ms = mais rÃ¢â€Å“ÃƒÂ­pido
            });
        }

        // ===== EVENTO DO BOTÃ¢â€Å“ÃƒÂ¢O DE CÃ¢â€Å“ÃƒÂ©MERA =====
        camBtn.addEventListener('click', async function(e) {
            console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ã‚Â© BotÃ¢â€Å“ÃƒÂºo de cÃ¢â€Å“ÃƒÂ³mera CLICADO!');
            e.preventDefault();
            e.stopPropagation();
            lastCode = '';

            // Enumerar cÃ¢â€Å“ÃƒÂ³meras antes de abrir modal
            await enumerateCameras();

            console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃ‚Â¼ Abrindo modal...');
            bsModal.show();

            // Dar tempo para o modal abrir antes de iniciar cÃ¢â€Å“ÃƒÂ³mera
            setTimeout(() => {
                console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃƒâ€˜ Iniciando cÃ¢â€Å“ÃƒÂ³mera...');
                startScanner();
            }, 400);
        });

        console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener da cÃ¢â€Å“ÃƒÂ³mera ADICIONADO ao botÃ¢â€Å“ÃƒÂºo');

        // ===== EVENTO DE MUDANÃ¢â€Å“ÃƒÂ§A DE CÃ¢â€Å“ÃƒÂ©MERA =====
        if (cameraSelect) {
            cameraSelect.addEventListener('change', function(e) {
                selectedDeviceId = e.target.value;
                console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ã¢â€¢Â£ Mudando para cÃ¢â€Å“ÃƒÂ³mera:', selectedDeviceId);

                // Reiniciar scanner com nova cÃ¢â€Å“ÃƒÂ³mera
                if (scanning) {
                    stopScanner();
                    setTimeout(() => startScanner(), 300);
                }
            });
            console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener de seleÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo de cÃ¢â€Å“ÃƒÂ³mera adicionado');
        }

        // ===== EVENTO DE CONTROLE DE ZOOM =====
        if (zoomSlider) {
            zoomSlider.addEventListener('input', function(e) {
                const zoomLevel = parseFloat(e.target.value);
                applyZoom(zoomLevel);
            });
            console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener de zoom adicionado');
        }

        // ===== EVENTO DO BOTÃ¢â€Å“ÃƒÂ¢O X =====
        if (btnCloseScanner) {
            btnCloseScanner.addEventListener('click', function(e) {
                console.log('Ãƒâ€ÃƒËœÃƒÂ® BotÃ¢â€Å“ÃƒÂºo X clicado');
                e.preventDefault();
                e.stopPropagation();
                stopScanner();
                bsModal.hide();
            });
            console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener do botÃ¢â€Å“ÃƒÂºo X adicionado');
        }

        // ===== LIMPAR QUANDO MODAL FECHAR =====
        modalEl.addEventListener('hidden.bs.modal', function() {
            console.log('Ã‚Â­Ã†â€™ÃƒÅ“Ã‚Â¬ Modal fechado');
            stopScanner();
            // Reset visual do frame
            const frame = document.querySelector('.scanner-frame');
            if (frame) {
                frame.style.borderColor = 'rgba(255, 255, 255, 0.8)';
                frame.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.5)';
            }
        });

        console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃƒÂ« === BARCODE SCANNER CONFIGURADO COM SUCESSO ===');
    }
</script>


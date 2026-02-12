<!-- Menu Lateral Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="menuLateralLabel">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-flex flex-column">
            <!-- Início -->
            <a href="/comuns" class="menu-item" data-bs-dismiss="offcanvas">
                <i class="bi bi-house-door me-3"></i>
                <span>Início</span>
            </a>

            <!-- Planilhas -->
            <div class="menu-section">
                <div class="menu-section-title">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                    <span>Planilhas</span>
                </div>
                <div class="menu-submenu">
                    <a href="/planilhas/importar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-upload me-2"></i>
                        <span>Importar Planilha</span>
                    </a>
                    <a href="/planilhas/visualizar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-eye me-2"></i>
                        <span>Visualizar Planilha</span>
                    </a>
                    <a href="/planilhas/progresso" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-bar-chart-line me-2"></i>
                        <span>Progresso de Importação</span>
                    </a>
                </div>
            </div>

            <!-- Produtos -->
            <div class="menu-section">
                <div class="menu-section-title">
                    <i class="bi bi-box-seam me-2"></i>
                    <span>Produtos</span>
                </div>
                <div class="menu-submenu">
                    <a href="/produtos" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-list-ul me-2"></i>
                        <span>Listar Produtos</span>
                    </a>
                    <a href="/produtos/criar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span>Criar Produto</span>
                    </a>
                    <a href="/produtos/editar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-pencil me-2"></i>
                        <span>Editar Produto</span>
                    </a>
                    <a href="/produtos/etiqueta" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-tags me-2"></i>
                        <span>Copiar Etiquetas</span>
                    </a>
                </div>
            </div>

            <!-- Dependências -->
            <div class="menu-section">
                <div class="menu-section-title">
                    <i class="bi bi-diagram-3 me-2"></i>
                    <span>Dependências</span>
                </div>
                <div class="menu-submenu">
                    <a href="/dependencias" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-list-ul me-2"></i>
                        <span>Listar Dependências</span>
                    </a>
                    <a href="/dependencias/criar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span>Criar Dependência</span>
                    </a>
                    <a href="/dependencias/editar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-pencil me-2"></i>
                        <span>Editar Dependência</span>
                    </a>
                </div>
            </div>

            <!-- Usuários -->
            <div class="menu-section">
                <div class="menu-section-title">
                    <i class="bi bi-people me-2"></i>
                    <span>Usuários</span>
                </div>
                <div class="menu-submenu">
                    <a href="/usuarios" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-list-ul me-2"></i>
                        <span>Listar Usuários</span>
                    </a>
                    <a href="/usuarios/criar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span>Criar Usuário</span>
                    </a>
                    <a href="/usuarios/editar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-pencil me-2"></i>
                        <span>Editar Usuário</span>
                    </a>
                </div>
            </div>

            <!-- Relatórios -->
            <div class="menu-section">
                <div class="menu-section-title">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <span>Relatórios</span>
                </div>
                <div class="menu-submenu">
                    <a href="/relatorios/14-1" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-file-earmark-pdf me-2"></i>
                        <span>Relatório 14.1</span>
                    </a>
                    <a href="/relatorios/visualizar" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-eye me-2"></i>
                        <span>Visualizar Relatório</span>
                    </a>
                    <a href="/relatorios/assinatura" class="menu-subitem" data-bs-dismiss="offcanvas">
                        <i class="bi bi-pen me-2"></i>
                        <span>Assinatura Digital</span>
                    </a>
                </div>
            </div>

            <hr class="my-3">

            <!-- Sair -->
            <a href="/logout" class="menu-item text-danger">
                <i class="bi bi-box-arrow-right me-3"></i>
                <span>Sair</span>
            </a>
        </div>
    </div>
</div>
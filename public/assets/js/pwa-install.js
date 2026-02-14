/**
 * pwa-install.js - Gerenciamento de instalação PWA
 * Este script deve ser carregado em todas as páginas do sistema
 */

(function() {
    'use strict';

    let deferredPrompt = null;
    let installButton = null;

    // Registrar Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('✓ Service Worker registrado:', registration.scope);
                    
                    // Verificar atualizações a cada 60 segundos
                    setInterval(() => {
                        registration.update();
                    }, 60000);
                })
                .catch(error => {
                    console.error('✗ Erro ao registrar Service Worker:', error);
                });
        });
    }

    // Detectar evento beforeinstallprompt (PWA pode ser instalado)
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('✓ PWA pode ser instalado');
        
        // Prevenir comportamento padrão (Chrome mostra mini-infobar)
        e.preventDefault();
        
        // Salvar evento para uso posterior
        deferredPrompt = e;
        
        // Adicionar opção no menu (sem mostrar botão flutuante)
        addInstallMenuOption();
    });

    // Detectar quando o app foi instalado
    window.addEventListener('appinstalled', () => {
        console.log('✓ PWA instalado com sucesso!');
        deferredPrompt = null;
        // Remover opção do menu
        const menuItem = document.getElementById('menu-install-pwa');
        if (menuItem && menuItem.parentElement) {
            menuItem.parentElement.remove();
        }
    });

    // Instalação via menu (sem botão flutuante)
    async function triggerInstall() {
        if (!deferredPrompt) {
            console.warn('Prompt de instalação não disponível');
            return;
        }

        // Mostrar prompt de instalação nativo
        deferredPrompt.prompt();

        // Aguardar escolha do usuário
        const { outcome } = await deferredPrompt.userChoice;
        
        console.log(`Usuário ${outcome === 'accepted' ? 'aceitou' : 'recusou'} a instalação`);

        // Limpar prompt (só pode ser usado uma vez)
        deferredPrompt = null;
        
        // Remover opção do menu se instalou
        if (outcome === 'accepted') {
            const menuItem = document.getElementById('menu-install-pwa');
            if (menuItem && menuItem.parentElement) {
                menuItem.parentElement.remove();
            }
        }
    }

    // Verificar se está instalado
    function isInstalled() {
        // Verificar display mode
        if (window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // iOS Safari
        if (window.navigator.standalone === true) {
            return true;
        }
        
        return false;
    }

    // Adicionar item no menu para instalação
    function addInstallMenuOption() {
        // Aguardar DOM carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addInstallMenuOption);
            return;
        }

        // Procurar por menu dropdown
        const menuDropdown = document.querySelector('.dropdown-menu');
        if (!menuDropdown || isInstalled()) return;

        // Verificar se já existe
        if (document.getElementById('menu-install-pwa')) return;

        // Só adicionar se houver prompt disponível
        if (!deferredPrompt) return;

        const menuItem = document.createElement('li');
        const link = document.createElement('a');
        link.id = 'menu-install-pwa';
        link.className = 'dropdown-item';
        link.href = '#';
        link.innerHTML = '<i class="bi bi-download me-2"></i>Instalar App';
        
        link.addEventListener('click', (e) => {
            e.preventDefault();
            triggerInstall();
        });

        menuItem.appendChild(link);
        
        // Adicionar após primeiro item do menu (geralmente é "Menu Principal")
        const firstItem = menuDropdown.querySelector('li');
        if (firstItem) {
            firstItem.after(menuItem);
        } else {
            menuDropdown.appendChild(menuItem);
        }
    }

    // Verificar se está rodando como PWA instalado
    if (isInstalled()) {
        console.log('✓ Rodando como PWA instalado');
        document.documentElement.classList.add('pwa-installed');
    }

    // Expor API global para controle manual
    window.PWAInstall = {
        install: triggerInstall,
        isInstalled: isInstalled,
        canInstall: () => deferredPrompt !== null
    };

    console.log('✓ PWA Install Manager carregado (modo menu apenas)');
})();

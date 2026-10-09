# Proposta: Barra de Ações Rápidas Mobile & Atalhos de Navegação Rápida no PC

## Motivação e Contexto
O aplicativo `check-planilha-imobilizado` é amplamente utilizado tanto em computadores (gestão de planilhas e relatórios) quanto em dispositivos móveis (conferência de bens em campo, nas salas e dependências).

No celular, a rolagem extensa em tabelas de bens dificulta o retorno à busca e aos filtros.
No computador, a ausência de atalhos rápidos reduz a agilidade dos conferentes que precisam analisar centenas de itens por dia.

## Objetivos
1. Disponibilizar uma **Barra de Ações Rápidas Mobile** fixa na base da tela para dispositivos com largura <= 768px, oferecendo acesso com 1 toque a:
   - Foco imediato na busca / código do bem.
   - Alternância de filtros.
   - Retorno instantâneo ao topo da página.
   - Painel de atalhos.
2. Disponibilizar **Atalhos de Teclado Globais** para Desktop:
   - `/`: Focar na busca / código do bem.
   - `F`: Abrir ou fechar gaveta de filtros.
   - `T`: Rolar suavemente para o topo.
   - `?`: Abrir modal acessível de atalhos.
   - `Esc`: Fechar modais ou limpar foco.
3. Garantir conformidade com acessibilidade (botões táteis >= 44px e atributos ARIA).

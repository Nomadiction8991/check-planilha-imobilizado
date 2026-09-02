# Proposta: Busca Progressiva e Acessível de Administração na Importação

## Justificativa e Motivação
Na tela de importação de planilhas (`/spreadsheets/import`), usuários com acesso a múltiplas administrações precisam selecionar a administração de destino em um seletor nativo `<select>`. Quando há muitas administrações cadastradas, a navegação manual é demorada e propensa a erros.

Seguindo o padrão de UI/UX já implementado com sucesso nas telas de Igrejas, Usuários, Tipos de Bens e Dependências, a tela de importação deve fornecer um campo de busca textual progressivo com feedback dinâmico (`aria-live="polite"`), filtrando as opções em tempo real e resetando adequadamente se a opção selecionada for oculta pelo termo de busca.

## Escopo
- Adicionar campo de busca textual de administração no formulário de envio da planilha.
- Conectar atributos ARIA (`aria-controls`, `role="status"`) e mensagem de acessibilidade quando nenhum resultado for encontrado.
- Implementar script cliente seguro, progressivo e compatível com SSR (fallback automático caso JavaScript não esteja disponível).
- Criar testes automatizados de feature comprovando a renderização dos elementos e atributos de acessibilidade.

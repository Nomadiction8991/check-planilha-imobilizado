# Proposta: Busca e Filtro por Administração na Listagem de Tipos de Bem

## Motivação
A listagem de tipos de bem exibe tipos vinculados a administrações específicas ou cadastros globais. Atualmente, administradores e usuários autorizados não dispõem de um filtro dedicado por administração com busca interativa na interface de tipos de bem, ao contrário de módulos como igrejas, usuários, departamentos e relatórios.

## Escopo
1. Atualizar o `AssetTypeFilters` para aceitar `administracao_id` (inteiro positivo opcional, ou null).
2. Atualizar o `LegacyAssetTypeBrowserServiceInterface` e `LegacyAssetTypeBrowserService` para filtrar por `administracao_id` quando fornecido (respeitando o escopo do usuário).
3. Adicionar o método `administrationOptions()` ou fornecer a lista de administrações para a view através do controller.
4. Incluir no formulário de filtros de `resources/views/asset-types/index.blade.php` o campo de busca digitável progressiva de administração (`data-asset-types-admin-search`) e o seletor correspondente com mensagem acessível (`aria-live="polite"`).
5. Cobrir a funcionalidade com testes unitários no DTO e Service, além de testes de feature no Controller e na View.

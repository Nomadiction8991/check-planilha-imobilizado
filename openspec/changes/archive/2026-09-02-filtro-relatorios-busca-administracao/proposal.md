# Proposal: Filtro por Administração na Listagem de Relatórios

## Why
Atualmente a tela de relatórios (`/reports`) permite apenas selecionar a igreja diretamente em uma lista simples com busca textual de igrejas. Quando o sistema possui muitas administrações e centenas de congregações, é trabalhoso encontrar a igreja desejada sem poder pré-filtrar ou selecionar pela administração à qual a congregação pertence. Além disso, todas as demais telas principais (Produtos, Conferência, Igrejas, Departamentos, Tipos de Bens e Importação) já contam com filtro por administração e busca dinâmica no select.

## What
1. Adicionar o método `administrationOptions(): Collection` no contrato `LegacyReportServiceInterface` e no serviço `LegacyReportService`.
2. Atualizar o `LegacyReportController@index` para injetar `administrations` na view `reports.index` e aceitar o parâmetro `administracao_id` no filtro/request.
3. Permitir filtrar o carregamento das opções de igrejas por administração selecionada ou carregar todas as administrações cadastradas.
4. Adicionar na view `resources/views/reports/index.blade.php` o campo de busca textual de administração e o select de administrações com busca instantânea via JavaScript (seguindo o padrão visual já consolidado).
5. Cobrir com testes unitários no `LegacyReportServiceTest` e testes de feature no `LegacyReportPagesTest`.

## Impact
- Melhora a ergonomia e consistência de navegação para administradores e operadores.
- Alinha a tela de relatórios com as diretrizes de UX e acessibilidade das demais listagens.

# Proposta: Filtro e busca rápida de administração na listagem de igrejas

## Motivação
A listagem de igrejas (`/churches`) atualmente permite apenas a busca textual simples por código ou descrição da igreja. Como o sistema organiza igrejas sob administrações específicas e administrações com muitos registros tornam a navegação demorada, adicionar a seleção e o filtro progressivo de administração melhora a usabilidade e mantém a consistência com as demais telas do sistema (produtos, relatórios, usuários).

## Escopo
- Adicionar o campo `administrationId` no DTO `ChurchFilters`.
- Filtrar por `administracao_id` no `LegacyChurchBrowserService`.
- Disponibilizar `administrations` na view `churches/index.blade.php`.
- Implementar campo select de administração com busca rápida integrada via JavaScript progressivo (sem dependências externas) na view de igrejas.
- Preservar compatibilidade total de rotas e testes existentes.

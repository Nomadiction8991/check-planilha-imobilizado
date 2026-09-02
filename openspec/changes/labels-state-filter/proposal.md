# Proposta: Filtro por Estado na Seleção e Cópia de Etiquetas

## Por que e o que
A tela de cópia de etiquetas (`/copiar-etiquetas`) permite selecionar administrações e igrejas para copiar e verificar etiquetas patrimoniais. Para alinhar a interface com os demais módulos do sistema (como Relatórios, Produtos, Departamentos, Igrejas e Usuários) e facilitar o trabalho de conferência de congregações por Unidade Federativa (UF), a tela deve disponibilizar um seletor de estado (UF) e filtrar as congregações disponíveis conforme o estado e/ou administração selecionados.

## Escopo
- Adicionar parâmetro e suporte a `$state` no método `availableChurches(?int $administrationId = null, ?string $state = null)` do serviço `LegacyAuthSessionService` e sua interface.
- Passar `$states` e `$selectedState` da controller `LegacyRouteCompatibilityController::labels` para a view `labels.index`.
- Incluir o campo select de Estado (UF) no formulário de filtros com sticky header e integração nos testes de feature.

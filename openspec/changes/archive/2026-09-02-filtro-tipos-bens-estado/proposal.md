# Proposta: Filtro por Estado (UF) na Listagem de Tipos de Bem

## Justificativa e Motivação
A listagem de tipos de bem já oferece filtro textual por código/descrição e por administração específica. Para manter a coerência visual e funcional com os módulos de Igrejas, Dependências, Usuários e Administrações, é necessário permitir o filtro direto pela UF (Estado) da administração vinculada ao tipo de bem.

## Escopo
- Adicionar propriedade `state` no DTO `AssetTypeFilters` sanitizando para 2 caracteres maiúsculos.
- Incluir suporte ao filtro no `LegacyAssetTypeBrowserService` aplicando escopo `whereHas('administracao', fn($q) => $q->where('estado', $state))`.
- Passar lista de estados do `config('brazil.states')` na view `asset-types.index` e exibir o campo select com os estados brasileiros.
- Suíte completa de testes unitários e de feature.

# Proposta: Padronização da passagem de estados para as views de criação e edição de produtos

## Por que
Seguindo as padronizações arquiteturais realizadas em Administrações, Auditoria, Dependências, Tipos de Bens e Importação de Planilhas, o controller `LegacyProductController` já injeta `states` a partir de `config('brazil.states', [])` nos métodos `index()` e `verification()`, mas ainda não o fazia explicitamente nos métodos `create()` e `edit()`. A injeção explícita de `states` em todas as views de formulário garante uniformidade no ecossistema e evita dependência de fallbacks globais em templates Blade.

## O que
- Injetar `'states' => (array) config('brazil.states', [])` nos métodos `create()` e `edit()` de `LegacyProductController`.
- Adicionar asserções de teste no `LegacyProductControllerTest` garantindo que as views `products.create` e `products.edit` recebem a variável `states`.

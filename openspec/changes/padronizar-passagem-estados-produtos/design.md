# Decisão Técnica

Injetar `'states' => (array) config('brazil.states', [])` nos dados passados para as views `products.create` e `products.edit` em `LegacyProductController`.

Isso alinha `create()` e `edit()` com o que já é feito em `index()` e `verification()` do mesmo controller, assim como nos outros controllers do sistema.

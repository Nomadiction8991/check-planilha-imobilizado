# Design Técnico: Padronização de Estados na View de Auditoria

## Abordagem
- Atualizar `LegacyAuditController@index` para enviar `'states' => (array) config('brazil.states', [])` nos dados da view.
- Atualizar a suíte de testes de `LegacyAuditControllerTest` para validar que a view possui a variável `states`.

# Proposta: Padronização de Estados na View de Auditoria

## Intenção e Escopo
Padronizar a passagem da lista de estados (UFs brasileiras) para a view de auditoria (`audits.index`) através do controller `LegacyAuditController`, mantendo consistência arquitetural com os outros controllers do sistema (LegacyAdministrationController, LegacyReportController, LegacyProductController, LegacyChurchController, LegacyUserController, LegacyDepartmentController, LegacyAssetTypeController).

## Abordagem
1. Injetar ou disponibilizar os estados via `config('brazil.states', [])` no `LegacyAuditController@index`.
2. Adicionar asserção em teste de feature garantindo a presença da chave `states` na view retornada.
3. Manter compatibilidade total com os testes e rotas existentes.

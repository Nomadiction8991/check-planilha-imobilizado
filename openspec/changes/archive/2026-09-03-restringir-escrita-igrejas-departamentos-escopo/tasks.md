## 1. Escopo compartilhado

- [x] 1.1 Estender `ResolvesLegacyProductScope` com `assertAdministrationWithinScope` e teste unitário de cobertura <!-- id: task-scope -->

## 2. Escrita de igrejas

- [x] 2.1 Bloquear `LegacyChurchManagementService::update` quando igreja atual ou nova administração estiver fora do escopo <!-- id: task-church-write -->
- [x] 2.2 Adicionar testes de `LegacyChurchManagementService` cobrindo criação bloqueada/emitida, troca de administração e bypass de admin <!-- id: task-church-tests -->

## 3. Escrita de dependências

- [x] 3.1 Bloquear `LegacyDepartmentManagementService::create/update/delete` quando igreja (atual ou nova) estiver fora do escopo <!-- id: task-dept-write -->
- [x] 3.2 Adicionar testes de `LegacyDepartmentManagementService` cobrindo todas as operações bloqueadas e permitidas com prova de não mutação <!-- id: task-dept-tests -->

## 4. Verificação e entrega

- [x] 4.1 Rodar `php -l` nos arquivos alterados e suíte de testes afetada <!-- id: task-tests -->
- [x] 4.2 Validar `openspec validate` e saúde da aplicação (`curl /login`) <!-- id: task-validate -->

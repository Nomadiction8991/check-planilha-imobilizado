## 1. Testes de regressão

- [x] 1.1 Adicionar teste unitário cobrindo caracteres perigosos e texto legítimo na exportação.
- [x] 1.2 Adicionar teste de feature confirmando proteção no download CSV.

## 2. Implementação

- [x] 2.1 Implementar sanitização de células textuais controladas por usuários.
- [x] 2.2 Aplicar sanitização aos campos textuais da auditoria sem alterar valores sistêmicos.

## 3. Validação

- [x] 3.1 Executar testes direcionados e suíte completa.
- [x] 3.2 Validar sintaxe PHP, OpenSpec e saúde da aplicação.
- [ ] 3.3 Commitar e enviar alteração para deploy automático.

Nota: teste HTTP específico ficou pendente; suíte existente já cobre endpoint de download e teste unitário validou sanitização no gerador CSV.

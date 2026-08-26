## ADDED Requirements

### Requirement: Middleware CSRF híbrido ativo no grupo web
O grupo de middleware `web` SHALL usar o middleware CSRF híbrido (com rotas isentas declaradas) no lugar do CSRF padrão, de forma que as isenções sejam aplicadas às requisições reais.

#### Scenario: Grupo web contém o middleware híbrido
- GIVEN a aplicação inicializada
- WHEN o grupo de middleware `web` é inspecionado
- THEN o middleware CSRF híbrido está presente e o CSRF padrão ausente

#### Scenario: Rota isenta fora do ambiente de teste
- GIVEN o ambiente da aplicação definido como produção
- WHEN um POST sem token chega a uma rota isenta (preview actions ou start)
- THEN a resposta não é 419

#### Scenario: Rota protegida continua bloqueada
- GIVEN o ambiente da aplicação definido como produção
- WHEN um POST sem token chega a uma rota não isenta
- THEN a resposta é 419

## 1. Testes de apresentação

- [x] 1.1 Adicionar teste de listagem que confirme a ausência do link de edição para usuário autenticado sem a permissão `products.edit`.
- [x] 1.2 Adicionar teste de verificação que confirme a ausência do link de edição para usuário sem a permissão, preservando a identificação e o checklist.
- [x] 1.3 Cobrir que administrador continua vendo a ação de edição nas telas afetadas.

## 2. Implementação das views

- [x] 2.1 Calcular a capacidade efetiva de edição com fallback seguro para sessões sem permissões carregadas.
- [x] 2.2 Condicionar o link de edição da listagem à capacidade efetiva, mantendo o modo consulta.
- [x] 2.3 Condicionar o link de edição da verificação à mesma capacidade, sem remover as demais ações.

## 3. Validação e entrega

- [x] 3.1 Executar os testes direcionados e a suíte completa do projeto, interrompendo repetição de qualquer falha idêntica conforme as guardas do job.
- [x] 3.2 Executar a validação do OpenSpec, a verificação de sintaxe PHP aplicável e as sondas de saúde das telas.
- [x] 3.3 Atualizar as tarefas concluídas, criar o commit convencional e enviar para a branch principal; confirmar o deploy e a saúde após publicação.

## 1. Especificação e estrutura

- [x] 1.1 Validar os parâmetros enviados pelo formulário de relatórios e definir os marcadores de estado acessível.
- [x] 1.2 Confirmar que as buscas locais de administração e igreja não participam da submissão automática.

## 2. Implementação do comportamento

- [x] 2.1 Adicionar marcadores e região de status ao formulário de relatórios.
- [x] 2.2 Implementar submissão automática dos selects de administração, estado e igreja com debounce e deduplicação.
- [x] 2.3 Preservar o envio manual e cancelar timers durante qualquer submissão.

## 3. Testes e entrega

- [x] 3.1 Adicionar teste de view verificando os marcadores, filtros enviados e exclusão das buscas locais da rotina.
- [x] 3.2 Executar lint PHP/Blade aplicável e a suíte PHPUnit relevante.
- [x] 3.3 Validar o change OpenSpec, a saúde local e a rota de relatórios.
- [x] 3.4 Commitar, enviar para a branch principal e confirmar o deploy.

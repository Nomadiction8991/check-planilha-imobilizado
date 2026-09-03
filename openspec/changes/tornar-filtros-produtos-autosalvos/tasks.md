## 1. Especificação e estrutura

- [x] 1.1 Validar o change e revisar os parâmetros dos dois formulários GET de produtos.
- [x] 1.2 Definir marcadores acessíveis e estado de envio sem alterar o contrato visual existente.

## 2. Implementação do comportamento

- [x] 2.1 Adicionar inicializador compartilhado para submissão automática após alteração dos selects de filtro.
- [x] 2.2 Adicionar debounce da busca geral, confirmação pelo evento de busca e tratamento da limpeza do campo.
- [x] 2.3 Cancelar timers pendentes, deduplicar submissões e preservar o envio manual do formulário.
- [x] 2.4 Integrar o comportamento nas telas de listagem e verificação sem submeter as buscas locais de administração e igreja.

## 3. Testes e entrega

- [x] 3.1 Adicionar testes de view verificando os marcadores, controles e regras do inicializador nas duas telas.
- [x] 3.2 Executar lint PHP/Blade aplicável e a suíte PHPUnit relevante.
- [x] 3.3 Validar o change OpenSpec, a saúde local e o comportamento HTTP das telas.
- [x] 3.4 Commitar, enviar para a branch principal e confirmar o deploy.

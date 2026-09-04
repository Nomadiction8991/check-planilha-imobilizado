## 1. Especificação e cobertura

- [x] 1.1 Registrar a mudança de comportamento da tela de etiquetas na especificação delta.
- [x] 1.2 Adicionar testes de renderização que garantam os eventos e atributos do envio automático sem remover o fluxo manual.

## 2. Implementação da interface

- [x] 2.1 Adicionar o identificador do formulário de filtros de etiquetas e a região de status acessível.
- [x] 2.2 Submeter automaticamente os selects de administração, estado, igreja e dependência após alterações reais.
- [x] 2.3 Manter as buscas locais de administração e igreja sem submeter o formulário durante a digitação.
- [x] 2.4 Remover a página atual ao iniciar uma navegação causada por filtro e deduplicar submissões.
- [x] 2.5 Preservar a limpeza de filtros, o envio manual e o fallback para navegadores sem `requestSubmit`.

## 3. Verificação

- [x] 3.1 Validar o change com o OpenSpec.
- [x] 3.2 Executar lint PHP nos arquivos PHP alterados e a suíte de testes relevante.
- [x] 3.3 Confirmar a saúde da aplicação após a alteração.

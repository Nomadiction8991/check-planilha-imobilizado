## 1. Testes de consulta

- [x] 1.1 Adicionar cenário de busca por tipo e dependência editados, garantindo que valores originais substituídos não sejam tratados como atuais.
- [x] 1.2 Adicionar cenários de filtros por identificador para tipo e dependência atuais, incluindo fallback para relações originais inválidas ou ausentes.

## 2. Implementação

- [x] 2.1 Extrair os predicados de classificação vigente para manter a regra de edição consistente na busca e nos filtros.
- [x] 2.2 Aplicar os predicados à paginação de produtos sem alterar escopo, ordenação, paginação ou parâmetros públicos.

## 3. Verificação

- [x] 3.1 Validar a especificação OpenSpec, executar os testes direcionados e a suíte completa, e verificar a sintaxe dos arquivos PHP alterados.
- [x] 3.2 Verificar as telas de produtos em execução e registrar a mudança para publicação.

## 1. Testes de escopo

- [x] 1.1 Adicionar teste unitário que configure usuário restrito com múltiplas administrações e confirme que a paginação de produtos inclui somente o escopo permitido.
- [x] 1.2 Adicionar teste unitário que confirme que administração solicitada fora do escopo retorna zero produtos e que administrador mantém acesso global.
- [x] 1.3 Adicionar testes unitários para opções de igrejas e dependências, garantindo que registros fora do escopo não sejam apresentados.
- [x] 1.4 Adicionar teste de integração da tela de produtos confirmando que o seletor renderiza somente igrejas permitidas.

## 2. Implementação

- [x] 2.1 Implementar resolução normalizada do escopo de administrações a partir da sessão, com fallback seguro para administração principal.
- [x] 2.2 Aplicar o escopo à consulta paginada e manter a interseção com filtros explícitos de administração, igreja e estado.
- [x] 2.3 Aplicar o escopo às opções de igrejas e dependências sem alterar o contrato das views.

## 3. Verificação

- [x] 3.1 Executar os testes focados e corrigir regressões.
- [x] 3.2 Executar validação OpenSpec e a suíte completa antes da publicação.
- [x] 3.3 Verificar sintaxe PHP e saúde da aplicação após a implementação.

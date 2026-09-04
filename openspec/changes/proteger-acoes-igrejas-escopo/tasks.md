## 1. Autorização no serviço

- [x] 1.1 Adicionar testes unitários para contagem e exclusão de igreja fora do escopo, incluindo administração principal, adicional e administrador global.
- [x] 1.2 Fazer a resolução de igreja reutilizar a validação de escopo existente antes de consultar ou alterar produtos.

## 2. Contratos HTTP

- [x] 2.1 Adicionar testes de integração para resposta 403 na contagem e redirecionamento seguro na exclusão fora do escopo.
- [x] 2.2 Tratar a exceção de escopo na contagem sem expor produtos e preservar as mensagens de exclusão e atualização.

## 3. Verificação e entrega

- [x] 3.1 Executar os testes focados, o lint PHP e a suíte PHPUnit completa.
- [x] 3.2 Validar o change OpenSpec e as sondas de saúde da aplicação.
- [x] 3.3 Commitar, enviar para a branch principal e confirmar o deploy.

## Context

A edição de igrejas já valida o escopo no serviço de gerenciamento, e a listagem aplica o escopo nas consultas. As operações auxiliares de contagem e exclusão recebem apenas um ID e atualmente obtêm a igreja sem validar sua administração, permitindo que uma requisição direta contorne a proteção da tela.

## Goals / Non-Goals

**Goals:**

- Centralizar a validação de acesso à igreja no serviço de gerenciamento.
- Reutilizar a mesma regra de escopo da edição e da navegação de igrejas.
- Fazer com que contagem e exclusão falhem antes de consultar ou alterar produtos fora do escopo.
- Traduzir a falha de autorização em respostas coerentes com cada tipo de endpoint.

**Non-Goals:**

- Alterar permissões de rota, banco de dados, sessão ou administradores globais.
- Criar uma nova política de autorização ou mudar o comportamento das telas de produtos.
- Diferenciar permissões de visualização e exclusão além das regras já aplicadas pelo middleware.

## Decisions

1. **Validar no serviço, não somente no controlador.** `findChurch` e `countProducts` serão pontos de entrada compartilhados pelas ações da tela. A validação no serviço impede que consumidores futuros ou chamadas internas esqueçam a regra. A alternativa de validar apenas no controlador foi rejeitada por duplicar autorização na camada HTTP.

2. **Usar a mesma trait de escopo da edição.** A regra existente já diferencia administrador global, administração principal e administrações adicionais. Reutilizá-la evita que contagem, exclusão e edição tenham interpretações diferentes do escopo. A alternativa de copiar a montagem dos IDs para o serviço foi rejeitada para não criar duas políticas paralelas.

3. **Falhar antes da operação de produto.** A busca da igreja será substituída por uma resolução que valida o ID e retorna a entidade autorizada; a contagem e a exclusão só continuarão após essa etapa. No controlador, a exceção de escopo será convertida em 403 para JSON e em redirecionamento com mensagem de erro para a exclusão, preservando os contratos atuais.

4. **Manter a compatibilidade de testes sem sessão.** A trait continua tratando a ausência completa de marcadores de escopo como fluxo global legado, enquanto uma sessão explicitamente restrita sem IDs autorizados não terá acesso. Isso mantém consumidores de teste e fluxos antigos compatíveis sem abrir uma sessão restrita.

## Risks / Trade-offs

- **[Risco]** Chamadas de contagem agora podem retornar 403 para IDs fora do escopo. → **Mitigação:** esse é o contrato de segurança esperado e será coberto por teste de integração sem alteração de dados.
- **[Risco]** Um serviço mockado nos testes pode não reproduzir a validação real. → **Mitigação:** adicionar testes unitários do serviço real e testes HTTP do controlador com a implementação real para os casos de autorização.
- **[Risco]** Exceções de escopo podem revelar detalhes internos se forem propagadas sem tratamento. → **Mitigação:** usar a mensagem fixa existente e não incluir detalhes de consulta ou banco.

## Migration Plan

Nenhuma migração de dados. Após a implementação, executar os testes focados, a suíte PHPUnit, o lint PHP e as sondas de saúde. O rollback consiste em reverter o commit, sem alteração persistida no banco.

## Context

A listagem já pede ao serviço a lista de igrejas usando os filtros de administração e estado, mas o controlador mantém o identificador informado na consulta ou na sessão sem compará-lo com essa lista. A implementação deve preservar o contrato atual do serviço e não introduzir uma nova consulta ao banco.

## Goals / Non-Goals

**Goals:**

- Calcular a lista filtrada de igrejas uma única vez por requisição.
- Considerar a igreja selecionada válida somente quando seu identificador estiver nessa lista.
- Reaproveitar a seleção normalizada para decidir se os relatórios serão carregados.
- Manter a mensagem de estado vazio já existente quando nenhuma igreja for selecionada.

**Non-Goals:**

- Alterar o contrato ou a implementação do serviço de relatórios.
- Alterar permissões, rotas, filtros de produtos ou dados persistidos.
- Fazer redirecionamento automático ou adicionar nova chamada HTTP.

## Decisions

### Validar contra as opções renderizadas

O controlador armazenará o resultado de `churchOptions($administrationId, $state)` e verificará a seleção efetiva com uma busca pelo identificador nessa coleção. Essa é a mesma fonte de dados que será renderizada no seletor, evitando divergência entre o que o usuário pode escolher e o que pode carregar.

Alternativa considerada: aceitar qualquer identificador positivo e deixar o serviço de relatórios validar depois. Essa abordagem permite que a página mantenha uma igreja invisível aos filtros e pode carregar dados fora do escopo visual; por isso foi descartada.

### Normalizar a seleção antes do carregamento

Quando a seleção não existir nas opções filtradas, o controlador definirá `selectedChurchId` como `null`. A expressão que carrega relatórios usará esse valor normalizado, garantindo que a rejeição ocorra antes de chamar `listAvailableReports`.

Alternativa considerada: manter o identificador original e apenas ocultar os relatórios na view. Isso ainda executaria uma consulta desnecessária e deixaria o estado do formulário incoerente.

### Cobertura por serviço substituído

Os testes de feature substituirão o contrato do serviço por um stub controlável, permitindo provar a seleção válida, a seleção incompatível e a ausência de chamada de relatórios sem depender do banco de produção.

## Risks / Trade-offs

- [A coleção de igrejas pode conter objetos com tipos variados] → comparar o identificador como inteiro, aceitando o formato já retornado pelo serviço.
- [Uma igreja da sessão deixa de estar disponível] → tratar como seleção nula e exibir o estado vazio, sem redirecionar o usuário.
- [A lista filtrada precisa ser usada na view e na validação] → guardar a coleção localmente e passá-la à view, evitando nova consulta.

## Migration Plan

Nenhuma migração de dados é necessária. A publicação é compatível com as rotas existentes; em caso de regressão, o rollback consiste em restaurar o controlador e o teste anteriores.

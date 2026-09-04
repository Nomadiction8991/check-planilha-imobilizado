# churches Specification

## Purpose
TBD - created by archiving change filtro-igrejas-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtro por administração na listagem de igrejas
O sistema SHALL permitir filtrar a listagem de igrejas por administração selecionada (`administracao_id`), além da busca por texto.

#### Scenario: Filtragem com administração selecionada
- GIVEN que existem igrejas pertencentes a diferentes administrações
- WHEN o usuário submete a listagem de igrejas com o parâmetro `administracao_id`
- THEN apenas as igrejas vinculadas à administração informada DEVEM ser retornadas

#### Scenario: Busca em tempo real de administrações no select
- GIVEN a presença do select de administrações na listagem de igrejas
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select DEVEM ser filtradas instantaneamente sem recarregar a página

### Requirement: Filtro de Igrejas por Estado
O sistema SHALL permitir que o usuário filtre as igrejas cadastradas por Unidade Federativa (UF/Estado) na tela de listagem de igrejas.

#### Scenario: Filtragem por estado específico
- GIVEN que existem igrejas cadastradas com estados diferentes (ex.: SP e PR)
- WHEN o usuário acessa a listagem com o parâmetro `estado=PR`
- THEN apenas as igrejas com `estado = 'PR'` devem ser exibidas na listagem
- AND o seletor de estado deve manter a opção 'PR' selecionada.

#### Scenario: Listagem sem filtro de estado
- GIVEN que o usuário não seleciona nenhum estado (ou seleciona "Todos os estados")
- WHEN a listagem de igrejas for carregada
- THEN todas as igrejas devem ser consideradas, respeitando os demais filtros ativos.

### Requirement: Restrição de escopo na edição de igrejas

Ao atualizar uma igreja, o sistema SHALL aplicar a regra de escopo administrativo vigente: usuários restritos só podem editar igrejas de sua administração autorizada e só podem reatribuir para administrações autorizadas; fora do escopo a operação SHALL ser rejeitada com mensagem `A igreja selecionada está fora do seu escopo permitido.` ou `A administração selecionada está fora do seu escopo permitido.` sem alterar dados.

#### Scenario: Edição de igreja fora do escopo é rejeitada

- WHEN a igreja alvo pertence a administração fora do escopo do usuário restrito
- THEN a edição SHALL ser rejeitada com status de erro e o registro permanece inalterado

#### Scenario: Troca de administração fora do escopo é rejeitada

- WHEN a nova administração informada está fora do escopo do usuário restrito
- THEN a edição SHALL ser rejeitada com status de erro e o registro permanece inalterado

### Requirement: Atualização automática dos filtros de igrejas
A tela de igrejas SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL manter as buscas auxiliares e o envio manual disponíveis.

#### Scenario: Alteração de filtro atualiza a listagem
- **GIVEN** a pessoa está na listagem de igrejas
- **WHEN** ela altera a administração, o estado ou a busca geral
- **THEN** a tela envia automaticamente a consulta com os valores atuais
- **AND** reinicia a consulta na primeira página

#### Scenario: Busca auxiliar não é enviada ao servidor
- **GIVEN** a tela oferece uma busca local de administrações para localizar uma opção do select
- **WHEN** a pessoa digita nessa busca auxiliar
- **THEN** somente as opções do select são filtradas no navegador
- **AND** a busca auxiliar não é enviada como parâmetro da listagem

#### Scenario: Limpeza da busca geral atualiza os resultados
- **GIVEN** a busca geral contém um valor
- **WHEN** a pessoa usa o controle nativo para limpar o campo
- **THEN** a tela envia automaticamente a consulta sem a busca

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente sem duplicidade automática

### Requirement: Ações de produtos da igreja respeitam o escopo

O sistema SHALL validar o escopo administrativo da igreja antes de contar ou excluir seus produtos. Usuários restritos SHALL receber uma resposta de erro sem executar a ação quando a igreja não pertencer à administração principal ou adicional autorizada, enquanto administradores globais SHALL manter o acesso.

#### Scenario: Contagem de produtos fora do escopo é rejeitada

- GIVEN um usuário restrito sem autorização para a administração da igreja
- WHEN ele solicita a contagem de produtos informando o identificador dessa igreja
- THEN o sistema SHALL responder com status HTTP 403 e a mensagem `A igreja selecionada está fora do seu escopo permitido.`
- AND a contagem SHALL não consultar nem expor produtos da igreja

#### Scenario: Exclusão de produtos fora do escopo é rejeitada

- GIVEN um usuário restrito sem autorização para a administração da igreja
- WHEN ele solicita a exclusão de produtos informando o identificador dessa igreja
- THEN o sistema SHALL redirecionar para a listagem com status de erro
- AND nenhum produto da igreja SHALL ser excluído

#### Scenario: Ações permanecem disponíveis para administrador global

- GIVEN um administrador global autenticado e uma igreja de qualquer administração
- WHEN ele solicita a contagem ou a exclusão de produtos da igreja
- THEN a ação SHALL seguir o fluxo existente com sucesso


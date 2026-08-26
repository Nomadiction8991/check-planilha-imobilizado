# relatorios-formulario Specification

## Purpose
TBD - created by archiving change exportar-csv-relatorios-formulario. Update Purpose after archive.
## Requirements
### Requirement: Download de CSV do formulário 14.1
O sistema SHALL oferecer um endpoint de download que gere o CSV do formulário
14.1 com todos os bens marcados para impressão da igreja selecionada, usando
BOM UTF-8, separador `;` e as mesmas descrições exibidas na prévia.

#### Scenario: Exportação com itens
- GIVEN uma igreja com bens marcados para o formulário 14.1
- WHEN o usuário solicita o download do CSV do formulário 14.1
- THEN a resposta é um arquivo CSV com cabeçalho e uma linha por bem,
      contendo código, condição, descrição original, descrição atual,
      dependência e dados da nota fiscal quando houver

#### Scenario: Formulário sem itens
- GIVEN uma igreja sem bens marcados para o formulário 14.1
- WHEN o usuário solicita o download do CSV do formulário 14.1
- THEN o sistema redireciona de volta para a lista de relatórios com
      mensagem amigável informando que não há itens para exportar

### Requirement: Download de CSV do formulário 14.6
O sistema SHALL oferecer um endpoint de download que gere o CSV do formulário
14.6 com todos os bens editados relevantes da igreja selecionada, comparando
descrição, tipo de bem e dependência antes e depois da edição.

#### Scenario: Exportação com itens editados
- GIVEN uma igreja com bens editados relevantes para o formulário 14.6
- WHEN o usuário solicita o download do CSV do formulário 14.6
- THEN a resposta é um arquivo CSV com uma linha por bem, contendo código,
      descrição original, descrição atual, tipo de bem e dependência
      (originais e editados)

#### Scenario: Sem edições relevantes
- GIVEN uma igreja sem bens com edições relevantes
- WHEN o usuário solicita o download do CSV do formulário 14.6
- THEN o sistema redireciona de volta para a lista de relatórios com
      mensagem amigável informando que não há itens para exportar

### Requirement: Nome de arquivo padronizado
O arquivo gerado SHALL seguir o padrão `relatorio_<formulario>_<codigo_igreja>_<data>_<hora>.csv`,
igual ao padrão já usado pelo backup da posição de verificação.

#### Scenario: Nome do arquivo inclui igreja e data
- GIVEN uma igreja de código `12-3456`
- WHEN o CSV do formulário 14.1 é gerado
- THEN o nome do arquivo começa com `relatorio_14.1_12-3456_` seguido de
      data e hora no formato `Ymd_His`

### Requirement: Controle de acesso e validação
O endpoint SHALL exigir a mesma permissão de visualização dos relatórios
(`reports.view`), validar o formulário informado e exigir igreja selecionada.

#### Scenario: Formulário inválido
- GIVEN um código de formulário inexistente
- WHEN o download é solicitado
- THEN o sistema responde com redirecionamento e mensagem de formulário
      inválido

#### Scenario: Igreja inexistente
- GIVEN um identificador de igreja que não existe
- WHEN o download é solicitado
- THEN o sistema responde com redirecionamento e mensagem de erro amigável

#### Scenario: Sem igreja selecionada
- WHEN o download é solicitado sem igreja selecionada
- THEN o sistema redireciona para a lista de relatórios pedindo a seleção

### Requirement: Botão na prévia do relatório
A prévia de um formulário com dados SHALL exibir um botão "Baixar CSV" que
aponta para o endpoint de download mantendo a igreja selecionada.

#### Scenario: Botão visível na prévia
- GIVEN a prévia do formulário 14.1 aberta para uma igreja
- WHEN a página é renderizada
- THEN o botão "Baixar CSV" aparece ao lado das ações existentes apontando
      para o endpoint com o parâmetro da igreja


## MODIFIED Requirements

### Requirement: Filtro por administração na seleção de relatórios

O sistema SHALL permitir filtrar a lista de congregações disponíveis na tela de relatórios por administração informada (`administracao_id`), além de fornecer as opções de administração para o formulário. Ao alterar administração, estado ou igreja no formulário, a tela SHALL submeter automaticamente uma única consulta GET preservando os demais critérios; a submissão manual pelo botão existente SHALL continuar disponível.

#### Scenario: Listagem de opções de administrações
- GIVEN que existem administrações cadastradas no banco
- WHEN o usuário acessa a tela de relatórios (`/reports`)
- THEN a view DEVE receber a lista de administrações ordenadas por descrição para permitir o filtro

#### Scenario: Filtragem dinâmica de administrações no select
- GIVEN a presença do select de administrações na tela de relatórios
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

#### Scenario: Select alterado atualiza a consulta automaticamente
- GIVEN o formulário de filtros está visível na tela de relatórios
- WHEN o usuário altera o seletor de administração, estado ou igreja
- THEN o navegador submete uma única consulta GET após a alteração, mantendo os demais campos preenchidos

#### Scenario: Submissão manual continua disponível
- WHEN o usuário aciona o botão "Carregar relatórios" ou utiliza o envio padrão do formulário
- THEN a consulta é submetida normalmente sem duplicar a requisição automática em andamento

#### Scenario: Feedback da atualização permanece reservado
- GIVEN o formulário foi renderizado antes de qualquer alteração
- WHEN o usuário altera um filtro que muda a consulta
- THEN a mensagem de atualização é exibida na região reservada de status, sem criar ou remover elementos durante o envio

## ADDED Requirements

### Requirement: Atualização automática dos filtros de relatórios

A tela de relatórios SHALL atualizar os resultados automaticamente quando um filtro enviado pelo servidor for alterado, sem submeter os campos de busca que servem apenas para filtrar opções localmente. A atualização SHALL manter os parâmetros atuais do formulário e evitar nova navegação quando a assinatura dos valores não mudar.

#### Scenario: Busca local não dispara consulta
- GIVEN o usuário está digitando no campo de busca de administração ou igreja
- WHEN o texto muda para filtrar as opções exibidas
- THEN a página filtra as opções localmente sem submeter o formulário

#### Scenario: Busca local restaura opções sem alterar resultados
- GIVEN o usuário limpou o campo de busca de administração ou igreja
- WHEN o controle local é atualizado
- THEN as opções permitidas voltam a ser exibidas e nenhuma consulta ao servidor é criada apenas pela limpeza

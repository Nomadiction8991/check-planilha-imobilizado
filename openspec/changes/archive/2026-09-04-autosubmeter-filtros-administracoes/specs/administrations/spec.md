## ADDED Requirements

### Requirement: Atualização automática dos filtros de administrações
A tela de administrações SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL manter o envio manual disponível.

#### Scenario: Alteração do estado atualiza a listagem
- **GIVEN** a pessoa está na listagem de administrações
- **WHEN** ela altera o estado selecionado
- **THEN** a tela envia automaticamente a consulta com o novo estado
- **AND** não exige um segundo toque no botão de filtragem

#### Scenario: Digitação da busca aguarda pausa
- **GIVEN** a pessoa está digitando uma busca por descrição, ID ou CNPJ
- **WHEN** ela continua digitando sem pausa
- **THEN** a tela não envia uma consulta a cada caractere
- **AND** envia a busca depois de uma breve pausa

#### Scenario: Limpeza da busca é reconhecida
- **GIVEN** a busca textual contém um valor
- **WHEN** a pessoa usa o controle nativo para limpar o campo
- **THEN** a tela envia automaticamente a consulta sem a busca

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente
- **AND** a tela não agenda uma segunda consulta para os mesmos valores

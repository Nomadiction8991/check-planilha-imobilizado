## ADDED Requirements

### Requirement: Atualização automática dos filtros de usuários
A tela de usuários SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL manter a busca auxiliar e o envio manual disponíveis.

#### Scenario: Alteração de filtro atualiza a listagem
- **GIVEN** a pessoa está na listagem de usuários
- **WHEN** ela altera administração, estado ou status
- **THEN** a tela envia automaticamente a consulta com os valores atuais
- **AND** reinicia a consulta na primeira página

#### Scenario: Digitação da busca aguarda pausa
- **GIVEN** a pessoa está digitando nome ou e-mail
- **WHEN** ela continua digitando sem pausa
- **THEN** a tela não envia uma consulta a cada caractere
- **AND** envia a busca depois de uma breve pausa

#### Scenario: Busca auxiliar de administração permanece local
- **GIVEN** a tela oferece uma busca auxiliar para localizar administrações
- **WHEN** a pessoa digita nessa busca
- **THEN** somente as opções do select são filtradas no navegador
- **AND** a busca auxiliar não é incluída na consulta

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente sem duplicidade automática

## ADDED Requirements

### Requirement: Atualização automática dos filtros de tipos de bem
A tela de tipos de bem SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL manter as buscas auxiliares e o envio manual disponíveis.

#### Scenario: Alteração de filtro atualiza a listagem
- **GIVEN** a pessoa está na listagem de tipos de bem
- **WHEN** ela altera a administração, o estado ou a busca geral
- **THEN** a tela envia automaticamente a consulta com os valores atuais
- **AND** reinicia a consulta na primeira página

#### Scenario: Busca auxiliar de administração permanece local
- **GIVEN** a tela oferece uma busca auxiliar para localizar administrações
- **WHEN** a pessoa digita nessa busca
- **THEN** somente as opções do select são filtradas no navegador
- **AND** a busca auxiliar não é incluída na consulta

#### Scenario: Limpeza da busca geral atualiza os resultados
- **GIVEN** a busca geral contém um valor
- **WHEN** a pessoa usa o controle nativo para limpar o campo
- **THEN** a tela envia automaticamente a consulta sem a busca

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente sem duplicidade automática

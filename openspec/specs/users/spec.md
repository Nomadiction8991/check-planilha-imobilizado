# users Specification

## Purpose
TBD - created by archiving change filtro-usuarios-estado. Update Purpose after archive.
## Requirements
### Requirement: Filtro de Usuários por Estado (UF)
O sistema SHALL permitir que o usuário filtre a listagem de usuários por Unidade Federativa (`endereco_estado`), preservando a paginação e os demais filtros de busca, status e administração.

#### Scenario: Filtragem por estado específico
- GIVEN usuários cadastrados no sistema com diferentes unidades federativas (`endereco_estado`)
- WHEN o usuário requisita a listagem enviando o parâmetro `estado=SP`
- THEN o sistema SHALL retornar apenas os usuários cujo `endereco_estado` seja igual a "SP"

#### Scenario: Sanitização do parâmetro de estado
- GIVEN uma requisição GET com parâmetro `estado=" rj "`
- WHEN o DTO `UserFilters` for construído a partir da requisição
- THEN a propriedade `state` SHALL ser convertida para "RJ" em maiúsculo e sem espaços

#### Scenario: Integração do filtro na interface de usuários
- GIVEN a visualização da tela de listagem de usuários
- WHEN a tela for renderizada
- THEN o seletor de estado (UF) SHALL exibir as opções de estados disponíveis e manter a seleção atual se informada

### Requirement: Formulário de usuário preserva a UF do endereço
O sistema SHALL renderizar o campo de UF do endereço usando o código da UF como valor enviado e o nome da UF como texto visível, selecionando o valor atual do cadastro ou o valor antigo informado quando o formulário for reexibido.

#### Scenario: Editar usuário com UF cadastrada
- **GIVEN** um usuário cujo endereço possui a UF `SP`
- **WHEN** a pessoa abre o formulário de edição
- **THEN** a opção `SP` deve ser selecionada
- **AND** o texto visível deve identificar São Paulo

#### Scenario: Reexibir formulário após erro de validação
- **GIVEN** a pessoa informou a UF `MT` e o formulário retornou com dados antigos
- **WHEN** o formulário é renderizado novamente
- **THEN** a opção `MT` deve permanecer selecionada
- **AND** o campo deve continuar enviando `MT` como valor

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

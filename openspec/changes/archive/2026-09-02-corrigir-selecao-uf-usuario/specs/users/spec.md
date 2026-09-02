## MODIFIED Requirements

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

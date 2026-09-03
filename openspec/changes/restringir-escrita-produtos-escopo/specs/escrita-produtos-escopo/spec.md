## Purpose

Garante que alterações no inventário só atinjam produtos pertencentes às administrações que o usuário pode acessar, inclusive quando a requisição é montada fora da interface.

## ADDED Requirements

### Requirement: Operações de produto respeitam o escopo administrativo

O sistema SHALL verificar o escopo administrativo da igreja associada ao produto antes de criar, editar ou atualizar um produto. Usuários administradores SHALL manter acesso global, enquanto usuários restritos SHALL poder operar somente em igrejas vinculadas às suas administrações permitidas.

#### Scenario: Usuário restrito cria produto em igreja permitida

- **GIVEN** um usuário restrito com a administração da igreja selecionada em seu escopo
- **WHEN** ele envia os dados válidos de um novo produto
- **THEN** o produto é criado normalmente

#### Scenario: Usuário restrito tenta criar produto fora do escopo

- **GIVEN** um usuário restrito sem acesso à administração da igreja informada
- **WHEN** ele envia os dados de um novo produto para essa igreja
- **THEN** a operação é rejeitada com uma mensagem de escopo e nenhum produto é criado

#### Scenario: Usuário restrito tenta editar produto fora do escopo

- **GIVEN** um produto pertencente a uma igreja fora das administrações permitidas
- **WHEN** o usuário envia uma atualização para esse produto
- **THEN** a operação é rejeitada com uma mensagem de escopo e os dados permanecem inalterados

### Requirement: Operações rápidas respeitam o escopo administrativo

O sistema SHALL verificar o escopo administrativo antes de salvar observações, marcações de conferência, etiquetas, assinaturas, limpeza de edições e sincronizações rápidas de produtos.

#### Scenario: Requisição rápida aponta para produto fora do escopo

- **GIVEN** um usuário restrito e um produto pertencente a uma administração não permitida
- **WHEN** ele envia uma operação rápida para esse produto
- **THEN** a operação é rejeitada sem alterar o produto

#### Scenario: Requisição rápida usa igreja permitida e produto da igreja

- **GIVEN** um usuário restrito com acesso à administração da igreja
- **WHEN** ele envia uma operação rápida para um produto ativo dessa igreja
- **THEN** a alteração é aplicada normalmente

#### Scenario: Administrador executa operação em qualquer igreja

- **GIVEN** um usuário administrador autenticado
- **WHEN** ele envia uma operação de produto para qualquer igreja existente
- **THEN** a operação é processada sem bloqueio de escopo

## ADDED Requirements

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

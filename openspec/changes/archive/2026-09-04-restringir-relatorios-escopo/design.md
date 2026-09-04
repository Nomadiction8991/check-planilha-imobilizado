## Context

A consulta de produtos já resolve administrações permitidas a partir da sessão, mas `LegacyReportService` carrega opções globais e os métodos de relatório recebem `comum_id` diretamente. Os controladores protegem a capacidade (`reports.view`), porém essa permissão não define a administração em que o usuário pode atuar. Ver `proposal.md` para a motivação e os deltas em `specs/` para o contrato observável.

## Goals / Non-Goals

**Goals:**

- Reutilizar a semântica atual da sessão: administrador global sem restrição; usuário restrito com a administração ativa somada às administrações permitidas; sessão explicitamente restrita sem IDs sem resultados.
- Restringir as opções de administração e igreja usadas pelos filtros de relatórios.
- Bloquear no serviço qualquer leitura de relatório iniciada com igreja fora do escopo, inclusive acessos diretos por URL e downloads.
- Manter mensagens de erro controladas e a compatibilidade dos formatos existentes.

**Non-Goals:**

- Alterar rotas, permissões, tabelas, migrations ou o formato dos relatórios.
- Reestruturar a autorização de outros módulos.
- Mudar a seleção local por texto ou o comportamento visual dos filtros.

## Decisions

### Escopo baseado na sessão do usuário

Adicionar ao serviço de relatórios uma resolução privada de IDs de administração baseada nas chaves de sessão já sincronizadas pelo bridge. A ausência de qualquer chave de escopo mantém compatibilidade com chamadas legadas e testes sem autenticação; quando a sessão se declara restrita, uma lista vazia significa nenhum acesso, evitando transformar uma sessão incompleta em acesso global.

Alternativa considerada: depender apenas de `LegacyReportController` ou do middleware de permissão. Rejeitada porque downloads e chamadas de serviço precisam da mesma proteção, e a permissão descreve a capacidade, não o escopo administrativo.

### Filtragem das opções no banco

Aplicar `whereIn('administracao_id', ...)` às consultas de administrações e igrejas antes do `get`, combinando com os filtros de administração e estado já existentes. Assim, a coleção usada pelo controlador também é a fonte de validação da seleção, sem carregar opções de outras administrações para o navegador.

Alternativa considerada: carregar tudo e filtrar somente no Blade. Rejeitada por vazar identificadores e nomes fora do escopo e por não proteger requisições manipuladas.

### Guarda antes de qualquer leitura de relatório

Os métodos públicos que carregam dados por igreja validarão o vínculo da igreja com uma administração permitida antes de consultar dados de formulário. A guarda lançará `RuntimeException` com mensagem de escopo para o controlador preservar o redirecionamento amigável já existente. O administrador global seguirá diretamente para o fluxo atual.

Alternativa considerada: validar somente `buildReportPreview`. Rejeitada porque posição de estoque, histórico e exportações têm entradas públicas independentes.

## Risks / Trade-offs

- [Risco] Sessões antigas podem não ter IDs permitidos. → [Mitigação] Preservar o modo legado quando nenhuma chave de escopo existe e bloquear quando a sessão se declara restrita sem IDs.
- [Risco] Uma administração ou igreja pode ser removida entre a montagem das opções e a requisição seguinte. → [Mitigação] Revalidar a igreja no serviço imediatamente antes da leitura; o resultado passa pelo tratamento de `RuntimeException` existente.
- [Risco] Testes com builders estáticos podem exigir expectativas adicionais. → [Mitigação] Cobrir a filtragem com teste isolado do modelo e manter as chamadas fora do caminho global quando não há sessão restrita.

## Migration Plan

Nenhuma migration ou ajuste de dados. Após o deploy, validar filtros e uma prévia com usuário restrito e administrador. O rollback consiste em reverter o commit, sem alterar sessões nem banco.
# Design: Filtro por Administração na Auditoria

## Arquitetura & Fluxo
1. **Camada de Serviço (`LegacyAuditTrailServiceInterface` e `LegacyAuditTrailService`)**:
   - Adicionar chave `'administracao_id'` no array `$filters` suportado por `paginate()` e `exportCsv()`.
   - Na filtragem das entradas (`filter()`), se o usuário for administrador (`$isAdmin === true`) e houver `$filters['administracao_id']` válido (> 0), filtrar apenas eventos com `entry->administrationId === (int) $filters['administracao_id']`. Se o usuário não for administrador, prevalece o escopo já restrito do usuário.
   - Adicionar método ou consulta de opções de administrações para preencher o select, ou permitir injeção direta de `Administracao::query()->orderBy('descricao')->get(['id', 'descricao'])` via controller / browser service.

2. **Camada de Controller (`LegacyAuditController`)**:
   - Obter `administracao_id` da requisição GET, limpando e validando como inteiro positivo quando presente.
   - Incluir `'administracao_id'` em `$filters`.
   - Passar `$administrations` para a view `audits.index`.
   - Passar `'administracao_id'` na rota de exportação CSV caso esteja definido.

3. **Camada de Visão (`resources/views/audits/index.blade.php`)**:
   - Inserir campos de busca de administração (`data-audits-admin-search`) e select (`data-audits-admin-select`) na barra de filtros.
   - Script JS com listener `input` para busca rápida case-insensitive e atualização de aria-live / status (`data-audits-admin-status`), compatível com mobile e desktop.

4. **Testes**:
   - Teste unitário no serviço verificando filtro por `administracao_id`.
   - Teste de feature no controller cobrindo paginação e exportação CSV com `administracao_id`.

# Erros Encontrados - Check Planilha Imobilizado

## Resumo
- Críticos: 0
- Altos: 4
- Médios: 1
- Baixos: 0

## Erros por Categoria
### Segurança (0)
### Lógica (3)
### Performance (0)
### Code Smell (0)

## Detalhes dos Erros

### LEG-001 - Reconhecer admin pela sessão nas listagens

**Arquivo**: `resources/views/administrations/index.blade.php` (linha 7)
**Severidade**: 🟠 ALTO
**Categoria**: Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: As listagens administrativas dependiam só de `legacySessionUser['is_admin']`. Quando o composer do layout retornava sessão parcial ou `legacySessionUser` vinha nulo, o usuário administrador perdia botões de criar e editar em várias telas.

**Risco/Impacto**: A interface ficava inconsistente e o administrador não conseguia acessar ações que deveriam estar liberadas.

**Recomendação**: Usar um fallback explícito com `session('is_admin')` nas telas de listagem ou normalizar a sessão no composer. Correção aplicada nas views afetadas.

**Padrão Recorrente**: Sim
**Prioridade de Correção**: 🚨 Imediata

### IMP-001 - Gravar igreja da importação como nulo

**Arquivo**: `app/Http/Requests/StoreSpreadsheetImportRequest.php` (linha 49)
**Severidade**: 🟠 ALTO
**Categoria**: Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: A importação multi-igreja estava enviando `churchId = 0` para `importacoes.comum_id`. Como essa coluna tem chave estrangeira para `comums.id`, o insert falha ou grava um valor inválido em produção quando não há igreja base.

**Risco/Impacto**: A análise da planilha não avança e o fluxo de importação quebra assim que tenta registrar a importação.

**Recomendação**: Persistir `comum_id` como `NULL` quando não existir igreja base e continuar usando `0` apenas como fallback interno no parser.

**Padrão Recorrente**: Sim
**Prioridade de Correção**: 🚨 Imediata

### LEG-002 - Troca de igreja sem validação de escopo do usuário

**Arquivo**: `app/Services/LegacyAuthSessionService.php` (linha 124-136) e `app/Http/Controllers/LegacyRouteCompatibilityController.php` (linha 429-431)
**Severidade**: 🟠 ALTO
**Categoria**: Segurança / Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: O método `switchChurch()` aceita qualquer `comum_id` existente e grava diretamente em sessão. O endpoint compatível `usersSelectChurch()` usa o mesmo fluxo sem validar se a igreja selecionada pertence ao escopo permitido do usuário autenticado. Como o restante do sistema usa `session('comum_id')` para filtrar listagens, um usuário autenticado pode pivotar o contexto para outra igreja sem restrição adicional.

**Código Atual**:
```php
public function switchChurch(int $churchId): void
{
    if ($churchId <= 0) {
        throw new RuntimeException('Igreja inválida.');
    }

    /** @var Comum|null $church */
    $church = Comum::query()->find($churchId);
    if ($church === null) {
        throw new RuntimeException('Igreja não encontrada.');
    }

    /** @var Usuario|null $user */
    $user = Usuario::query()->find((int) Session::get('usuario_id', 0));
    if ($user === null) {
        throw new RuntimeException('Sessão inválida.');
    }

    if (!$user->isAdministrator() && (int) $user->comum_id !== $churchId) {
        throw new RuntimeException('Igreja fora do escopo permitido.');
    }

    Session::put('comum_id', $churchId);
}
```

**Risco/Impacto**: A sessão passa a apontar para uma igreja arbitrária. Em telas e ações que confiam apenas no `comum_id` da sessão, isso pode expor dados de outra unidade ou permitir operações fora do escopo pretendido.

**Recomendação**:
```php
Session::put('comum_id', $churchId);
```

**Padrão Recorrente**: Não
**Prioridade de Correção**: 🚨 Imediata

### LEG-003 - Logout público exposto via GET sem proteção CSRF

**Arquivo**: `routes/web.php` (linha 21-23) e `app/Http/Controllers/PublicAccessController.php` (linha 59-68)
**Severidade**: 🟡 MÉDIO
**Categoria**: Segurança / Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: O endpoint `/logout-publico` executa alteração de sessão via `GET`. Isso permite que qualquer navegação, pré-carregamento ou requisição cruzada dispare o logout sem intenção explícita do usuário. Por ser uma ação com efeito colateral, o método deveria exigir `POST` e token CSRF.

**Código Atual**:
```php
Route::post('/logout-publico', [PublicAccessController::class, 'logout'])->name('public.access.logout');
```

**Risco/Impacto**: O usuário pode perder o contexto público inesperadamente, o que quebra o fluxo e abre margem para CSRF por navegação involuntária.

**Recomendação**: Manter a rota em `POST` com proteção CSRF do grupo `web` e atualizar qualquer formulário/link que ainda chame o endpoint por `GET`.

**Padrão Recorrente**: Não
**Prioridade de Correção**: ⚠️ Próxima Sprint

### AUD-001 - Auditoria gravando em diretório não gravável

**Arquivo**: `app/Services/LegacyAuditTrailService.php` (linha 20)
**Severidade**: 🟠 ALTO
**Categoria**: Lógica / Operação
**Status**: Corrigido nesta rodada.
**Descrição**: O log de auditoria era apontado para `storage/app/private/audits/audit-log.jsonl`, mas o diretório e o arquivo estavam com dono `root` e sem permissão de escrita para o processo da aplicação. Como o serviço engolia falhas em `record()`, a tela mostrava apenas o histórico já existente no arquivo, dando a impressão de dados fictícios.

**Risco/Impacto**: Nenhum evento novo era persistido, então a página de auditoria ficava desatualizada e enganosa.

**Recomendação**: Resolver o storage para um caminho realmente gravável com fallback seguro e evitar falha silenciosa no writer. Correção aplicada no serviço.

**Padrão Recorrente**: Sim
**Prioridade de Correção**: 🚨 Imediata

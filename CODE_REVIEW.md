# RELATÓRIO DE REVISÃO COMPLETA DE CÓDIGO
## Projeto: check-planilha-imobilizado-ccb
**Data:** 09/03/2026 | **Revisor:** Claude Code (Clean Code Mentor)

---

## 1. VISÃO GERAL DO PROJETO

**Linguagem:** PHP 8.x (com `declare(strict_types=1)` em todos os arquivos principais)

**Framework:** Sem framework externo — arquitetura MVC artesanal com front controller customizado.

**Dependências principais:**
- `league/csv` para parsing de CSV
- `phpoffice/phpspreadsheet` para planilhas
- `robmorgan/phinx` para migrations
- `setasign/fpdi` para PDF

**Propósito do sistema:** Sistema de gestão e conferência de bens imobilizados (patrimônio) de igrejas da CCB (Congregação Cristã no Brasil). Permite importar planilhas CSV do sistema de imobilizado, conferir itens fisicamente, gerar relatórios 14.1 e gerenciar usuários por congregação ("comum").

**Infraestrutura:** Docker com Apache + PHP, MySQL, HTTPS local com certificado autoassinado.

---

## 2. ESTRUTURA DE ARQUIVOS E PASTAS

```
/
├── public/              # Front controller + assets estáticos
│   ├── index.php        # Entry point (roteador)
│   └── assets/          # CSS/JS por módulo
├── src/
│   ├── Contracts/       # Interfaces (RepositoryInterface, AuthServiceInterface)
│   ├── Controllers/     # 10 controllers
│   ├── Core/            # Infraestrutura (ConnectionManager, SessionManager, ViewRenderer, CsrfService)
│   ├── Exceptions/      # Exceções de domínio
│   ├── Helpers/         # Funções globais + helpers OOP
│   ├── Middleware/       # AuthMiddleware
│   ├── Repositories/    # Camada de dados
│   ├── Routes/          # Mapa de rotas
│   ├── Services/        # Lógica de negócio
│   └── Views/           # Templates PHP
├── config/              # Bootstrap, database, app_config
├── database/migrations/ # Phinx migrations
├── scripts/             # Scripts utilitários
├── storage/             # Logs, arquivos temporários, uploads
│   ├── importation/     # CSVs importados
│   └── tmp/             # Análises JSON + arquivo de sessão
└── docs/                # Documentação técnica extensa
```

A organização é coerente e bem segmentada. O ponto negativo é que `storage/tmp/` contém dados de sessão e arquivos JSON de análise que estão sendo rastreados pelo git.

---

## 3. PROBLEMAS CRÍTICOS (ALTA SEVERIDADE)

### 3.1 Arquivo `.env` com credenciais no repositório (SEGURANÇA)
**Arquivo:** `/.env` | **Linhas:** 1-4

O `.env` está no `.gitignore` e NÃO está rastreado pelo git neste momento — porém o arquivo físico existe localmente com credenciais reais (`DB_PASS=checkplanilha123`). O risco é que qualquer `git add .` acidental o cometa. Adicionalmente, o `.env.example` expõe o esquema real das variáveis de ambiente.

**Princípio violado:** Pragmatic Programmer — "Never store secrets in version control."

**Recomendação:** Adicionar um `.env.production` ao `.gitignore` separadamente e usar um mecanismo de validação no bootstrap que lance exceção clara se variáveis obrigatórias estiverem ausentes.

---

### 3.2 Criação de diretórios com permissão `0777` (SEGURANÇA)
**Arquivo:** `src/Services/CsvParserService.php` linha 958 e `src/Controllers/PlanilhaController.php` linha 92

```php
mkdir($dir, 0777, true);
mkdir($dirImportacao, 0777, true);
```

Permissão `0777` em servidor Linux concede escrita a qualquer usuário do sistema, podendo ser explorada para sobrescrever arquivos de upload por outros processos.

**Correção:** Usar `0755` para diretórios que o webserver precisa ler, ou `0750` com grupo adequado.

---

### 3.3 Injeção de dependência manual — Service Locator via `ConnectionManager::getConnection()` (ARQUITETURA)
**Arquivos:** Todos os Controllers e Services

```php
// PlanilhaController.php linha 26
$conexao = $conexao ?? ConnectionManager::getConnection();
// ImportacaoService.php linha 36
$this->conexao = $conexao ?? ConnectionManager::getConnection();
```

`ConnectionManager` é um Singleton/Service Locator estático. Todos os serviços o chamam diretamente como fallback, violando o **Dependency Inversion Principle** (DIP). Isso cria acoplamento oculto e torna testes unitários impraticáveis sem mock do estado global.

---

### 3.4 SQL raw dentro de controller — violação de separação de responsabilidades (ARQUITETURA CRÍTICA)
**Arquivo:** `src/Controllers/PlanilhaController.php` | **Linhas:** 453-460

```php
$conexao = \App\Core\ConnectionManager::getConnection();
$stmtErros = $conexao->prepare(
    'SELECT COUNT(*) FROM import_erros ie
      JOIN importacoes imp ON ie.importacao_id = imp.id
     WHERE imp.comum_id = :comum_id AND ie.resolvido = 0'
);
$stmtErros->execute([':comum_id' => $comumId]);
$errosPendentes = (int) $stmtErros->fetchColumn();
```

Um controller não deve nunca escrever SQL. Esse código deveria estar no `ErrosImportacaoRepository`. Viola o **Single Responsibility Principle** (SRP) e o **Dependency Rule** da Clean Architecture.

---

### 3.5 SQL raw dentro de controller no método `clearEdits` (ARQUITETURA CRÍTICA)
**Arquivo:** `src/Controllers/ProdutoController.php` | **Linhas:** 597-618

```php
$sql = "UPDATE produtos
    SET editado_tipo_bem_id = 0,
        editado_bem = '',
        ...
        editado = 0
    WHERE id_produto = :id_produto
        AND comum_id = :comum_id";
$stmt = $this->conexao->prepare($sql);
```

Um método de "limpar edições" é claramente responsabilidade do `ProdutoRepository`, não do controller.

---

### 3.6 Ausência total de testes automatizados (QUALIDADE CRÍTICA)

Nenhum arquivo de teste foi encontrado no projeto. Não existe diretório `tests/`, nenhuma suite PHPUnit, nenhum teste de unidade, integração ou caracterização.

> *"Code without tests is legacy code."* — Michael Feathers

A lógica de importação CSV em `CsvParserService.php` (1001 linhas) é complexa o suficiente para justificar dezenas de testes. O método `parsearNome()` (linhas 445-597) implementa um algoritmo de parsing em múltiplos passos sem nenhuma rede de segurança.

---

### 3.7 Arquivo de sessão PHP em `storage/tmp/` (SEGURANÇA/PRIVACIDADE)
**Arquivo:** `storage/tmp/sess_fc4ecb0eeaec5d6af902d925e0664673`

Um arquivo de sessão real está no diretório `storage/tmp/`. O PHP está salvando sessões nesse diretório em vez do diretório padrão do sistema, o que aumenta o risco de colisão de nomes e vazamento de dados.

---

## 4. PROBLEMAS MODERADOS (MÉDIA SEVERIDADE)

### 4.1 Classe `CsvParserService` com 1001 linhas — God Class (SOLID/SRP)
**Arquivo:** `src/Services/CsvParserService.php`

Esta classe faz tudo: leitura de CSV, detecção de encoding, detecção de delimitador, parsing de nomes, análise comparativa contra o banco, persistência de análise em JSON, e carregamento de dados do banco.

**Responsabilidades identificadas (pelo menos 6 distintas):**
1. Leitura/encoding de arquivo CSV
2. Detecção de metadados e início dos dados
3. Parsing do campo "Nome" (algoritmo complexo)
4. Comparação CSV vs. banco (diff)
5. Persistência da análise (JSON em disco)
6. Carregamento de dados do banco (Identity Map)

**Refatoração sugerida (Fowler — Extract Class):**
- `CsvReader` — leitura, detecção, encoding
- `NomeParser` — algoritmo de parsing do campo nome
- `ImportacaoAnalyser` — lógica de diff CSV vs. banco
- `AnaliseStorage` — persistência JSON

---

### 4.2 `ImportacaoService` (733 linhas) mistura dois fluxos distintos com código duplicado (DRY/SRP)
**Arquivo:** `src/Services/ImportacaoService.php`

O serviço contém dois fluxos paralelos quase idênticos: o fluxo moderno (`processarComAcoes`) e o fluxo legado (`processar`). A lógica de `buscarOuCriarTipoBem`, `buscarOuCriarDependencia`, `buscarOuCriarComum` e `atualizarProduto` está duplicada conceitualmente entre os dois fluxos.

---

### 4.3 `UsuarioController` usa `require` direto para views em vez de `ViewRenderer` (INCONSISTÊNCIA)
**Arquivo:** `src/Controllers/UsuarioController.php` | **Linhas:** 350-382

```php
private function renderizarListagemLegada(array $dados): void
{
    extract($dados);
    $conexao = ConnectionManager::getConnection(); // Service Locator dentro de view!
    require __DIR__ . '/../Views/users/list.php';
}
```

Três problemas:
1. Uso de `require` direto em vez de `ViewRenderer::render()` — inconsistente com o resto do projeto
2. `extract($dados)` sem sanitização — polui o escopo local com variáveis imprevisíveis
3. Injetar `$conexao` na view — views não devem ter acesso a banco de dados

---

### 4.4 Nomenclatura inconsistente — `filtro_STATUS` em maiúsculas misturada com `status` e `filtro_status`
**Arquivos:** `ProdutoController.php`, `ProdutoRepository.php`, Views e `GlobalFunctions.php`

```php
// ProdutoController.php linha 76
'filtro_STATUS' => trim($this->query('status', $this->query('filtro_STATUS', ''))),
// ProdutoController.php linha 586
$status = $this->query('STATUS', $this->query('status', ''));
// Views/products/clear-edits.php linha 37-38
'status' => $filtro_STATUS,
'STATUS' => $filtro_STATUS,
```

Mesmo parâmetro com três grafias distintas: `status`, `STATUS`, `filtro_STATUS`. Viola "Meaningful Names" (Clean Code, Cap. 2).

---

### 4.5 `UsuarioController::create()` chama `$this->store()` internamente ao detectar POST (DESIGN SMELL)
**Arquivo:** `src/Controllers/UsuarioController.php` | **Linhas:** 75-80

```php
public function create(): void
{
    if ($this->isPost()) {
        $this->store();  // chama outro método público internamente
        return;
    }
    // ...
}
```

Uma action que delega para outra action internamente — quando a rota `POST /users/create` já existe e é mapeada para `store()` diretamente. Viola o princípio de menor surpresa.

---

### 4.6 Lógica de negócio em `ProdutoController::store()` em vez de `ProdutoService` (SRP)
**Arquivo:** `src/Controllers/ProdutoController.php` | **Linhas:** 115-200

O controller coleta dados, valida, e persiste diretamente via `produtoRepository->criar()`. Existe uma `ProdutoService.php` que é completamente ignorada neste controller. A validação de campos obrigatórios, a normalização de `condicao_14_1`, e o loop de criação com multiplicador são lógica de domínio que deveria estar no serviço.

---

### 4.7 `BaseRepository::filterColumns()` — proteção silenciosa que pode mascarar bugs
**Arquivo:** `src/Repositories/BaseRepository.php` | **Linhas:** 137-144

```php
private function filterColumns(array $dados): array
{
    if (empty($this->colunas)) {
        return $dados; // sem whitelist = aceita tudo
    }
    return array_intersect_key($dados, array_flip($this->colunas));
}
```

Quando `$this->colunas` está vazio, qualquer dado é aceito sem filtragem. A proteção é opt-in, não opt-out, invertendo o princípio de Secure by Default.

---

### 4.8 `atualizarProduto()` em `ImportacaoService` constrói SQL dinâmico sem whitelist
**Arquivo:** `src/Services/ImportacaoService.php` | **Linhas:** 479-492

```php
private function atualizarProduto(int $id, array $dados): void
{
    $sets = [];
    foreach ($dados as $campo => $valor) {
        $sets[] = "$campo = :$campo"; // campo vem diretamente do array
        $params[":$campo"] = $valor;
    }
    $sql = "UPDATE produtos SET " . implode(', ', $sets) . " WHERE id_produto = :id";
```

Os nomes de colunas vêm do array `$dados` sem validação. Se um atacante pudesse influenciar as chaves desse array (ex.: via CSV malformado), poderia injetar nomes de colunas arbitrários no SQL.

---

### 4.9 `contarLinhasArquivo()` não fecha o arquivo em caso de exceção
**Arquivo:** `src/Services/ImportacaoService.php` | **Linhas:** 510-525

```php
private function contarLinhasArquivo(string $caminho): int
{
    $arquivo = fopen($caminho, 'r');
    // sem verificação de $arquivo !== false
    fgets($arquivo); // pula cabeçalho
    while (!feof($arquivo)) {
        if (fgets($arquivo)) { $linhas++; }
    }
    fclose($arquivo);
    return $linhas;
}
```

Se `fopen` falhar (retorna `false`), `fgets(false)` gerará um TypeError. Não há verificação de `$arquivo !== false` antes do uso. Padrão correto seria verificar o retorno e usar `try/finally`.

---

### 4.10 Lógica de `buscarOuCriarComum` cria entidades automaticamente sem confirmação (DOMÍNIO)
**Arquivo:** `src/Services/ImportacaoService.php` | **Linhas:** 408-433

O serviço cria automaticamente uma `comum` no banco se ela não existir, sem que o usuário seja avisado ou confirme. Uma nova "Comum" criada com descrição genérica `'Comum ' . $codigoComum` pode poluir o banco com dados incorretos se o CSV contiver um código desconhecido.

---

## 5. PROBLEMAS MENORES (BAIXA SEVERIDADE)

### 5.1 Comentários que descrevem "o que" em vez de "o porquê"
**Arquivos:** Vários em `PlanilhaController.php`, `CsvParserService.php`

```php
// Carrega dados da importação  ← descreve o óbvio
$importacao = $this->importacaoService->buscarProgresso($importacaoId);
```

Clean Code (Cap. 4): "A comment is a failure to express yourself in code."

---

### 5.2 Constantes mágicas espalhadas
**Arquivo:** `PlanilhaController.php` linha 437, `BaseRepository.php` linhas 26, 37

```php
$itensPorPagina = 20; // magic number
```

O valor `20` deveria ser uma constante de classe ou configuração. Aparece em múltiplos lugares com valores diferentes (10 no `UsuarioController`, 20 no `PlanilhaController`).

---

### 5.3 Nome de tabela `comums` (inglês incorreto)
**Arquivos:** `ImportacaoService.php` linha 415, `CsvParserService.php` linha 860 e vários outros

```php
$stmt = $this->conexao->prepare("SELECT id FROM comums WHERE codigo = :codigo LIMIT 1");
```

`comums` não existe em inglês nem em português. Há inclusive uma função `detectar_tabela_comuns()` em `GlobalFunctions.php` que tenta ambos os nomes, confirmando que a inconsistência já causou problemas reais.

---

### 5.4 `ReflectionMethod::setAccessible(true)` em script de produção
**Arquivo:** `scripts/fix_bem_complemento.php` | **Linhas:** 48-49

```php
$refMethod = new ReflectionMethod(CsvParserService::class, 'parsearNome');
$refMethod->setAccessible(true);
```

O uso de Reflection para acessar métodos privados indica que o método `parsearNome` deveria ser `public` ou que deveria existir uma interface pública para reutilização.

---

### 5.5 Múltiplas views duplicadas para relatórios 14.1
**Pasta:** `src/Views/reports/`

Existem quatro arquivos diferentes para o mesmo relatório:
- `report-141.php`
- `report-141-new.php`
- `report-141-template.php`
- `report-141-v2.php`

E ainda arquivos HTML estáticos: `14-1.html`, `14.1.html`, `14.2.html` ... `14.8.html`. Indica histórico de experimentos não removidos — violação de "Dead Code" (Fowler).

---

### 5.6 Funções globais em `GlobalFunctions.php` marcadas como `@deprecated` mas ainda em uso
**Arquivo:** `src/Helpers/GlobalFunctions.php`

Funções como `isLoggedIn()`, `to_uppercase()`, `gerarParametrosFiltro()`, `getReturnUrl()` estão todas marcadas como `@deprecated`, porém o arquivo é carregado no bootstrap e provavelmente ainda utilizado em views legadas.

---

### 5.7 `UsuarioController` injeta `$conexao` diretamente na view `show.php`
**Arquivo:** `src/Controllers/UsuarioController.php` | **Linha:** 382

```php
$conexao = $this->conexao;
require __DIR__ . '/../Views/users/show.php';
```

A view recebe uma conexão PDO como variável disponível. Views não devem fazer queries.

---

## 6. ANÁLISE POR PRINCÍPIO

### SOLID

| Princípio | Status | Observação |
|---|---|---|
| **SRP** | VIOLADO | `CsvParserService` com 6+ responsabilidades; SQL em controllers |
| **OCP** | PARCIAL | `BaseRepository` extensível; mas `CsvParserService` exige modificação para novos tipos |
| **LSP** | APLICADO | Repositórios estendem `BaseRepository` corretamente |
| **ISP** | PARCIAL | `RepositoryInterface` expõe SQL (`string $where`) para consumidores da interface |
| **DIP** | VIOLADO | Controllers instanciam dependências diretamente; `ConnectionManager` como Service Locator |

---

### DRY (Don't Repeat Yourself)

**VIOLADO em vários pontos:**

1. Lógica de construção de query string de filtros duplicada em `ProdutoController::update()` (linhas 330-344) e `ProdutoController::observacao()` (linhas 472-484)

2. O padrão `if ($comumId === null) return;` aparece 15+ vezes nos controllers — deveria ser tratado como exceção no middleware

3. A lógica de normalização de `condicao_14_1` está duplicada em `store()` linha 137 e `update()` linha 276

---

### KISS (Keep It Simple, Stupid)

O algoritmo `parsearNome()` em `CsvParserService` (linhas 445-597) é genuinamente complexo por necessidade do domínio. Porém a abordagem de múltiplas passes com `$bemEncontrado` como flag booleana, três blocos `if (!$bemEncontrado)` aninhados, e os loops de remoção de eco tornam o método difícil de seguir.

---

### YAGNI (You Aren't Gonna Need It)

Quatro versões do relatório 14.1, duas versões de `create-legacy.php` e `create.php` para usuários, e `users/list.php` coexistindo com `users/index.php` sugerem que código foi acumulado por medo de remover.

---

## 7. PONTOS POSITIVOS

### 7.1 Segurança CSRF implementada corretamente
`CsrfService` usa `hash_equals()` para comparação resistente a timing attacks, gera token com `random_bytes()`, e valida em todos os POSTs. Implementação correta e robusta.

### 7.2 Whitelist de colunas no `ProdutoRepository`
A lista `protected array $colunas` previne mass assignment ao restringir quais colunas podem ser inseridas/atualizadas. Boa prática de Defensive Programming.

### 7.3 Session management centralizado e seguro
`SessionManager` configura `httponly`, `secure` e `samesite=Lax` corretamente. `AuthService` chama `SessionManager::regenerate()` após autenticação para prevenir session fixation.

### 7.4 `declare(strict_types=1)` em todos os arquivos
Previne coerção implícita de tipos em PHP, forçando contratos de tipo mais rígidos.

### 7.5 Identity Map no `CsvParserService`
O pré-carregamento de produtos por comum (`carregarProdutosDoComum`) antes de processar o CSV evita N queries para cada linha. Boa decisão de performance.

### 7.6 Migrations versionadas com Phinx
O histórico de schema está versionado com migrations numeradas (`20260211`, `20260218`, `20260226`), permitindo reprodução do ambiente.

### 7.7 Validação de path traversal no `ViewRenderer`
```php
$sanitized = str_replace(['../', '..\\', "\0"], '', $name);
```
Previne ataques de path traversal ao renderizar views.

### 7.8 Autenticação com `password_verify` e `password_hash`
`AuthService` usa a API nativa do PHP para hash de senhas, sem algoritmos caseiros.

### 7.9 Soft delete implementado consistentemente
Produtos são desativados (`ativo = 0`) em vez de deletados, preservando histórico e evitando órfãos de chave estrangeira.

### 7.10 Documentação técnica rica
O diretório `docs/` contém 11 arquivos de documentação cobrindo arquitetura, decisões de migração, configuração HTTPS e planos de refatoração.

---

## 8. RECOMENDAÇÕES PRIORITÁRIAS

Ordenadas por impacto decrescente:

1. **[CRÍTICO] Adicionar testes automatizados** — Começar com testes de caracterização (Feathers) para `CsvParserService::parsearNome()` e `ImportacaoService::processarComAcoes()`. Ferramentas: PHPUnit + Mockery.

2. **[CRÍTICO] Mover SQL dos controllers para repositórios** — `PlanilhaController::visualizar()` (linhas 453-460) e `ProdutoController::clearEdits()` (linhas 597-618) devem delegar para repositórios. Criar `ErrosImportacaoRepository::contarPendentesPorComum()` e `ProdutoRepository::limparEdicoes()`.

3. **[ALTO] Corrigir permissões de diretório de 0777 para 0755** — Dois locais: `CsvParserService.php` linha 958 e `PlanilhaController.php` linha 92.

4. **[ALTO] Extrair `CsvParserService` em classes menores** — Pelo menos `NomeParser` e `AnaliseStorage` devem ser extraídos. Objetivo: cada classe abaixo de 250 linhas com responsabilidade única.

5. **[ALTO] Padronizar nomenclatura do filtro de status** — Escolher um nome (`filtro_status` em snake_case) e aplicar consistentemente em todos os controllers, repositories, views e query strings.

6. **[MÉDIO] Eliminar fallbacks para Service Locator** — O padrão `$conexao ?? ConnectionManager::getConnection()` deve ser eliminado. Controllers devem receber dependências já construídas.

7. **[MÉDIO] Remover código morto de views** — `report-141.php`, `report-141-new.php`, `report-141-template.php`, `report-141-v2.php`, `14-1.html` a `14.8.html`, `users/list.php`, `users/create-legacy.php` — identificar qual é o ativo e remover os demais.

8. **[MÉDIO] Adicionar whitelist ao `atualizarProduto()` do `ImportacaoService`** — Os nomes de colunas devem ser validados contra uma whitelist antes de compor o SQL dinâmico.

9. **[MÉDIO] Remover funções `@deprecated` do `GlobalFunctions.php`** — Migrar os chamadores para as classes OOP.

10. **[BAIXO] Padronizar nome da tabela `comums`** — Criar uma migration que renomeia para `comuns` e remover `detectar_tabela_comuns()`.

---

## 9. SCORE GERAL

### Nota: **6.0 / 10.0**

| Dimensão | Nota | Observação |
|---|---|---|
| Segurança | 6.5/10 | CSRF bom, session bom, mas 0777 e ausência de whitelist em ImportacaoService |
| Arquitetura | 5.5/10 | Camadas existem mas com furos (SQL em controllers, conexão em views) |
| Legibilidade | 6.5/10 | Nomes em português são consistentes; funções grandes prejudicam |
| Testabilidade | 2.0/10 | Zero testes; dependências hard-coded; Service Locator |
| DRY/KISS | 5.5/10 | Duplicações em filtros, dois fluxos de importação, views múltiplas |
| Boas Práticas PHP | 7.5/10 | strict_types, PSR-4, PDO parametrizado, password_hash correto |
| Documentação | 8.0/10 | Documentação técnica excelente no diretório docs/ |

**O que eleva a nota:** O projeto demonstra consciência arquitetural clara — há contratos (interfaces), separação em camadas, CSRF correto, sessão segura, migrations, e documentação técnica relevante.

**O que rebaixa a nota:** A ausência absoluta de testes é o problema mais grave. Além disso, as violações de SRP em classes centrais (`CsvParserService` com 1001 linhas, SQL em controllers) e a inconsistência de nomenclatura indicam que o projeto cresceu mais rápido que a disciplina de design conseguiu acompanhar.

**Caminho para 8.0+:** Adicionar testes automatizados (PHPUnit), mover SQL dos controllers para repositórios, extrair `CsvParserService` em classes menores, e remover o código morto das views de relatório.

---

**Arquivos mais críticos revisados:**
- `src/Services/CsvParserService.php`
- `src/Services/ImportacaoService.php`
- `src/Controllers/PlanilhaController.php`
- `src/Controllers/ProdutoController.php`
- `src/Controllers/UsuarioController.php`
- `src/Repositories/BaseRepository.php`
- `public/index.php`

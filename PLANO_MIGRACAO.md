# PLANO TÉCNICO DE MIGRAÇÃO INCREMENTAL
## Sistema Check Planilha Imobilizado CCB

**Data de Criação:** 11/02/2026  
**Versão:** 1.0  
**Responsável Técnico:** Equipe de Desenvolvimento  
**Prazo Estimado:** 16 semanas (~4 meses)

---

## 📋 SUMÁRIO EXECUTIVO

### Objetivo
Migrar gradualmente a aplicação da arquitetura legada (procedural misturada com MVC) para uma arquitetura limpa e moderna baseada em:
- **MVC + Service Layer + Repository Pattern**
- **Injeção de Dependências**
- **Rotas Centralizadas**
- **Testes Automatizados**

### Princípios da Migração
1. ✅ **Zero Downtime** - Sistema continua funcionando durante toda migração
2. ✅ **Backward Compatibility** - Código legado convive com novo até migração completa
3. ✅ **Test-Driven** - Testes criados ANTES de refatorar código crítico
4. ✅ **Incremental** - Pequenas mudanças validadas a cada etapa
5. ✅ **Reversível** - Cada fase pode ser revertida se necessário

### Situação Atual
- **Código Legado:** ~15.000 linhas em estrutura procedural/MVC híbrida
- **Migrado:** ~5% (apenas AuthController e login)
- **Pendente:** Controllers, Services, Repositories, Views, Rotas

---

## 🎯 ESTRATÉGIA DE MIGRAÇÃO

### Ordem Ideal de Migração (Bottom-Up Approach)

```
CAMADA 1: FUNDAÇÃO (Infraestrutura)
    ↓
CAMADA 2: ACESSO A DADOS (Repositories)
    ↓
CAMADA 3: LÓGICA DE NEGÓCIO (Services)
    ↓
CAMADA 4: CONTROLADORES (Controllers)
    ↓
CAMADA 5: APRESENTAÇÃO (Views)
    ↓
CAMADA 6: ROTEAMENTO (Routes)
    ↓
CAMADA 7: QUALIDADE (Otimizações, Segurança)
```

**Justificativa:**
- **Bottom-Up** evita retrabalho (camadas inferiores estáveis quando superiores são migradas)
- **Repositories primeiro** remove SQL de controllers/views
- **Services antes de Controllers** para controllers ficarem "magros"
- **Views por último** pois dependem de controllers refatorados
- **Rotas no final** para não quebrar URLs durante migração

---

## 📊 ANÁLISE DE DEPENDÊNCIAS OCULTAS

### Dependências Críticas Identificadas

#### 1. **Variável Global `$conexao`**
**Localizações:** 30+ arquivos  
**Impacto:** CRÍTICO - Impede injeção de dependências  
**Solução:**
- FASE 1: Criar `ConnectionManager` que mantém `$conexao` como singleton
- FASE 2-6: Injetar `ConnectionManager` em Repositories/Services
- FASE 7: Remover `$conexao` global completamente

#### 2. **Views Incluindo Controllers**
**Arquivos Afetados:**
- `app/views/usuarios/usuarios_listar.php` → includes `UsuarioListController.php`
- `app/views/usuarios/usuario_criar.php` → includes `UsuarioCreateController.php`
- `app/views/usuarios/usuario_editar.php` → includes `UsuarioUpdateController.php`
- `app/views/dependencias/dependencia_criar.php` → includes `DependenciaCreateController.php`
- [+4 arquivos adicionais]

**Impacto:** CRÍTICO - Inversão de fluxo MVC  
**Solução:**
- FASE 4: Refatorar controllers para retornar dados (não ecoar HTML)
- FASE 5: Views recebem dados via `Renderizador::render()`
- Manter compatibilidade: controllers legados continuam funcionando via includes

#### 3. **SQL Direto em Views**
**Arquivos Afetados:**
- `produto_check_view.php` (linhas 35, 57)
- `produto_copiar_etiquetas.php` (linhas 16, 25, 59, 80)
- `produtos_limpar_edicoes.php` (linha 54)
- `usuario_ver.php` (linha 12)

**Impacto:** ALTO - Viola separação de concerns  
**Solução:**
- FASE 2: Extrair queries para Repositories
- FASE 5: Passar dados pré-carregados para views

#### 4. **Funções Globais Procedurais**
**Helpers Afetados:**
- `comum_helper.php`: 15 funções globais (buscar_comuns_paginated, etc.)
- `produto_parser_service.php`: 20+ funções prefixadas `pp_*`
- `auth_helper.php`: verificar_login(), redirect_to_login()

**Impacto:** MÉDIO - Namespace poluído, sem injeção de dependências  
**Solução:**
- FASE 7: Converter em classes estáticas
- Manter funções globais como facades até FASE 9

#### 5. **Controllers Monolíticos**
**Arquivos Críticos:**
- `ImportacaoPlanilhaController.php`: 1480 linhas (!!!)
- `FormularioController.php`: ~600 linhas
- `Relatorio141Generator.php`: ~800 linhas

**Impacto:** ALTO - Difícil testar e manter  
**Solução:**
- FASE 4: Dividir em múltiplos Services antes de migrar
- Exemplo: ImportacaoPlanilhaController → PlanilhaUploadService + ExcelParserService + ProductImportService

#### 6. **Entry Points Duplicados**
**Conflitos:**
- `/index.php` (raiz) vs `/public/index.php`
- `/login.php` (raiz) vs `/public/index.php?route=/login`

**Impacto:** MÉDIO - Confusão de URLs  
**Solução:**
- Manter ambos durante FASES 1-5
- FASE 6: Redirecionar legado para novo (301 Moved Permanently)
- FASE 9: Remover arquivos raiz

---

## 🏗️ PADRÕES ARQUITETURAIS A ADOTAR

### 1. **Repository Pattern** (Acesso a Dados)

**Objetivo:** Abstrair lógica de persistência

**Estrutura:**
```php
interface RepositoryInterface {
    public function findById(int $id): ?array;
    public function findAll(array $filters = []): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

abstract class BaseRepository implements RepositoryInterface {
    protected PDO $connection;
    protected string $table;
    
    public function __construct(ConnectionManager $connManager) {
        $this->connection = $connManager->getConnection();
    }
    
    // Métodos CRUD genéricos...
}

class UsuarioRepository extends BaseRepository {
    protected string $table = 'usuarios';
    
    public function findByEmail(string $email): ?array {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE UPPER(email) = :email"
        );
        $stmt->execute(['email' => strtoupper($email)]);
        return $stmt->fetch() ?: null;
    }
}
```

**Benefícios:**
- SQL centralizado em um local
- Reutilização de queries
- Fácil substituir banco (PostgreSQL, MongoDB)
- Testável com mocks

### 2. **Service Layer** (Lógica de Negócio)

**Objetivo:** Separar regras de negócio de controllers

**Estrutura:**
```php
class AuthService {
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        private SessionManager $session
    ) {}
    
    public function authenticate(string $email, string $senha): bool {
        $usuario = $this->usuarioRepo->findByEmail($email);
        
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            throw new InvalidCredentialsException();
        }
        
        if ($usuario['ativo'] != 1) {
            throw new InactiveUserException();
        }
        
        $this->session->set('usuario_id', $usuario['id']);
        $this->session->set('usuario_nome', $usuario['nome']);
        
        return true;
    }
}
```

**Benefícios:**
- Controllers "magros" (apenas coordenam)
- Lógica reutilizável (CLI, API, Web)
- Testável isoladamente

### 3. **Dependency Injection Container**

**Objetivo:** Gerenciar criação e injeção de dependências

**Implementação:**
```php
class Container {
    private array $services = [];
    
    public function register(string $id, callable $factory): void {
        $this->services[$id] = $factory;
    }
    
    public function get(string $id): mixed {
        if (!isset($this->services[$id])) {
            throw new ServiceNotFoundException($id);
        }
        return $this->services[$id]($this);
    }
}

// Configuração (bootstrap)
$container->register(ConnectionManager::class, fn() => new ConnectionManager($config));
$container->register(UsuarioRepository::class, fn($c) => new UsuarioRepository($c->get(ConnectionManager::class)));
$container->register(AuthService::class, fn($c) => new AuthService(
    $c->get(UsuarioRepository::class),
    $c->get(SessionManager::class)
));
```

### 4. **Front Controller + Router**

**Objetivo:** Centralizar roteamento

**Estrutura:**
```php
// public/index.php
$router = new Router($container);
$router->loadRoutes(__DIR__ . '/../src/Routes');

$request = Request::createFromGlobals();
$response = $router->dispatch($request);
$response->send();

// src/Routes/web.php
$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/{id}', [UsuarioController::class, 'show']);
$router->post('/usuarios', [UsuarioController::class, 'store']);
$router->middleware([AuthMiddleware::class])->group(function($router) {
    // Rotas protegidas...
});
```

### 5. **Middleware Pipeline**

**Objetivo:** Request/Response processing

**Implementação:**
```php
interface MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response;
}

class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response {
        if (!isset($_SESSION['usuario_id'])) {
            return new RedirectResponse('/login');
        }
        return $next($request);
    }
}

class CsrfMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response {
        if ($request->isPost() && !$this->validateCsrfToken($request)) {
            throw new CsrfTokenMismatchException();
        }
        return $next($request);
    }
}
```

---

## 🔧 LIDANDO COM PROBLEMAS ESPECÍFICOS

### 1. Código Procedural Misturado com Renderização

**Problema Atual:**
```php
// index.php (raiz) - 421 linhas
<?php
require_once __DIR__ . '/app/bootstrap.php';

// Controller logic inline
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_ativo = isset($_GET['ativo']) ? $_GET['ativo'] : '';

// Database query inline
$comuns = buscar_comuns_paginated($pagina_atual, $itens_por_pagina, $busca, $filtro_ativo);

// HTML rendering inline
?>
<!DOCTYPE html>
<html>
<body>
    <?php foreach ($comuns as $comum): ?>
        <tr>...</tr>
    <?php endforeach; ?>
</body>
</html>
```

**Solução (FASE 3):**
```php
// src/Controllers/ComumController.php
class ComumController {
    public function index(Request $request): Response {
        $pagina = $request->query('pagina', 1);
        $busca = $request->query('busca', '');
        $filtro_ativo = $request->query('ativo', '');
        
        $comuns = $this->comumRepo->paginate($pagina, 20, [
            'busca' => $busca,
            'ativo' => $filtro_ativo
        ]);
        
        return $this->view->render('comuns/index', [
            'comuns' => $comuns,
            'pagina' => $pagina
        ]);
    }
}
```

**Estratégia de Transição:**
1. FASE 3: Criar `ComumController` com lógica extraída
2. FASE 3: Adicionar rota `/comuns` → `ComumController::index`
3. FASE 6: Criar redirect de `/index.php` → `/comuns` (301)
4. FASE 9: Remover `/index.php`

### 2. Funções Globais

**Problema Atual:**
```php
// comum_helper.php
function buscar_comuns_paginated($pagina, $limite, $busca = '', $ativo = '') {
    global $conexao; // ⚠️
    $offset = ($pagina - 1) * $limite;
    $sql = "SELECT * FROM comuns WHERE ...";
    $stmt = $conexao->prepare($sql);
    // ...
}
```

**Solução (FASE 2 + FASE 7):**
```php
// FASE 2: Criar Repository
class ComumRepository extends BaseRepository {
    public function paginate(int $page, int $limit, array $filters = []): array {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE ...";
        $stmt = $this->connection->prepare($sql);
        // ...
    }
}

// FASE 7: Manter função como facade (backward compatibility)
function buscar_comuns_paginated($pagina, $limite, $busca = '', $ativo = '') {
    static $repo = null;
    if (!$repo) {
        global $container;
        $repo = $container->get(ComumRepository::class);
    }
    return $repo->paginate($pagina, $limite, [
        'busca' => $busca,
        'ativo' => $ativo
    ]);
}
```

**Cronograma:**
- FASE 2: Criar `ComumRepository` (sem remover função global)
- FASE 3-5: Migrar controllers para usar `ComumRepository` diretamente
- FASE 7: Converter função global em facade
- FASE 9: Deprecar função global, remover em versão futura

### 3. Variáveis Globais

**Problema Atual:**
```php
// config/database.php (linha 57)
$database = new Database();
$conexao = $database->getConnection(); // ⚠️ GLOBAL

// Usado em 30+ arquivos
global $conexao;
$stmt = $conexao->prepare("SELECT ...");
```

**Solução (FASE 1):**
```php
// src/Core/ConnectionManager.php
class ConnectionManager {
    private static ?PDO $connection = null;
    
    public static function getInstance(array $config = []): PDO {
        if (self::$connection === null) {
            self::$connection = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']}",
                $config['user'],
                $config['pass']
            );
        }
        return self::$connection;
    }
    
    // Para backward compatibility
    public static function getGlobalConnection(): PDO {
        global $conexao;
        if (!$conexao) {
            $conexao = self::getInstance($_ENV);
        }
        return $conexao;
    }
}

// config/database.php (refatorado)
$conexao = ConnectionManager::getGlobalConnection(); // Mantém compatibilidade
```

**Estratégia de Eliminação:**
1. FASE 1: Criar `ConnectionManager`, manter `$conexao` global
2. FASE 2-6: Injetar `ConnectionManager` em classes novas
3. FASE 7: Refatorar código legado para usar `ConnectionManager`
4. FASE 9: Remover `$conexao` global

### 4. Imports Circulares

**Status:** ✅ Nenhum import circular detectado

**Prevenção:**
- Usar interfaces para desacoplar
- Dependency Injection quebra dependências circulares
- Evitar `require` em classes (usar autoload PSR-4)

---

## ✅ CHECKLIST TÉCNICO DE MIGRAÇÃO

### Pré-Requisitos (FASE 0)
- [ ] Análise arquitetural completa (✅ FEITO)
- [ ] Configurar PHPUnit
- [ ] Criar testes de integração para fluxos críticos:
  - [ ] Login/Logout
  - [ ] CRUD Usuários
  - [ ] CRUD Produtos
  - [ ] Importação de Planilha
  - [ ] Geração Relatório 14.1
- [ ] Configurar CI/CD (GitHub Actions)
- [ ] Criar branch de desenvolvimento (`feature/refactor-architecture`)
- [ ] Documentar APIs internas (contratos de dados)

### FASE 1: Infraestrutura
- [ ] Criar `src/Core/ConnectionManager.php`
- [ ] Criar `src/Core/Container.php` (DI Container)
- [ ] Criar `src/Core/Request.php`
- [ ] Criar `src/Core/Response.php`
- [ ] Criar `src/Core/Router.php`
- [ ] Criar `src/Middleware/` (AuthMiddleware, CsrfMiddleware)
- [ ] Unificar bootstrap (deprecar `app/bootstrap.php`)
- [ ] Testes unitários para cada classe Core
- [ ] Validar: Sistema legado continua funcionando

### FASE 2: Repositories
- [ ] Criar `src/Repositories/BaseRepository.php`
- [ ] Implementar `UsuarioRepository` (findById, findByEmail, create, update, delete, paginate)
- [ ] Implementar `ComumRepository` (migrar comum_helper.php)
- [ ] Implementar `ProdutoRepository`
- [ ] Implementar `DependenciaRepository`
- [ ] Implementar `TipoBemRepository`
- [ ] Implementar `ConfiguracaoRepository`
- [ ] Substituir SQL direto em views por chamadas a Repositories
- [ ] Testes unitários para cada Repository (com mocks PDO)
- [ ] Validar: Zero SQL em views

### FASE 3: Controllers
- [ ] Migrar `UsuarioController` (create, update, delete, index, show)
- [ ] Migrar `ComumController`
- [ ] Migrar `DependenciaController`
- [ ] Migrar `TipoBemController`
- [ ] Migrar `ProdutoController`
- [ ] Adicionar rotas em `MapaRotas.php`
- [ ] Testes unitários para cada Controller (mocks de Repositories)
- [ ] Validar: Rotas novas funcionando em paralelo com legado

### FASE 4: Services
- [ ] Extrair `PlanilhaUploadService` de ImportacaoPlanilhaController
- [ ] Extrair `ExcelParserService` (migrar produto_parser_service.php)
- [ ] Extrair `ProductImportService`
- [ ] Extrair `Relatorio141Service` (migrar Relatorio141Generator.php)
- [ ] Extrair `JobManagerService`
- [ ] Refatorar controllers para usar Services
- [ ] Testes unitários para cada Service
- [ ] Validar: ImportacaoPlanilhaController reduzido a <200 linhas

### FASE 5: Views
- [ ] Remover `include` de controllers em views
- [ ] Migrar views para `src/Views/` (estrutura por domínio)
- [ ] Criar `src/Helpers/FormHelper.php`
- [ ] Criar `src/Helpers/PaginationHelper.php`
- [ ] Criar `src/Helpers/AlertHelper.php`
- [ ] Padronizar layouts (`src/Views/layouts/`)
- [ ] Criar partials (`src/Views/partials/`)
- [ ] Validar: Zero `include` de controllers em views

### FASE 6: Roteamento
- [ ] Expandir `MapaRotas.php` com TODAS as rotas
- [ ] Suporte a parâmetros de rota (`/usuarios/{id}`)
- [ ] Suporte a middleware por rota
- [ ] Criar `.htaccess` com rewrite rules
- [ ] Criar helper `route()` para geração de URLs
- [ ] Atualizar links em views (`href="{{ route('usuarios.create') }}"`)
- [ ] Configurar redirects 301 de URLs legadas
- [ ] Validar: 100% rotas via MapaRotas

### FASE 7: Helpers Refactoring
- [ ] Criar `src/Helpers/TextHelper.php` (migrar uppercase_helper.php)
- [ ] Criar `src/Helpers/AuthHelper.php` (migrar auth_helper.php)
- [ ] Converter funções globais em facades
- [ ] Deprecar funções globais (adicionar @deprecated)
- [ ] Validar: Helpers como classes

### FASE 8: Segurança e Otimizações
- [ ] Implementar CSRF Protection
- [ ] Implementar Rate Limiting (login, importação)
- [ ] Adicionar Cache Layer (Redis/Memcached)
- [ ] Configurar Logging Estruturado (Monolog)
- [ ] Validação robusta de upload (MIME type, vírus scan)
- [ ] Auditoria OWASP Top 10
- [ ] Validar: Todas vulnerabilidades mitigadas

### FASE 9: Cleanup
- [ ] Mover arquivos legados para `__legacy_backup__/`
- [ ] Remover `$conexao` global
- [ ] Remover funções globais
- [ ] Atualizar README.md (arquitetura, guias)
- [ ] Gerar documentação API (Swagger/OpenAPI)
- [ ] Configurar PHP-CS-Fixer (PSR-12)
- [ ] Code review final
- [ ] Deploy em produção
- [ ] Validar: Sistema 100% refatorado

---

## 📅 FASES DETALHADAS

## FASE 0: PREPARAÇÃO E TESTES
**Duração:** 2 semanas  
**Objetivo:** Criar rede de segurança antes de refatorar

### Tarefas

#### 1. Configurar Ambiente de Testes
```bash
composer require --dev phpunit/phpunit
composer require --dev mockery/mockery
```

**Arquivo:** `phpunit.xml`
```xml
<?xml version="1.0"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>src</directory>
        </include>
    </coverage>
</phpunit>
```

#### 2. Criar Testes de Integração Críticos

**tests/Integration/AuthTest.php**
```php
class AuthTest extends TestCase {
    public function test_usuario_pode_fazer_login() {
        $response = $this->post('/login', [
            'email' => 'admin@checkplanilha.com',
            'senha' => 'password'
        ]);
        
        $this->assertRedirect('/');
        $this->assertSessionHas('usuario_id');
    }
    
    public function test_usuario_inativo_nao_pode_logar() {
        // ...
    }
}
```

**tests/Integration/UsuarioCrudTest.php**
**tests/Integration/PlanilhaImportTest.php**
**tests/Integration/Relatorio141Test.php**

#### 3. Configurar CI/CD

**.github/workflows/tests.yml**
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - run: composer install
      - run: vendor/bin/phpunit
```

### Critérios de Validação
- [ ] PHPUnit configurado e rodando
- [ ] Pelo menos 5 testes de integração passando
- [ ] CI/CD executando testes automaticamente
- [ ] Cobertura mínima: 50% dos fluxos críticos

### Riscos
- **Risco:** Testes quebrarem durante migração  
  **Mitigação:** Manter testes de integração (high-level), não unit tests

---

## FASE 1: INFRAESTRUTURA CORE
**Duração:** 1 semana  
**Objetivo:** Criar camada de base reutilizável

### Tarefas

#### 1. ConnectionManager (Gerenciamento de Conexão)

**src/Core/ConnectionManager.php**
```php
namespace App\Core;

use PDO;

class ConnectionManager {
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = [
                'host' => env('DB_HOST', 'db'),
                'dbname' => env('DB_NAME', 'checkplanilha'),
                'user' => env('DB_USER', 'checkplanilha'),
                'pass' => env('DB_PASS', 'checkplanilha123'),
                'charset' => 'utf8mb4'
            ];
            
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            
            self::$instance = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        
        return self::$instance;
    }
    
    // Manter compatibilidade com $conexao global
    public static function setGlobalConnection(): void {
        global $conexao;
        $conexao = self::getInstance();
    }
}
```

**Uso:**
```php
// Novo código
$connection = ConnectionManager::getInstance();

// Código legado (mantém funcionando)
ConnectionManager::setGlobalConnection();
global $conexao; // Agora $conexao aponta para ConnectionManager
```

#### 2. Dependency Injection Container

**src/Core/Container.php**
```php
namespace App\Core;

class Container {
    private array $bindings = [];
    private array $instances = [];
    
    public function bind(string $abstract, callable $concrete): void {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function singleton(string $abstract, callable $concrete): void {
        $this->bind($abstract, function($container) use ($abstract, $concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete($container);
            }
            return $this->instances[$abstract];
        });
    }
    
    public function get(string $abstract): mixed {
        if (!isset($this->bindings[$abstract])) {
            // Auto-resolve se for classe concreta
            return $this->resolve($abstract);
        }
        return $this->bindings[$abstract]($this);
    }
    
    private function resolve(string $class): mixed {
        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();
        
        if (!$constructor) {
            return new $class;
        }
        
        $dependencies = array_map(
            fn($param) => $this->get($param->getType()->getName()),
            $constructor->getParameters()
        );
        
        return $reflector->newInstanceArgs($dependencies);
    }
}
```

#### 3. Request/Response Wrappers

**src/Core/Request.php**
**src/Core/Response.php**

#### 4. Middleware Pipeline

**src/Middleware/AuthMiddleware.php**
**src/Middleware/CsrfMiddleware.php**

### Critérios de Validação
- [ ] ConnectionManager funciona e mantém `$conexao` global
- [ ] Container resolve dependências automaticamente
- [ ] Testes unitários para cada classe Core
- [ ] Sistema legado NÃO quebrou

### Riscos
- **Risco:** Configuração de DI Container complexa  
  **Mitigação:** Usar biblioteca existente (PHP-DI) se necessário

### Rollback
1. Remover arquivos `src/Core/*` criados
2. Restaurar `config/database.php` original
3. Executar testes de integração

---

## FASE 2: REPOSITORIES (Data Access Layer)
**Duração:** 2 semanas  
**Objetivo:** Centralizar acesso a dados, remover SQL de controllers/views

### Tarefas

#### 1. BaseRepository Abstrato

**src/Repositories/BaseRepository.php**
```php
namespace App\Repositories;

use App\Core\ConnectionManager;
use PDO;

abstract class BaseRepository {
    protected PDO $connection;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct() {
        $this->connection = ConnectionManager::getInstance();
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function findAll(): array {
        return $this->connection->query("SELECT * FROM {$this->table}")->fetchAll();
    }
    
    public function create(array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $stmt = $this->connection->prepare(
            "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)"
        );
        $stmt->execute($data);
        
        return (int) $this->connection->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $set = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($data)));
        
        $stmt = $this->connection->prepare(
            "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = :id"
        );
        
        return $stmt->execute([...$data, 'id' => $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->connection->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id"
        );
        return $stmt->execute(['id' => $id]);
    }
    
    protected function paginate(int $page, int $perPage, array $filters = []): array {
        // Implementação genérica de paginação
    }
}
```

#### 2. Repositories Específicos

**src/Repositories/UsuarioRepository.php**
```php
namespace App\Repositories;

class UsuarioRepository extends BaseRepository {
    protected string $table = 'usuarios';
    
    public function findByEmail(string $email): ?array {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE UPPER(email) = :email"
        );
        $stmt->execute(['email' => strtoupper($email)]);
        return $stmt->fetch() ?: null;
    }
    
    public function findByCpf(string $cpf): ?array {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE cpf = :cpf"
        );
        $stmt->execute(['cpf' => $cpf]);
        return $stmt->fetch() ?: null;
    }
    
    public function paginateWithFilters(int $page, int $perPage, array $filters): array {
        $conditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['busca'])) {
            $conditions[] = "(nome LIKE :busca OR email LIKE :busca)";
            $params['busca'] = "%{$filters['busca']}%";
        }
        
        if (isset($filters['ativo'])) {
            $conditions[] = "ativo = :ativo";
            $params['ativo'] = $filters['ativo'];
        }
        
        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE $where LIMIT :limit OFFSET :offset"
        );
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $countStmt = $this->connection->prepare(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE $where"
        );
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => ceil($total / $perPage)
        ];
    }
}
```

**Criar similares para:**
- `ComumRepository` (migrar comum_helper.php)
- `ProdutoRepository` (queries com JOINs)
- `DependenciaRepository`
- `TipoBemRepository`

#### 3. Substituir SQL em Views

**ANTES (produto_check_view.php - linha 35):**
```php
$stmt_STATUS = $conexao->prepare('SELECT checado, imprimir_etiqueta, imprimir_14_1 
                                   FROM produtos 
                                   WHERE id_produto = :id_produto AND comum_id = :comum_id');
$stmt_STATUS->execute([':id_produto' => $id_produto, ':comum_id' => $comum_id]);
$row = $stmt_STATUS->fetch();
```

**DEPOIS:**
```php
// No controller antes de chamar a view
$produto = $produtoRepo->findById($id_produto);

// Na view, apenas usar $produto (sem SQL)
<?php if ($produto['checado']): ?>
    Checado
<?php endif; ?>
```

### Critérios de Validação
- [ ] Todos Repositories têm testes unitários
- [ ] Zero SQL direto em views (verificar com grep)
- [ ] Controllers legados funcionando (usam funções helper que chamam Repositories internamente)

### Riscos
- **Risco:** Queries complexas difíceis de migrar  
  **Mitigação:** Permitir raw SQL em Repositories inicialmente, refatorar depois

### Rollback
1. Manter funções helper originais funcionando em paralelo
2. Se Repository falhar, código legado continua via helpers

---

## FASE 3: CONTROLLERS REFACTORING
**Duração:** 3 semanas  
**Objetivo:** Controllers magros, injeção de dependências

### Tarefas

#### 1. Estrutura de Controller Padrão

**src/Controllers/BaseController.php**
```php
namespace App\Controllers;

use App\Core\Renderizador;
use App\Core\Request;
use App\Core\Response;

abstract class BaseController {
    protected Renderizador $view;
    
    public function __construct(Renderizador $view) {
        $this->view = $view;
    }
    
    protected function render(string $template, array $data = []): string {
        return $this->view->render($template, $data);
    }
    
    protected function redirect(string $url, int $status = 302): void {
        header("Location: $url", true, $status);
        exit;
    }
    
    protected function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

#### 2. UsuarioController Completo

**src/Controllers/UsuarioController.php**
```php
namespace App\Controllers;

use App\Repositories\UsuarioRepository;
use App\Core\Request;

class UsuarioController extends BaseController {
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        Renderizador $view
    ) {
        parent::__construct($view);
    }
    
    public function index(Request $request): string {
        $page = (int) $request->query('pagina', 1);
        $busca = $request->query('busca', '');
        $ativo = $request->query('ativo', '');
        
        $result = $this->usuarioRepo->paginateWithFilters($page, 20, [
            'busca' => $busca,
            'ativo' => $ativo
        ]);
        
        return $this->render('usuarios/index', [
            'usuarios' => $result['data'],
            'pagination' => $result,
            'filtros' => compact('busca', 'ativo')
        ]);
    }
    
    public function create(Request $request): string {
        if ($request->isPost()) {
            return $this->store($request);
        }
        
        return $this->render('usuarios/create', [
            'usuario' => []
        ]);
    }
    
    public function store(Request $request): void {
        $data = $request->post();
        
        // Validação
        $erros = $this->validarUsuario($data);
        if (!empty($erros)) {
            return $this->render('usuarios/create', [
                'erros' => $erros,
                'usuario' => $data
            ]);
        }
        
        // Hash senha
        $data['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        $data['email'] = strtoupper($data['email']);
        
        try {
            $id = $this->usuarioRepo->create($data);
            $this->redirect("/usuarios?sucesso=Usuario criado com ID $id");
        } catch (\Exception $e) {
            return $this->render('usuarios/create', [
                'erro' => 'Erro ao criar usuário: ' . $e->getMessage(),
                'usuario' => $data
            ]);
        }
    }
    
    private function validarUsuario(array $data): array {
        $erros = [];
        
        if (empty($data['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Email inválido';
        }
        
        if ($this->usuarioRepo->findByEmail($data['email'])) {
            $erros[] = 'Email já cadastrado';
        }
        
        // ... mais validações
        
        return $erros;
    }
    
    // show(), edit(), update(), delete()...
}
```

#### 3. Adicionar Rotas

**src/Routes/MapaRotas.php**
```php
use App\Controllers\UsuarioController;
use App\Controllers\ComumController;
// ...

return [
    // Auth
    'GET /' => [AuthController::class, 'login'],
    'GET /login' => [AuthController::class, 'login'],
    'POST /login' => [AuthController::class, 'authenticate'],
    'GET /logout' => [AuthController::class, 'logout'],
    
    // Usuarios (CRUD completo)
    'GET /usuarios' => [UsuarioController::class, 'index'],
    'GET /usuarios/criar' => [UsuarioController::class, 'create'],
    'POST /usuarios' => [UsuarioController::class, 'store'],
    'GET /usuarios/{id}' => [UsuarioController::class, 'show'],
    'GET /usuarios/{id}/editar' => [UsuarioController::class, 'edit'],
    'POST /usuarios/{id}' => [UsuarioController::class, 'update'],
    'POST /usuarios/{id}/deletar' => [UsuarioController::class, 'delete'],
    
    // Comuns
    'GET /comuns' => [ComumController::class, 'index'],
    // ...
];
```

#### 4. Compatibilidade Legada

**app/views/usuarios/usuarios_listar.php - REFATORADO**
```php
<?php
// Redirecionar para nova rota
header('Location: /usuarios?' . http_build_query($_GET), true, 301);
exit;
```

### Critérios de Validação
- [ ] Todos controllers CRUD migrados
- [ ] Rotas funcionando via MapaRotas
- [ ] Testes unitários para cada controller (mocks de Repositories)
- [ ] Sistema legado redirecionando para novo (301)

### Riscos
- **Risco:** Validação complexa em controllers duplicada  
  **Mitigação:** Criar classes Validator na FASE 4

---

## FASE 4: SERVICE LAYER (Business Logic)
**Duração:** 2 semanas  
**Objetivo:** Extrair lógica complexa, dividir classes monolíticas

### Foco Principal: ImportacaoPlanilhaController (1480 linhas!)

#### Decomposição:

**1. PlanilhaUploadService** (Upload e Validação)
```php
namespace App\Services;

class PlanilhaUploadService {
    public function uploadFile(array $file): string {
        // Validar MIME type
        // Validar tamanho
        // Scan de vírus (ClamAV)
        // Mover para storage/tmp/
        // Retornar caminho
    }
    
    public function validateFile(string $path): array {
        // Verificar se é Excel válido
        // PhpSpreadsheet::load()
        // Retornar erros ou []
    }
}
```

**2. ExcelParserService** (Parsing e Normalização)
```php
namespace App\Services;

class ExcelParserService {
    public function parse(string $filePath, array $config): array {
        // Carregar Excel em chunks (evitar estouro de memória)
        // Aplicar mapeamento de colunas
        // Normalizar texto (UTF-8, uppercase)
        // Retornar array de linhas processadas
    }
    
    public function detectColumns(array $headerRow): array {
        // Auto-detectar colunas (código, descrição, etc)
        // Fuzzy matching com pp_match_fuzzy()
    }
}
```

**3. ProductImportService** (Lógica de Importação)
```php
namespace App\Services;

class ProductImportService {
    public function __construct(
        private ProdutoRepository $produtoRepo,
        private TipoBemRepository $tipoBemRepo,
        private DependenciaRepository $dependenciaRepo
    ) {}
    
    public function importBatch(array $produtos, int $comumId): array {
        // Detectar tipos de bens
        // Garantir dependências existem
        // Inserção em lote (bulkInsert)
        // Retornar estatísticas (inseridos, erros, duplicados)
    }
}
```

**4. JobManagerService** (Gerenciamento de Jobs Assíncronos)
```php
namespace App\Services;

class JobManagerService {
    public function createJob(string $jobId, int $userId): void {
        // Criar registro em import_job_processed
    }
    
    public function updateJobStatus(string $jobId, string $status): void {
        // Atualizar status (processing, completed, failed)
    }
    
    public function getJobProgress(string $jobId): array {
        // Retornar progresso (produtos processados / total)
    }
}
```

**PlanilhaController FINAL (<200 linhas):**
```php
namespace App\Controllers;

class PlanilhaController extends BaseController {
    public function __construct(
        private PlanilhaUploadService $uploadService,
        private ExcelParserService $parserService,
        private ProductImportService $importService,
        private JobManagerService $jobManager,
        Renderizador $view
    ) {
        parent::__construct($view);
    }
    
    public function import(Request $request): string {
        if ($request->isPost()) {
            return $this->processImport($request);
        }
        
        return $this->render('planilhas/import');
    }
    
    private function processImport(Request $request): string {
        try {
            // Upload
            $filePath = $this->uploadService->uploadFile($request->file('arquivo'));
            
            // Parse
            $produtos = $this->parserService->parse($filePath, $request->post('config', []));
            
            // Import
            $jobId = uniqid('import_');
            $this->jobManager->createJob($jobId, $_SESSION['usuario_id']);
            
            $result = $this->importService->importBatch($produtos, $request->post('comum_id'));
            
            $this->jobManager->updateJobStatus($jobId, 'completed');
            
            return $this->render('planilhas/import_result', [
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return $this->render('planilhas/import', [
                'erro' => $e->getMessage()
            ]);
        }
    }
}
```

### Critérios de Validação
- [ ] ImportacaoPlanilhaController < 200 linhas
- [ ] Cada Service testável isoladamente
- [ ] Importação ainda funciona corretamente

---

## FASE 5: VIEWS MIGRATION
**Duração:** 2 semanas  
**Objetivo:** Views puras (sem SQL, sem includes de controllers)

### Antes e Depois

**ANTES (app/views/usuarios/usuarios_listar.php):**
```php
<?php
require_once __DIR__ . '/../../bootstrap.php';
include __DIR__ . '/../../controllers/read/UsuarioListController.php'; // ⚠️

// UsuarioListController define $usuarios, $total, $pagina_atual
?>
<!DOCTYPE html>
<html>
<body>
    <?php foreach ($usuarios as $usuario): ?>
        <tr>...</tr>
    <?php endforeach; ?>
</body>
</html>
```

**DEPOIS (src/Views/usuarios/index.php):**
```php
<!-- $usuarios, $pagination, $filtros passados pelo controller -->
<!DOCTYPE html>
<html>
<body>
    <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?= htmlspecialchars($usuario['nome']) ?></td>
            <td><?= htmlspecialchars($usuario['email']) ?></td>
            <td><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></td>
        </tr>
    <?php endforeach; ?>
    
    <?= PaginationHelper::render($pagination) ?>
</body>
</html>
```

### Helpers de View

**src/Helpers/PaginationHelper.php**
```php
namespace App\Helpers;

class PaginationHelper {
    public static function render(array $pagination): string {
        // Gerar HTML de paginação Bootstrap
        $html = '<nav><ul class="pagination">';
        
        for ($i = 1; $i <= $pagination['lastPage']; $i++) {
            $active = $i === $pagination['page'] ? 'active' : '';
            $html .= "<li class='page-item $active'>";
            $html .= "<a class='page-link' href='?pagina=$i'>$i</a>";
            $html .= "</li>";
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
}
```

### Critérios de Validação
- [ ] Zero `include` de controllers
- [ ] Zero SQL direto
- [ ] Todas views em `src/Views/`

---

## FASE 6: ROTEAMENTO UNIFICADO
**Duração:** 1 semana  
**Objetivo:** 100% rotas via MapaRotas, desativar sistema legado

### .htaccess
```apache
RewriteEngine On
RewriteBase /

# Redirecionar URLs legadas (301 Permanent)
RewriteRule ^index\.php$ /comuns [R=301,L]
RewriteRule ^login\.php$ /login [R=301,L]
RewriteRule ^logout\.php$ /logout [R=301,L]

# Front Controller
RewriteRule ^(.*)/$ /$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /public/index.php [QSA,L]
```

---

## FASE 7-9: HELPERS, SEGURANÇA, CLEANUP
(Detalhamento similar às fases anteriores)

---

## 🛡️ ESTRATÉGIA DE ROLLBACK

### Por Fase

#### FASE 0-1: Rollback Simples
1. Deletar branch `feature/refactor-architecture`
2. Restaurar `main` branch
3. **Perda:** Apenas código de testes

#### FASE 2-4: Rollback Intermediário
1. Manter funções helper originais funcionando
2. Remover classes `src/Repositories`, `src/Services`
3. Restaurar controllers originais de backup
4. **Perda:** Código refatorado (mas sistema legado intacto)

#### FASE 5-6: Rollback Arriscado
1. Restaurar views originais de `__legacy_backup__/`
2. Remover redirects 301 do `.htaccess`
3. Restaurar entry points raiz
4. **Perda:** Todo progresso de migração
5. **Tempo:** ~2 horas

#### FASE 7-9: Rollback Crítico
⚠️ **NÃO RECOMENDADO** - Sistema legado já foi removido

**Alternativa:** Hotfix pontual em vez de rollback completo

### Checklist Pré-Deploy
- [ ] Backup completo do banco de dados
- [ ] Tag Git da versão atual (`git tag v1.0-pre-refactor`)
- [ ] Testes de integração passando (100%)
- [ ] Testes em ambiente staging (mínimo 1 semana)
- [ ] Plano de comunicação com usuários
- [ ] Monitoramento de erros configurado (Sentry/Rollbar)

---

## 🧪 ESTRATÉGIA DE TESTES

### Pirâmide de Testes

```
         /\
        /  \  E2E (5%)
       /____\
      /      \  Integration (25%)
     /________\
    /          \  Unit (70%)
   /____________\
```

### Testes por Camada

#### Unit Tests (70% dos testes)
**Objetivo:** Testar classes isoladamente

**Ferramentas:** PHPUnit + Mockery

**Exemplo:**
```php
// tests/Unit/Repositories/UsuarioRepositoryTest.php
class UsuarioRepositoryTest extends TestCase {
    public function test_findByEmail_retorna_usuario() {
        $pdoMock = Mockery::mock(PDO::class);
        $stmtMock = Mockery::mock(PDOStatement::class);
        
        $pdoMock->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*FROM usuarios/'))
            ->andReturn($stmtMock);
        
        $stmtMock->shouldReceive('execute')->once();
        $stmtMock->shouldReceive('fetch')->once()->andReturn([
            'id' => 1,
            'email' => 'TEST@EXAMPLE.COM',
            'nome' => 'Test User'
        ]);
        
        $repo = new UsuarioRepository($pdoMock);
        $usuario = $repo->findByEmail('test@example.com');
        
        $this->assertEquals(1, $usuario['id']);
        $this->assertEquals('TEST@EXAMPLE.COM', $usuario['email']);
    }
}
```

**Cobertura:**
- Todos Repositories
- Todos Services
- Helpers (FormHelper, PaginationHelper)

#### Integration Tests (25% dos testes)
**Objetivo:** Testar fluxos completos com banco real

**Ferramentas:** PHPUnit + Database seeding

**Exemplo:**
```php
// tests/Integration/UsuarioCrudTest.php
class UsuarioCrudTest extends DatabaseTestCase {
    public function test_usuario_pode_ser_criado_e_listado() {
        $repo = new UsuarioRepository();
        
        $id = $repo->create([
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => password_hash('123456', PASSWORD_DEFAULT),
            'ativo' => 1
        ]);
        
        $this->assertGreaterThan(0, $id);
        
        $usuario = $repo->findById($id);
        $this->assertEquals('JOAO@EXAMPLE.COM', $usuario['email']);
    }
}
```

**Cobertura:**
- Fluxo de login/logout
- CRUD de usuários
- CRUD de produtos
- Importação de planilha
- Geração de relatórios

#### E2E Tests (5% dos testes)
**Objetivo:** Testar UI completo (browser automation)

**Ferramentas:** Symfony Panther ou Codeception

**Exemplo:**
```php
class LoginCest {
    public function testUserCanLogin(AcceptanceTester $I) {
        $I->amOnPage('/login');
        $I->fillField('email', 'admin@checkplanilha.com');
        $I->fillField('senha', 'password');
        $I->click('Entrar');
        $I->seeInCurrentUrl('/comuns');
        $I->see('Bem-vindo');
    }
}
```

**Cobertura:**
- Login/Logout
- Criação de usuário via formulário
- Importação de planilha end-to-end

### Cobertura Mínima por Fase

| Fase | Cobertura Unit | Cobertura Integration |
|------|---------------|----------------------|
| FASE 0 | - | 50% fluxos críticos |
| FASE 1 | 80% Core classes | - |
| FASE 2 | 90% Repositories | 70% CRUD |
| FASE 3 | 70% Controllers | 80% rotas |
| FASE 4 | 85% Services | 90% importação |
| FASE 5-9 | 75% geral | 85% geral |

### Automação CI/CD

**.github/workflows/ci.yml**
```yaml
name: CI Pipeline
on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: checkplanilha_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, pdo_mysql, zip
          coverage: xdebug
      
      - name: Install Dependencies
        run: composer install --prefer-dist
      
      - name: Run Migrations
        run: vendor/bin/phinx migrate -e test
      
      - name: Run Unit Tests
        run: vendor/bin/phpunit --testsuite=Unit --coverage-clover coverage.xml
      
      - name: Run Integration Tests
        run: vendor/bin/phpunit --testsuite=Integration
      
      - name: Upload Coverage
        uses: codecov/codecov-action@v2
        with:
          files: ./coverage.xml
      
      - name: Check Coverage Threshold
        run: |
          COVERAGE=$(php -r "echo round((simplexml_load_file('coverage.xml')->project->metrics['coveredstatements'] / simplexml_load_file('coverage.xml')->project->metrics['statements']) * 100, 2);")
          if (( $(echo "$COVERAGE < 75" | bc -l) )); then
            echo "Coverage $COVERAGE% is below 75% threshold"
            exit 1
          fi
```

---

## ✅ CRITÉRIOS DE "MIGRAÇÃO CONCLUÍDA"

### Técnicos

#### Código
- [ ] Zero arquivos em `app/controllers/`, `app/views/` (movidos para `src/`)
- [ ] Zero `global $conexao` no código
- [ ] Zero funções globais (exceto facades deprecadas)
- [ ] Zero `include` de controllers em views
- [ ] Zero SQL direto em views
- [ ] 100% rotas via `MapaRotas.php`
- [ ] PSR-12 compliance (PHP-CS-Fixer)
- [ ] PHPStan level 5 sem erros

#### Testes
- [ ] Cobertura de código: ≥75% (Unit + Integration)
- [ ] 100% fluxos críticos cobertos (login, CRUD, import, relatórios)
- [ ] CI/CD rodando e passando
- [ ] Zero erros em staging por 1 semana

#### Arquitetura
- [ ] Dependency Injection em 100% dos controllers/services
- [ ] Repository Pattern para acesso a dados
- [ ] Service Layer para lógica de negócio
- [ ] Middleware para autenticação/CSRF
- [ ] Rotas RESTful padronizadas

#### Segurança
- [ ] CSRF protection implementado
- [ ] Rate limiting (login, importação)
- [ ] Validação robusta de uploads
- [ ] Logs estruturados (Monolog)
- [ ] Auditoria OWASP Top 10 completa

#### Documentação
- [ ] README.md atualizado
- [ ] Guia de arquitetura (ARCHITECTURE.md)
- [ ] Guia de desenvolvimento (CONTRIBUTING.md)
- [ ] API docs (Swagger/OpenAPI se houver endpoints JSON)
- [ ] PHPDoc em 100% classes públicas

### Funcionais

#### Usuários
- [ ] Login/Logout funcionando
- [ ] CRUD de usuários completo
- [ ] Permissões e acessos mantidos

#### Planilhas
- [ ] Importação Excel funcionando
- [ ] Detecção de colunas correta
- [ ] Normalização de texto (UTF-8, uppercase)
- [ ] Jobs assíncronos (ou síncronos se não houver muito volume)

#### Relatórios
- [ ] Formulário 14.1 gerado corretamente
- [ ] Relatórios 14.2 a 14.8 funcionando
- [ ] Exportação PDF/Excel

#### Performance
- [ ] Tempo de resposta médio <500ms (páginas)
- [ ] Tempo de importação <2min (10.000 linhas)
- [ ] Queries otimizadas (sem N+1)

### Organizacionais

#### Deploy
- [ ] Ambiente staging testado (mínimo 2 semanas)
- [ ] Rollback testado e documentado
- [ ] Backup do banco criado
- [ ] Downtime planejado (se necessário): <30min

#### Comunicação
- [ ] Usuários notificados sobre mudanças
- [ ] Changelog publicado
- [ ] Treinamento (se UI mudou)

#### Equipe
- [ ] Code review completo (peer review)
- [ ] Conhecimento transferido (pair programming)
- [ ] Onboarding docs atualizados

---

## 📊 MÉTRICAS DE SUCESSO

### KPIs Técnicos

| Métrica | Antes | Meta Após Migração |
|---------|-------|-------------------|
| Linhas de código | ~15.000 | ~12.000 (20% redução) |
| Arquivos | ~80 | ~60 (consolidação) |
| Cobertura de testes | 0% | ≥75% |
| Tempo médio de response | ~800ms | <500ms |
| Bugs críticos/mês | ? | <2 |
| Tempo para adicionar feature | ~3 dias | ~1 dia |
| Onboarding dev novo | ~2 semanas | ~1 semana |

### ROI Estimado

**Investimento:**
- 16 semanas × 1 dev full-time = ~640 horas

**Retorno:**
- Manutenção: -50% tempo (bugs, features)
- Onboarding: -50% tempo
- Segurança: -90% incidentes (CSRF, injection)
- Performance: +40% velocidade

**Payback:** ~6 meses

---

## 🚨 ALERTAS E AVISOS

### ⚠️ RISCOS CRÍTICOS NÃO MITIGÁVEIS

1. **Sem testes atuais = Refatoração perigosa**
   - **Impacto:** Regressões não detectadas
   - **Mitigação:** FASE 0 criar testes ANTES de mexer

2. **Sistema em produção + ZERO downtime exigido**
   - **Impacto:** Migração mais lenta e complexa
   - **Mitigação:** Manter código legado funcionando até FASE 9

3. **ImportacaoPlanilhaController (1480 linhas) = Bomba-relógio**
   - **Impacto:** Refatorar pode quebrar importações
   - **Mitigação:** Criar testes E2E de importação ANTES

### 🟡 DECISÕES TÉCNICAS A VALIDAR

- [ ] **Framework ou Plain PHP?** (Recomendação: Plain inicialmente, avaliar Symfony/Laravel após)
- [ ] **ORM ou Query Builder?** (Recomendação: Manter PDO, adicionar Query Builder depois)
- [ ] **Frontend Framework?** (Recomendação: Manter jQuery/Bootstrap, avaliar Vue.js/Alpine depois)
- [ ] **Cache Strategy?** (Recomendação: Redis para sessões + queries frequentes)

---

## 📚 REFERÊNCIAS ARQUITETURAIS

### Padrões a Seguir
- **Clean Architecture** (Uncle Bob)
- **Domain-Driven Design** (Evans) - Lite version
- **PSR-12** (PHP Coding Standard)
- **PSR-4** (Autoloading)
- **SOLID Principles**

### Livros Recomendados
- "Refactoring: Improving the Design of Existing Code" - Martin Fowler
- "Working Effectively with Legacy Code" - Michael Feathers
- "Clean Code" - Robert Martin

---

## 📞 CONTATOS E SUPORTE

**Responsável Técnico:** [Nome]  
**Canal de Comunicação:** Slack #refactor-project  
**Reuniões de Status:** Segundas 10h (Sprint Planning) + Sextas 16h (Review)  
**Documentação Detalhada:** [Link Confluence/Notion]

---

**FIM DO PLANO DE MIGRAÇÃO**

---

*Versão 1.0 - Criado em 11/02/2026*  
*Este documento deve ser revisado a cada fase completada*

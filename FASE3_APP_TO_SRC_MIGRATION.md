# Fase 3: Migração app/ → src/

## 📋 Objetivo

Migrar todo o código do diretório `app/` para `src/` seguindo os princípios SOLID já estabelecidos.

---

## 🗂️ Inventário app/ (Legacy)

### app/helpers/ (6 arquivos - 1200+ linhas)
1. **auth_helper.php** (103 linhas)
   - Middleware procedural de autenticação
   - Funções: `getLoginUrl()`, `isAdmin()`, `isDoador()`, `isLoggedIn()`
   - **Destino**: Integrar com `src/Services/AuthService.php` + criar `src/Middleware/AuthMiddleware.php`

2. **comum_helper.php** (662 linhas)
   - Funções procedurais CRUD: `buscar_comuns_paginated()`, `contar_comuns()`, `garantir_comum_por_codigo()`, `gerar_cnpj_unico()`
   - **Destino**: DEPRECAR (já existe `src/Services/ComumService.php`) + criar facade de compatibilidade

3. **comum_helper_facade.php**
   - Wrapper para comum_helper
   - **Destino**: Atualizar para usar `ComumService` ou deprecar

4. **csv_encoding_helper.php** (68 linhas)
   - Funções: `ip_normalizar_csv_encoding()`, `ip_fix_text_encoding()`
   - **Destino**: `src/Helpers/CsvHelper.php` (classe SOLID)

5. **env_helper.php**
   - Carregamento de variáveis de ambiente
   - **Destino**: DEPRECAR (já existe `src/Core/LerEnv.php`)

6. **uppercase_helper.php** (212 linhas)
   - Normalização de texto: `to_uppercase()`, `uppercase()`, `uppercase_fields()`
   - **Destino**: Integrar com `src/Helpers/ViewHelper.php` ou criar `src/Helpers/StringHelper.php`

### app/services/ (2 arquivos - 1260+ linhas)
1. **Relatorio141Generator.php** (~800 linhas estimadas)
   - Geração de relatório 14.1 HTML
   - Usa PDO diretamente (sem DI)
   - **Destino**: `src/Services/Relatorio141Service.php` (refatorar com DI, usar Repositories)

2. **produto_parser_service.php** (460 linhas)
   - Funções procedurais prefixadas `pp_*`: `pp_normaliza()`, `pp_normaliza_char()`, `pp_gerar_variacoes()`
   - **Destino**: `src/Services/ProdutoParserService.php` (converter para classe)

### app/controllers/ (25+ arquivos)
- **FormularioController.php** (~600 linhas)
- **create/** (4 controllers): DependenciaCreateController, ImportacaoPlanilhaController, ProdutoCreateController, UsuarioCreateController
- **read/** (5 controllers)
- **update/** (8 controllers)
- **delete/** (4 controllers)
- **Destino**: `__legacy_backup__/app/controllers/` (já temos controllers modernos em `src/Controllers/`)

### app/views/ (6 subdirs)
- **shared/** (3 arquivos): menu_planilha.php, menu_principal.php, menu_unificado.php
- **comuns/**, **dependencias/**, **planilhas/**, **produtos/**, **usuarios/**
- **Destino**: `src/Views/` (migrar views funcionais, deprecated views → __legacy_backup__)

### app/bootstrap.php
- Carrega config/bootstrap.php + helpers
- **Usado por 11 arquivos**: index.php, login.php, public/index.php, scripts/*
- **Destino**: Consolidar com `config/bootstrap.php`

---

## 🎯 Estratégia de Migração

### Fase 3.1: Helpers (app/helpers/ → src/Helpers/)

#### 3.1.1 - CsvHelper (NOVO)
```php
// src/Helpers/CsvHelper.php
class CsvHelper {
    public static function normalizarEncodingCsv(string $filePath): void
    public static function fixTextEncoding(?string $valor): ?string
}
```
**Origem**: `app/helpers/csv_encoding_helper.php`

#### 3.1.2 - StringHelper (NOVO)
```php
// src/Helpers/StringHelper.php
class StringHelper {
    public static function toUppercase(string $value): string
    public static function uppercaseFields(array &$data, array $fields): array
    public static function normalizeWhitespace(string $text): string
}
```
**Origem**: `app/helpers/uppercase_helper.php`

#### 3.1.3 - AuthMiddleware (NOVO)
```php
// src/Middleware/AuthMiddleware.php
class AuthMiddleware {
    private AuthService $authService;
    private SessionManager $sessionManager;
    
    public function handle(): void
    public function checkTimeout(): bool
    public function isPublicRoute(string $scriptPath): bool
}
```
**Origem**: `app/helpers/auth_helper.php`

#### 3.1.4 - Deprecate Legacy Helpers
- **env_helper.php** → Deprecated (usar `src/Core/LerEnv.php`)
- **comum_helper.php** → Deprecated (usar `src/Services/ComumService.php`)
- **comum_helper_facade.php** → Deprecated

### Fase 3.2: Services (app/services/ → src/Services/)

#### 3.2.1 - Relatorio141Service
```php
// src/Services/Relatorio141Service.php
class Relatorio141Service {
    private ComumRepository $comumRepository;
    private ProdutoRepository $produtoRepository;
    
    public function __construct(
        ComumRepository $comumRepository,
        ProdutoRepository $produtoRepository
    ) { }
    
    public function gerarRelatorio(int $idComum): array
    private function buscarDadosComum(int $idComum): array
    private function buscarProdutos(int $idComum): array
}
```
**Origem**: `app/services/Relatorio141Generator.php`

#### 3.2.2 - ProdutoParserService
```php
// src/Services/ProdutoParserService.php
class ProdutoParserService {
    public function normalizar(string $str): string
    public function normalizarChar(string $char): string
    public function gerarVariacoes(string $str): array
    public function calcularSimilaridade(string $a, string $b): float
}
```
**Origem**: `app/services/produto_parser_service.php` (converter funções `pp_*` em métodos)

### Fase 3.3: Controllers (app/controllers/ → __legacy_backup__)

**Ação**: Mover todo `app/controllers/` para `__legacy_backup__/app/controllers/`

**Razão**: Já temos controllers modernos em `src/Controllers/` com SOLID:
- ✅ `src/Controllers/AuthController.php` (DI com AuthService)
- ✅ `src/Controllers/UsuarioController.php` (DI com UsuarioService)
- ✅ `src/Controllers/ComumController.php` (DI com ComumService)
- ✅ `src/Controllers/BaseController.php`

Os controllers legados em `app/controllers/` podem ser usados como referência para implementar novos controllers quando necessário.

### Fase 3.4: Views (app/views/ → src/Views/)

#### Views já migradas (em src/Views/):
- ✅ layouts/app.php
- ✅ partials/header.php, footer.php, flash_messages.php, pagination.php
- ✅ comuns/index.php
- ✅ usuarios/index.php, create.php

#### Views a migrar:
1. **app/views/shared/menu_*.php** → `src/Views/partials/navigation/`
2. **app/views/planilhas/** → `src/Views/planilhas/`
3. **app/views/produtos/** → `src/Views/produtos/`
4. **app/views/dependencias/** → `src/Views/dependencias/`
5. Views duplicadas → `__legacy_backup__/app/views/`

### Fase 3.5: Bootstrap (app/bootstrap.php)

**Arquivos que usam `app/bootstrap.php` (11 arquivos)**:
- index.php
- login.php
- registrar_publico.php
- public/index.php
- public/assinatura_publica.php
- scripts/*.php (7 arquivos)

**Estratégia**:
1. Consolidar inicialização em `config/bootstrap.php`
2. Atualizar `app/bootstrap.php` para deprecation wrapper:
```php
<?php
/**
 * @deprecated Use config/bootstrap.php diretamente
 */
require_once __DIR__ . '/../config/bootstrap.php';
```
3. Atualizar gradualmente os 11 arquivos para usar `config/bootstrap.php`

---

## 📊 Checklist de Execução

### ✅ Fase 3.1: Helpers
- [ ] Criar `src/Helpers/CsvHelper.php`
- [ ] Criar `src/Helpers/StringHelper.php`
- [ ] Criar `src/Middleware/AuthMiddleware.php`
- [ ] Deprecar `app/helpers/env_helper.php`
- [ ] Deprecar `app/helpers/comum_helper.php`
- [ ] Deprecar `app/helpers/comum_helper_facade.php`
- [ ] Atualizar `app/helpers/auth_helper.php` → wrapper para AuthMiddleware
- [ ] Atualizar `app/helpers/uppercase_helper.php` → wrapper para StringHelper
- [ ] Atualizar `app/helpers/csv_encoding_helper.php` → wrapper para CsvHelper

### ✅ Fase 3.2: Services
- [ ] Criar `src/Services/Relatorio141Service.php`
- [ ] Criar `src/Services/ProdutoParserService.php`
- [ ] Deprecar `app/services/Relatorio141Generator.php`
- [ ] Deprecar `app/services/produto_parser_service.php`

### ✅ Fase 3.3: Controllers
- [ ] Mover `app/controllers/` → `__legacy_backup__/app/controllers/`

### ✅ Fase 3.4: Views
- [ ] Migrar `app/views/shared/` → `src/Views/partials/navigation/`
- [ ] Migrar `app/views/planilhas/` → `src/Views/planilhas/`
- [ ] Migrar `app/views/produtos/` → `src/Views/produtos/`
- [ ] Migrar `app/views/dependencias/` → `src/Views/dependencias/`
- [ ] Mover views duplicadas → `__legacy_backup__/app/views/`

### ✅ Fase 3.5: Bootstrap
- [ ] Atualizar `app/bootstrap.php` para deprecation wrapper
- [ ] Atualizar 11 arquivos para usar `config/bootstrap.php` diretamente

### ✅ Fase 3.6: Validação
- [ ] Executar `grep -r "app/helpers" .` → Nenhuma referência direta
- [ ] Executar `grep -r "app/services" .` → Nenhuma referência direta
- [ ] Executar `grep -r "app/controllers" .` → Apenas legacy backup
- [ ] Testar login/autenticação
- [ ] Testar CRUD de comuns
- [ ] Testar CRUD de usuários
- [ ] Verificar relatórios funcionando

---

## 🎯 Resultado Esperado

### Estrutura Final:
```
src/
  Core/
    ConnectionManager.php ✅
    SessionManager.php ✅
    ViewRenderer.php ✅
    LerEnv.php ✅
    Configuracoes.php ✅
  
  Contracts/
    RepositoryInterface.php ✅
    PaginableInterface.php ✅
    AuthServiceInterface.php ✅
  
  Services/
    AuthService.php ✅
    UsuarioService.php ✅
    ComumService.php ✅
    Relatorio141Service.php 🆕
    ProdutoParserService.php 🆕
  
  Repositories/
    BaseRepository.php ✅
    UsuarioRepository.php ✅
    ComumRepository.php ✅
  
  Helpers/
    FormHelper.php ✅
    PaginationHelper.php ✅
    AlertHelper.php ✅
    ViewHelper.php ✅
    CnpjValidator.php ✅
    NotificadorTelegram.php ✅
    CsvHelper.php 🆕
    StringHelper.php 🆕
  
  Middleware/
    AuthMiddleware.php 🆕
  
  Controllers/
    AuthController.php ✅
    BaseController.php ✅
    UsuarioController.php ✅
    ComumController.php ✅
  
  Views/
    layouts/app.php ✅
    partials/ ✅
    navigation/ 🆕
    comuns/ ✅
    usuarios/ ✅
    planilhas/ 🆕
    produtos/ 🆕
    dependencias/ 🆕

app/
  bootstrap.php (DEPRECATED wrapper)
  helpers/ (DEPRECATED wrappers para compatibilidade)
  services/ (DEPRECATED wrappers para compatibilidade)
  
__legacy_backup__/
  app/
    controllers/
    views/ (duplicadas/obsoletas)
```

### Benefícios:
✅ 100% do código ativo em `src/` com SOLID  
✅ `app/` apenas com wrappers de compatibilidade  
✅ Legacy code isolado em `__legacy_backup__/`  
✅ Fácil identificação de dependencies obsoletas  
✅ Estrutura pronta para namespacing PSR-4  

---

## 📝 Notas Técnicas

### Compatibilidade Retroativa
- Manter wrappers em `app/helpers/` para código legacy que ainda não foi migrado
- Wrappers devem delegar para classes em `src/`
- Adicionar `@deprecated` notices com mensagens claras

### Namespacing Futuro
Após migração completa, adicionar namespaces PSR-4:
```php
namespace App\Services;
namespace App\Controllers;
namespace App\Helpers;
namespace App\Middleware;
```

### Autoloading
Atualizar `composer.json` para incluir PSR-4 autoloading:
```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

---

**Status**: 🚀 Pronto para execução  
**Duração Estimada**: 2-3 dias  
**Risco**: Baixo (com wrappers de compatibilidade)

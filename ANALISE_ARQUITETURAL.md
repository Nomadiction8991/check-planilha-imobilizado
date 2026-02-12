# ANÁLISE ARQUITETURAL - Sistema Check Planilha Imobilizado CCB

**Data da Análise:** 11/02/2025  
**Objetivo:** Entender profundamente o sistema atual antes de reestruturação organizacional

---

## 1. VISÃO GERAL DO SISTEMA

### 1.1. Propósito
Sistema de gestão de patrimônio imobiliário para CCB (Congregação Cristã no Brasil), incluindo:
- Importação de planilhas Excel com inventário de produtos
- Gestão de usuários, comuns (organizações), dependências, tipos de bens
- Geração de relatórios (14.1 a 14.8)
- Sistema de autenticação e controle de acesso
- Etiquetagem e checagem de produtos

### 1.2. Stack Tecnológico Atual
- **PHP:** 8.3.6
- **Banco de Dados:** MySQL 8.0 (Docker)
- **Servidor Web:** Apache 2.4 com mod_rewrite
- **Frontend:** Bootstrap 5 + JavaScript vanilla
- **Bibliotecas PHP:** 
  - PhpOffice/PhpSpreadsheet (importação Excel)
  - Phinx (migrations - recém-adicionado)
  - voku/portable-utf8 (normalização UTF-8)
- **Infraestrutura:** Docker Compose (web + db)

### 1.3. Estrutura de Diretórios
```
.
├── app/                          # Sistema legado principal
│   ├── bootstrap.php             # Bootstrap da aplicação
│   ├── controllers/              # Controllers organizados por operação
│   │   ├── create/               # Criação (8 arquivos)
│   │   ├── read/                 # Leitura (5 arquivos)
│   │   ├── update/               # Atualização (8 arquivos)
│   │   ├── delete/               # Exclusão (4 arquivos)
│   │   └── FormularioController.php  # Formulário 14.1
│   ├── helpers/                  # Funções auxiliares procedurais
│   │   ├── auth_helper.php       # Middleware de autenticação
│   │   ├── comum_helper.php      # CRUD de comuns (662 linhas)
│   │   ├── env_helper.php        # Carregamento .env
│   │   └── uppercase_helper.php  # Conversão uppercase
│   ├── services/                 # Lógica de negócio
│   │   ├── produto_parser_service.php  # Parser de produtos (460 linhas)
│   │   └── Relatorio141Generator.php   # Geração relatório 14.1
│   └── views/                    # Templates de visualização
│       ├── comuns/               # Views de comuns
│       ├── dependencias/         # Views de dependências
│       ├── layouts/              # Layouts compartilhados
│       ├── planilhas/            # Views de planilhas/relatórios
│       ├── produtos/             # Views de produtos
│       ├── shared/               # Menus compartilhados
│       └── usuarios/             # Views de usuários
├── config/                       # Configurações centralizadas
│   ├── app_config.php            # Configurações da aplicação
│   ├── app.php                   # Config adicional
│   ├── bootstrap.php             # Bootstrap central (sessão, UTF-8, timezone)
│   └── database.php              # Classe Database + $conexao global
├── database/                     # Migrations e schema
│   └── migrations/
│       └── 20260211120000_initial_schema.php
├── public/                       # Document root (novo)
│   ├── index.php                 # Front controller (rotas via MapaRotas)
│   ├── assinatura_publica.php    # Formulário público
│   └── assets/                   # CSS, JS, imagens
├── src/                          # Nova estrutura MVC (em migração)
│   ├── Controllers/
│   │   └── AuthController.php    # ✓ Migrado
│   ├── Core/
│   │   ├── Configuracoes.php     # Gerenciador de configurações
│   │   ├── Database.php          # Wrapper de conexão
│   │   └── Renderizador.php      # Renderização de views
│   ├── Helpers/                  # Vazios (pendente migração)
│   ├── Routes/
│   │   └── MapaRotas.php         # Definição de rotas
│   ├── Services/
│   │   └── AuthService.php       # ✓ Migrado
│   └── Views/
│       └── auth/
│           └── login.php         # ✓ Migrado
├── scripts/                      # Scripts de manutenção/debug
├── storage/                      # Armazenamento temporário
│   ├── logs/                     # Logs da aplicação
│   └── tmp/                      # Arquivos temporários
├── vendor/                       # Dependências Composer
├── index.php                     # ⚠️ Entry point LEGADO (raiz)
├── login.php                     # ⚠️ Login LEGADO (duplicado)
├── logout.php                    # ⚠️ Logout LEGADO
├── registrar_publico.php         # ⚠️ Redirect wrapper
├── .env                          # Variáveis de ambiente
├── .env.example                  # Template de .env
├── composer.json                 # Dependências
├── docker-compose.yml            # Orquestração Docker
└── phinx.yml                     # Configuração migrations
```

---

## 2. ANÁLISE DE PADRÕES ARQUITETURAIS

### 2.1. Padrões Identificados

#### ✅ **Padrões Positivos**
1. **Separação de Concerns Inicial:** Controllers organizados por operação CRUD
2. **Bootstrap Centralizado:** `config/bootstrap.php` configura sessão, UTF-8, timezone
3. **Helpers Específicos:** Funções agrupadas por domínio (auth, comum, uppercase)
4. **Service Layer Emergente:** `produto_parser_service.php`, `Relatorio141Generator.php`
5. **Configurações Centralizadas:** `.env` para credenciais sensíveis
6. **Migrações de Banco:** Phinx configurado com schema inicial
7. **Autoload PSR-4:** Composer mapeando `App\` => `src/`, `Old\` => root

#### ⚠️ **Anti-Padrões Críticos**

##### 2.1.1. **Global State Pollution**
**Problema:** Variável global `$conexao` usada em TODO o sistema.

**Evidência:**
```php
// config/database.php (linha 57)
$database = new Database();
$conexao = $database->getConnection();  // ⚠️ GLOBAL
```

**Impacto:**
- 30+ arquivos dependem de `$conexao` global
- Controllers não são testáveis unitariamente
- Impossível injetar dependências
- Violação de princípios SOLID (Dependency Inversion)

**Localização:**
- `app/controllers/create/UsuarioCreateController.php` (linha 108)
- `app/controllers/delete/ProdutoDeleteController.php` (linha 20)
- `app/views/planilhas/produto_check_view.php` (linha 35) ⚠️ **VIEW com SQL!**
- [+27 arquivos adicionais]

##### 2.1.2. **Views Incluindo Controllers**
**Problema:** Views executam lógica de negócio via `include` de controllers.

**Evidência:**
```php
// app/views/usuarios/usuarios_listar.php (linha 6)
include __DIR__ . '/../../../app/controllers/read/UsuarioListController.php';

// app/views/dependencias/dependencia_criar.php (linha 6)
include __DIR__ . '/../../../app/controllers/create/DependenciaCreateController.php';
```

**Impacto:**
- Inversão do fluxo MVC (View chama Controller ao invés do contrário)
- Controllers executados como side effect de renderização
- Reutilização de controllers impossível (acoplado à view)
- Testes unitários inviáveis

**Ocorrências:** 8 arquivos de view incluem controllers diretamente

##### 2.1.3. **SQL Direto em Views**
**Problema:** Views executam queries SQL diretamente.

**Evidência:**
```php
// app/views/planilhas/produto_check_view.php (linha 35)
$stmt_STATUS = $conexao->prepare('SELECT checado, imprimir_etiqueta, imprimir_14_1 
                                   FROM produtos 
                                   WHERE id_produto = :id_produto AND comum_id = :comum_id');

// app/views/planilhas/produto_copiar_etiquetas.php (linha 16)
$stmt_planilha = $conexao->prepare($sql_planilha);

// app/views/usuarios/usuario_ver.php (linha 12)
$stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
```

**Impacto:**
- Lógica de dados misturada com apresentação
- Views impossíveis de testar sem banco
- Duplicação de queries entre views e controllers
- Violação extrema de SRP (Single Responsibility Principle)

**Ocorrências:** 5+ views executam SQL diretamente

##### 2.1.4. **Controllers Monolíticos**
**Problema:** Controllers com responsabilidades excessivas.

**Evidência:**
```php
// app/controllers/create/ImportacaoPlanilhaController.php
// 1480 LINHAS!
// Responsabilidades:
// - Upload de arquivo
// - Parsing Excel (PhpSpreadsheet)
// - Validação de dados
// - Correção de encoding
// - Mapeamento de colunas
// - Detecção de tipos de bens
// - Inserção em lote no banco
// - Gerenciamento de jobs assíncronos
// - Logging de erros
// - Geração de relatórios de importação
```

**Outros Arquivos Grandes:**
- `FormularioController.php`: Geração completa do formulário 14.1
- `comum_helper.php`: 662 linhas de funções CRUD procedurais
- `produto_parser_service.php`: 460 linhas de parsing

**Impacto:**
- Manutenção extremamente difícil
- Testes unitários impossíveis (muitas responsabilidades)
- Violação grave de SRP
- Reutilização de código inviável

##### 2.1.5. **Helpers Procedurais**
**Problema:** Funções globais sem namespacing adequado.

**Evidência:**
```php
// app/helpers/comum_helper.php
function buscar_comuns_paginated(...) { }
function contar_comuns(...) { }
function garantir_comum_por_codigo(...) { }
function gerar_cnpj_unico() { }

// app/services/produto_parser_service.php
function pp_normaliza($str) { }
function pp_gerar_variacoes($str) { }
function pp_match_fuzzy($str1, $str2) { }
function pp_extrair_codigo_prefixo($texto) { }
```

**Impacto:**
- Namespace global poluído
- Risco de colisão de nomes
- Impossível usar injeção de dependências
- Testes requerem inclusão de arquivo inteiro

##### 2.1.6. **Entry Points Duplicados**
**Problema:** Sistema possui múltiplos pontos de entrada.

**Evidência:**
- `/index.php` (raiz) - Sistema legado principal
- `/login.php` (raiz) - Login legado
- `/logout.php` (raiz) - Logout legado
- `/public/index.php` - Front controller novo (via MapaRotas)

**Impacto:**
- Confusão sobre qual arquivo usar
- Rotas inconsistentes (`/index.php` vs `/public`)
- Migração gradual causando duplicação

### 2.2. Fluxo de Requisição

#### **Fluxo Legado (Atual - Maioria das Requisições)**
```
┌─────────────────────────────────────────────────────────────────┐
│ 1. index.php (raiz)                                             │
│    └─> require app/bootstrap.php                                │
│        ├─> config/bootstrap.php (sessão, UTF-8, timezone)       │
│        ├─> config/database.php ($conexao global)                │
│        ├─> app_config.php                                       │
│        └─> helpers (auth, comum, uppercase)                     │
│    └─> verificar_login() (auth_helper.php)                      │
│    └─> Lógica do Controller EMBUTIDA (pagination, filtros)      │
│    └─> buscar_comuns_paginated() (comum_helper.php)             │
│    └─> HTML renderizado inline                                  │
│    └─> include app/views/layouts/app_wrapper.php                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 2. app/views/usuarios/usuarios_listar.php                       │
│    └─> require app/bootstrap.php                                │
│    └─> include app/controllers/read/UsuarioListController.php   │ ⚠️
│        └─> Executa query SQL                                    │
│        └─> Define variáveis $usuarios, $total, etc.             │
│    └─> HTML com loop foreach($usuarios)                         │
│    └─> include app/views/layouts/app_wrapper.php                │
└─────────────────────────────────────────────────────────────────┘
```

#### **Fluxo Moderno (Apenas Login Migrado)**
```
┌─────────────────────────────────────────────────────────────────┐
│ 3. GET /login                                                   │
│    └─> public/index.php                                         │
│        └─> app/bootstrap.php                                    │
│        └─> vendor/autoload.php                                  │
│        └─> src/Routes/MapaRotas.php                             │
│            └─> AuthController::login()                          │
│                └─> Renderizador::render('auth/login.php')       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 4. POST /login                                                  │
│    └─> public/index.php                                         │
│        └─> MapaRotas::resolver()                                │
│            └─> AuthController::authenticate()                   │
│                └─> AuthService::authenticate($email, $senha)    │
│                    └─> Query SQL com $conexao global            │ ⚠️
│                    └─> password_verify()                        │
│                    └─> $_SESSION['usuario_id'] = ...            │
│                └─> header('Location: ../index.php')             │ ⚠️
└─────────────────────────────────────────────────────────────────┘
```

**Observações:**
- ⚠️ Mesmo no código migrado, `AuthService` ainda usa `$conexao` global
- ⚠️ Após login bem-sucedido, redireciona para `../index.php` (sistema legado)
- ✅ Roteamento via MapaRotas é melhor que arquivos soltos

---

## 3. INVENTÁRIO DE ARQUIVOS

### 3.1. Controllers

| Arquivo | Linhas | Tipo | Responsabilidades | Status |
|---------|--------|------|-------------------|--------|
| `ImportacaoPlanilhaController.php` | 1480 | Create | Upload, parsing Excel, validação, importação em lote, jobs | ⚠️ MONOLÍTICO |
| `UsuarioCreateController.php` | 232 | Create | Criação de usuário, validação CPF/email, hash senha | OK |
| `ProdutoCreateController.php` | ~200 | Create | Criação de produto, validação tipo/dependência | OK |
| `DependenciaCreateController.php` | ~150 | Create | Criação de dependência, validação código único | OK |
| `UsuarioListController.php` | ~180 | Read | Listagem paginada, filtros nome/status | ⚠️ Incluído por view |
| `ProdutoListController.php` | 206 | Read | Listagem produtos, filtros múltiplos, joins | OK |
| `UsuarioUpdateController.php` | ~250 | Update | Edição usuário, validação, update condicional | OK |
| `ProdutoUpdateController.php` | ~300 | Update | Edição produto, validação campos | OK |
| `ComumUpdateController.php` | ~200 | Update | Edição comum, validação CNPJ | OK |
| `ProdutoObservacaoController.php` | ~150 | Update | Update observação produto | OK |
| `UsuarioDeleteController.php` | ~80 | Delete | Exclusão usuário | OK |
| `ProdutoDeleteController.php` | ~100 | Delete | Exclusão produto | OK |
| `DependenciaDeleteController.php` | ~80 | Delete | Exclusão dependência | OK |
| `FormularioController.php` | ~600 | Special | Geração formulário 14.1, preenchimento campos | ⚠️ GRANDE |
| **src/Controllers/AuthController.php** | ~80 | Auth | Login/autenticação | ✅ MIGRADO |

**Total:** 15 controllers, ~4.300 linhas de código

**Problemas:**
- 1 arquivo com 1480 linhas (ImportacaoPlanilhaController)
- Controllers executados via `include` de views
- Uso de `$conexao` global em todos
- Mistura de validação, lógica de negócio e acesso a dados

### 3.2. Views

| Arquivo | Linhas | Tipo | Problemas Identificados |
|---------|--------|------|------------------------|
| `usuarios_listar.php` | 223 | List | ⚠️ Include controller, bootstrap duplicado |
| `usuario_criar.php` | ~300 | Form | ⚠️ Include controller |
| `usuario_editar.php` | ~350 | Form | ⚠️ Include controller (linha 12) |
| `usuario_ver.php` | ~150 | Detail | ⚠️ SQL direto (linha 12) |
| `produtos_listar.php` | ~250 | List | ⚠️ Include controller |
| `produtos_limpar_edicoes.php` | ~200 | Action | ⚠️ SQL direto (linha 54) |
| `produto_check_view.php` | ~180 | Action | ⚠️ SQL direto (linhas 35, 57) |
| `produto_copiar_etiquetas.php` | ~250 | Action | ⚠️ SQL direto (linhas 16, 25, 59, 80) |
| `planilha_importar.php` | ~300 | Form | OK (apenas UI) |
| `relatorio141_view_new.php` | ~500 | Report | OK (JavaScript pesado) |
| `relatorio_visualizar.php` | ~400 | Report | OK |
| `menu_principal.php` | ~250 | Menu | ⚠️ Include app_wrapper |
| `menu_planilha.php` | ~300 | Menu | ⚠️ Include app_wrapper |
| `app_wrapper.php` | ~650 | Layout | Layout wrapper Bootstrap 5 |
| **src/Views/auth/login.php** | ~120 | Auth | ✅ MIGRADO (sem SQL, sem includes) |

**Total:** ~50 arquivos de view, ~7.000 linhas

**Problemas Críticos:**
- 8 views incluem controllers via `include`
- 5 views executam SQL diretamente
- Duplicação de `require bootstrap.php` em cada arquivo
- Mistura de lógica PHP com HTML

### 3.3. Helpers e Services

| Arquivo | Linhas | Tipo | Função | Problemas |
|---------|--------|------|--------|-----------|
| `auth_helper.php` | 103 | Middleware | Verificação autenticação, redirect login | ✅ OK |
| `comum_helper.php` | 662 | Data Access | CRUD comuns (buscar, contar, garantir, gerar CNPJ) | ⚠️ Procedural, usa $conexao global |
| `uppercase_helper.php` | ~50 | Util | Conversão para maiúsculas | ✅ OK |
| `env_helper.php` | ~80 | Config | Carregamento .env | ✅ OK |
| `produto_parser_service.php` | 460 | Business Logic | Parsing de produtos Excel (normalização, fuzzy matching) | ⚠️ Funções globais prefixadas `pp_*` |
| `Relatorio141Generator.php` | ~800 | Business Logic | Geração relatório 14.1 em HTML | ⚠️ Classe mas usa $conexao global |
| **src/Services/AuthService.php** | ~60 | Business Logic | Autenticação usuário | ✅ MIGRADO (mas ainda usa $conexao global) |

**Total:** 7 arquivos, ~2.200 linhas

**Observações:**
- `comum_helper.php` deveria ser uma classe Repository
- `produto_parser_service.php` deveria ser classe com métodos
- Funções globais poluem namespace

### 3.4. Core e Configuração

| Arquivo | Linhas | Função | Status |
|---------|--------|--------|--------|
| `config/bootstrap.php` | 99 | Inicialização (sessão, UTF-8, timezone, autoload) | ✅ BEM ESTRUTURADO |
| `config/database.php` | 59 | Classe Database + instância global | ⚠️ Cria $conexao global |
| `config/app_config.php` | ~100 | Constantes da aplicação | ✅ OK |
| `app/bootstrap.php` | ~50 | Bootstrap secundário (includes helpers) | ⚠️ Duplicação de propósito |
| `src/Core/Configuracoes.php` | ~100 | Gerenciamento de configurações | ✅ NOVO |
| `src/Core/Database.php` | ~80 | Wrapper de conexão | ✅ NOVO (redundante com config/database.php) |
| `src/Core/Renderizador.php` | ~60 | Renderização de views | ✅ NOVO |

**Problemas:**
- 2 arquivos bootstrap (`config/bootstrap.php` + `app/bootstrap.php`)
- 2 classes Database (`config/database.php` + `src/Core/Database.php`)
- `$conexao` global impede uso de Database como dependency

### 3.5. Entry Points

| Arquivo | Função | Status | Problema |
|---------|--------|--------|----------|
| `index.php` (raiz) | Entry point principal (lista comuns) | LEGADO ATIVO | ⚠️ Controller + View inline |
| `login.php` (raiz) | Login | LEGADO DUPLICADO | ⚠️ Duplica src/Controllers/AuthController |
| `logout.php` (raiz) | Logout | LEGADO ATIVO | ✅ Simples (session_destroy + redirect) |
| `registrar_publico.php` (raiz) | Redirect para usuario_criar.php | LEGADO ATIVO | ⚠️ Wrapper desnecessário |
| `public/index.php` | Front controller (MapaRotas) | NOVO | ✅ Apenas rotas `/login` e `/` funcionais |
| `public/assinatura_publica.php` | Formulário público | LEGADO ATIVO | ⚠️ Não migrado para rotas |

**Decisão Necessária:**
- Manter ambos entry points durante migração gradual?
- Redirecionar tudo para `public/index.php`?
- Deprecar arquivos raiz após migração completa?

---

## 4. ANÁLISE DE DEPENDÊNCIAS

### 4.1. Grafo de Dependências (Principais)

```
┌──────────────────────────────┐
│ config/bootstrap.php         │ ← Inicialização global
│  ├─ session_start()          │
│  ├─ UTF-8 encoding           │
│  ├─ timezone America/Cuiaba  │
│  ├─ vendor/autoload.php      │
│  └─ app/helpers/env_helper   │
└──────────────────────────────┘
            ⬇
┌──────────────────────────────┐
│ config/database.php          │
│  └─ $conexao [GLOBAL]        │ ← ⚠️ PONTO CRÍTICO
└──────────────────────────────┘
            ⬇
┌──────────────────────────────┬──────────────────────────────┐
│ app/helpers/comum_helper.php │ app/controllers/**/*.php     │
│  └─ usa $conexao             │  └─ usa $conexao             │
└──────────────────────────────┴──────────────────────────────┘
            ⬇
┌──────────────────────────────┐
│ app/views/**/*.php           │
│  ├─ include controller       │ ← ⚠️ ANTI-PADRÃO
│  └─ usa $conexao direto      │ ← ⚠️ ANTI-PADRÃO
└──────────────────────────────┘
```

### 4.2. Dependências Circulares

**Encontradas:**
- Nenhuma dependência circular explícita
- Views incluem controllers (unidirecional, mas problemático)

### 4.3. Dependências Externas (Composer)

```json
{
  "phpoffice/phpspreadsheet": "^2.4",      // Importação Excel
  "robmorgan/
": "^0.16.10",   // Migrations
  "symfony/yaml": "^7.4",                  // Phinx dependency
  "voku/portable-utf8": "^6.0",            // UTF-8 normalização
  "maennchen/zipstream-php": "^3.1",       // Geração ZIP
  "setasign/fpdf": "^1.8"                  // Geração PDF
}
```

**Observações:**
- Bibliotecas bem escolhidas
- PhpSpreadsheet usado apenas em ImportacaoPlanilhaController
- FPDF não encontrado em grep (possível dependência não utilizada)

---

## 5. PADRÕES DE CÓDIGO

### 5.1. Convenções de Nomenclatura

| Tipo | Padrão Atual | Avaliação |
|------|-------------|-----------|
| Controllers | `{Entidade}{Acao}Controller.php` | ✅ BOM (UsuarioCreateController) |
| Views | `{entidade}_{acao}.php` | ✅ BOM (usuario_criar.php) |
| Helpers | `{dominio}_helper.php` | ✅ BOM |
| Funções Helper | `{verbo}_{entidade}_{complemento}()` | ✅ BOM (buscar_comuns_paginated) |
| Funções Parser | `pp_{acao}()` | ⚠️ Prefixo não intuitivo |
| Classes (novo) | PascalCase | ✅ BOM (AuthController) |
| Métodos | camelCase | ✅ BOM |
| Variáveis | snake_case | ⚠️ Inconsistente (mistura com camelCase) |
| Constantes | UPPER_SNAKE_CASE | ✅ BOM |

### 5.2. Segurança

#### ✅ **Boas Práticas**
1. **Prepared Statements:** 100% das queries usam PDO prepare/execute
2. **Password Hashing:** `password_hash()` e `password_verify()`
3. **Session Security:** Flags httponly, secure, samesite configurados
4. **HTML Escaping:** `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` usado
5. **CSRF Protection:** NÃO IMPLEMENTADO ⚠️

#### ⚠️ **Vulnerabilidades Potenciais**
1. **Sem CSRF Tokens:** Formulários sem proteção CSRF
2. **Sem Rate Limiting:** Login pode sofrer brute force
3. **Redirect Aberto:** `header('Location: ../index.php')` sem validação
4. **File Upload:** ImportacaoPlanilhaController sem validação MIME type robusta
5. **SQL em Views:** Views com acesso direto ao banco (risco de injeção se modificadas)

### 5.3. Performance

#### ⚠️ **Problemas Identificados**
1. **N+1 Queries:** Não detectado (queries aparentam usar JOINs)
2. **Queries Não Indexadas:** Já corrigido (índices adicionados recentemente)
3. **Sem Cache:** Nenhuma camada de cache (queries repetitivas)
4. **Import em Memória:** ImportacaoPlanilhaController carrega Excel completo (risco com arquivos grandes)

#### ✅ **Otimizações Presentes**
1. **Paginação:** Implementada em listagens
2. **Prepared Statements:** Evita parsing repetido
3. **UTF-8 Configurado:** Encoding consistente evita conversões

### 5.4. Testes

**Status:** ⚠️ **ZERO TESTES AUTOMATIZADOS**

**Diretórios Inexistentes:**
- `tests/`
- `phpunit.xml`

**Impacto:**
- Refatoração perigosa (sem rede de segurança)
- Risco de regressão alto
- Impossível garantir comportamento após mudanças

---

## 6. RISCOS DE MIGRAÇÃO

### 6.1. Riscos CRÍTICOS (🔴)

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| **Quebra de Views que incluem Controllers** | ALTA | ALTO | Criar controllers com métodos públicos que retornam dados ao invés de ecoar HTML |
| **$conexao global em 30+ arquivos** | ALTA | ALTO | Refatorar gradualmente, manter $conexao até migração completa |
| **SQL direto em Views** | MÉDIA | ALTO | Extrair para Repositories antes de mover views |
| **ImportacaoPlanilhaController (1480 linhas)** | ALTA | MÉDIO | Dividir em Services antes de mover (PlanilhaUploadService, ExcelParserService, ProductImportService) |
| **Sem testes automatizados** | ALTA | CRÍTICO | Criar testes de integração básicos ANTES de refatorar |

### 6.2. Riscos MÉDIOS (🟡)

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| **Duplicação de entry points** | MÉDIA | MÉDIO | Manter sistema legado funcional até migração 100% completa |
| **Helpers procedurais globais** | BAIXA | MÉDIO | Criar classes wrapper, manter funções como facade temporário |
| **Bootstrap duplicado** | BAIXA | BAIXO | Unificar em config/bootstrap.php, deprecar app/bootstrap.php |
| **Classes Database duplicadas** | BAIXA | BAIXO | Padronizar em src/Core/Database.php, remover $conexao global |

### 6.3. Riscos BAIXOS (🟢)

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| **Convenções de nomenclatura** | BAIXA | BAIXO | PSR-4 autoloading já configurado |
| **Dependências externas** | BAIXA | BAIXO | Composer.json bem estruturado |

---

## 7. PLANO DE MIGRAÇÃO PROPOSTO

### 7.1. Princípios Norteadores

1. **Migração Gradual:** Sistema legado continua funcionando
2. **Test-First:** Criar testes de integração ANTES de refatorar
3. **Zero Downtime:** Nenhuma funcionalidade para durante migração
4. **Data Integrity:** Migrações de banco reversíveis
5. **Code Freeze Parcial:** Evitar novos features durante reestruturação

### 7.2. Fases de Migração

#### **FASE 0: PREPARAÇÃO (1-2 semanas)**
**Objetivo:** Criar rede de segurança para refatoração

**Tarefas:**
1. ✅ **Análise arquitetural completa** (CONCLUÍDO)  
2. ⬜ **Criar testes de integração básicos**  
   - Testar fluxo de login  
   - Testar CRUD de usuários  
   - Testar importação de planilha  
   - Testar geração de relatório 14.1  
3. ⬜ **Configurar PHPUnit**  
4. ⬜ **Criar CI/CD pipeline** (GitHub Actions ou similar)  
5. ⬜ **Documentar APIs internas** (endpoints, contratos de dados)  

**Critério de Conclusão:** Pelo menos 50% de cobertura de integração nos fluxos críticos

---

#### **FASE 1: INFRAESTRUTURA (1 semana)**
**Objetivo:** Padronizar camadas de base

**Tarefas:**
1. ⬜ **Criar src/Core/ConnectionManager.php**  
   - Singleton ou Service Container para gerenciar $conexao  
   - Manter $conexao global como backward compatibility  
2. ⬜ **Criar src/Repositories/BaseRepository.php**  
   - Classe abstrata com CRUD genérico  
   - Recebe ConnectionManager via construtor  
3. ⬜ **Criar src/Middleware/**  
   - `AuthMiddleware.php` (migrar auth_helper.php)  
   - `CsrfMiddleware.php` (adicionar proteção CSRF)  
4. ⬜ **Unificar Bootstrap**  
   - Manter `config/bootstrap.php` como único ponto  
   - Deprecar `app/bootstrap.php`  
5. ⬜ **Criar src/Core/Request.php e src/Core/Response.php**  
   - Encapsular $_GET, $_POST, $_SERVER  
   - Métodos para JSON response, redirects  

**Critério de Conclusão:** Testes passando com novo ConnectionManager

---

#### **FASE 2: EXTRAÇÃO DE REPOSITORIES (2 semanas)**
**Objetivo:** Remover SQL de Controllers e Views

**Ordem de Migração:**
1. ⬜ **src/Repositories/UsuarioRepository.php**  
   - Métodos: findById, findByEmail, findByCpf, create, update, delete, paginate  
   - Substituir uso em UsuarioCreateController, UsuarioUpdateController, etc.  
2. ⬜ **src/Repositories/ComumRepository.php**  
   - Migrar comum_helper.php para classe  
   - Métodos: findAll, findById, findByCodigo, create, garantir, gerarCnpjUnico  
3. ⬜ **src/Repositories/ProdutoRepository.php**  
   - SQL complexos com JOINs (tipos_bens, dependencias)  
   - Métodos: findWithRelations, paginate, bulkInsert, updateObservacao  
4. ⬜ **src/Repositories/DependenciaRepository.php**  
5. ⬜ **src/Repositories/TipoBemRepository.php**  

**Para cada Repository:**
- Criar classe com métodos públicos  
- Escrever testes unitários (mocks de PDO)  
- Substituir SQL em controllers  
- Substituir SQL em views (⚠️ CRÍTICO)  
- Manter funções helper como wrapper temporário  

**Critério de Conclusão:** Zero SQL direto em Views, 80% SQL em Repositories

---

#### **FASE 3: MIGRAÇÃO DE CONTROLLERS (3 semanas)**
**Objetivo:** Mover controllers para src/Controllers/ com injeção de dependências

**Ordem de Migração:**
1. ⬜ **Controllers Simples (CRUD básico)**  
   - UsuarioController (create, update, delete)  
   - DependenciaController  
   - ComumController  
2. ⬜ **Controllers Médios**  
   - ProdutoController (múltiplos filtros, joins)  
3. ⬜ **Controllers Complexos**  
   - PlanilhaController (importação - DIVIDIR EM SERVICES!)  

**Para cada Controller:**
- Criar em `src/Controllers/`  
- Receber Repositories via construtor  
- Métodos retornam dados (não ecoam HTML)  
- Usar Renderizador para views  
- Adicionar à MapaRotas.php  
- Criar rota de compatibilidade legada (redirect ou proxy)  
- Testes unitários com mocks de Repositories  

**Exemplo:**
```php
// ANTES (app/controllers/create/UsuarioCreateController.php)
$stmt = $conexao->prepare('INSERT INTO usuarios...');
echo '<div class="alert alert-success">...</div>';

// DEPOIS (src/Controllers/UsuarioController.php)
class UsuarioController {
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        private Renderizador $view
    ) {}
    
    public function create(Request $request): Response {
        $data = $request->post();
        $usuario = $this->usuarioRepo->create($data);
        return $this->view->render('usuarios/usuario_criar.php', [
            'success' => 'Usuário criado com sucesso!',
            'usuario' => $usuario
        ]);
    }
}
```

**Critério de Conclusão:** Todos controllers CRUD migrados, rotas funcionais

---

#### **FASE 4: EXTRAÇÃO DE SERVICES (2 semanas)**
**Objetivo:** Extrair lógica de negócio complexa

**Services a Criar:**
1. ⬜ **src/Services/PlanilhaImportService.php**  
   - Extrair de ImportacaoPlanilhaController (1480 linhas)  
   - Métodos: uploadFile, validateFile, parseExcel  
2. ⬜ **src/Services/ExcelParserService.php**  
   - Migrar produto_parser_service.php (funções globais)  
   - Métodos: normalizarTexto, gerarVariacoes, matchFuzzy, extrairCodigo  
3. ⬜ **src/Services/ProdutoImportService.php**  
   - Lógica de importação em lote  
   - Detecção de tipos de bens, mapeamento de colunas  
4. ⬜ **src/Services/Relatorio141Service.php**  
   - Migrar Relatorio141Generator.php  
   - Geração HTML do formulário 14.1  
5. ⬜ **src/Services/JobManagerService.php**  
   - Gerenciamento de jobs assíncronos de importação  

**Critério de Conclusão:** ImportacaoPlanilhaController reduzido a <200 linhas

---

#### **FASE 5: MIGRAÇÃO DE VIEWS (2 semanas)**
**Objetivo:** Remover includes de controllers, padronizar views

**Tarefas:**
1. ⬜ **Remover `include` de controllers**  
   - Controllers passam dados para views via Renderizador  
   - Views recebem variáveis como parâmetros  
2. ⬜ **Migrar para src/Views/**  
   - Estrutura: `src/Views/{dominio}/{acao}.php`  
   - Exemplo: `src/Views/usuarios/listar.php`  
3. ⬜ **Criar ViewHelpers**  
   - src/Helpers/FormHelper.php (geração de formulários)  
   - src/Helpers/PaginationHelper.php  
   - src/Helpers/AlertHelper.php  
4. ⬜ **Padronizar Layouts**  
   - src/Views/layouts/app.php (layout principal)  
   - src/Views/layouts/auth.php (layout login)  
   - src/Views/partials/ (menus, headers, footers)  

**Critério de Conclusão:** Zero `include` de controllers em views

---

#### **FASE 6: ROTEAMENTO COMPLETO (1 semana)**
**Objetivo:** Migrar todas URLs para MapaRotas.php

**Tarefas:**
1. ⬜ **Expandir MapaRotas.php**  
   - Adicionar todas rotas (GET/POST)  
   - Suporte a parâmetros de rota (`/usuarios/{id}`)  
   - Suporte a middleware por rota  
2. ⬜ **Criar .htaccess**  
   - Rewrite rules para public/index.php  
   - Compatibilidade com URLs legadas (redirect 301)  
3. ⬜ **Atualizar links**  
   - Substituir `href="usuario_criar.php"` por `route('usuarios.create')`  
   - Helper route() para geração de URLs  

**Critério de Conclusão:** 100% das rotas via MapaRotas, sistema legado desativado

---

#### **FASE 7: REFATORAÇÃO DE HELPERS (1 semana)**
**Objetivo:** Converter funções globais em classes

**Tarefas:**
1. ⬜ **src/Helpers/TextHelper.php**  
   - Migrar uppercase_helper.php  
   - Métodos estáticos: toUppercase(), normalize()  
2. ⬜ **src/Helpers/AuthHelper.php**  
   - Migrar auth_helper.php  
   - Classe não-estática com dependência de SessionManager  
3. ⬜ **Manter funções globais como facades**  
   - `function to_uppercase($str) { return TextHelper::toUppercase($str); }`  
   - Deprecar após migração completa  

**Critério de Conclusão:** Todas helpers como classes, funções globais como wrappers

---

#### **FASE 8: OTIMIZAÇÕES E SEGURANÇA (1 semana)**
**Objetivo:** Adicionar recursos de produção

**Tarefas:**
1. ⬜ **Implementar CSRF Protection**  
   - Token em formulários  
   - Validação em POST requests  
2. ⬜ **Rate Limiting**  
   - Login (5 tentativas/minuto)  
   - Import (1 job por vez por usuário)  
3. ⬜ **Cache Layer**  
   - Cache de queries frequentes (tipos_bens, dependencias)  
   - PSR-6 ou PSR-16 com adapter Redis/Memcached  
4. ⬜ **Logging Estruturado**  
   - Monolog para logs estruturados  
   - Níveis: DEBUG, INFO, WARNING, ERROR  
5. ⬜ **Validação de Upload**  
   - MIME type validation robusto  
   - Scan de vírus (ClamAV) para arquivos Excel  

**Critério de Conclusão:** OWASP Top 10 mitigado, logs centralizados

---

#### **FASE 9: CLEANUP E DOCUMENTAÇÃO (1 semana)**
**Objetivo:** Remover código legado, documentar sistema novo

**Tarefas:**
1. ⬜ **Remover arquivos legados**  
   - Mover para `__legacy_backup__/` (já existe)  
   - Arquivos: index.php (raiz), login.php, app/controllers/, app/views/  
2. ⬜ **Atualizar README.md**  
   - Arquitetura do sistema  
   - Guia de desenvolvimento  
   - Como adicionar novos controllers/services  
3. ⬜ **Gerar documentação API**  
   - PHPDoc em todas classes públicas  
   - Swagger/OpenAPI para endpoints JSON  
4. ⬜ **Code Style**  
   - Configurar PHP-CS-Fixer  
   - PSR-12 compliance  

**Critério de Conclusão:** Zero código legado em produção, documentação completa

---

### 7.3. Cronograma Estimado

| Fase | Duração | Início | Fim |
|------|---------|--------|-----|
| FASE 0: Preparação | 2 semanas | Semana 1 | Semana 2 |
| FASE 1: Infraestrutura | 1 semana | Semana 3 | Semana 3 |
| FASE 2: Repositories | 2 semanas | Semana 4 | Semana 5 |
| FASE 3: Controllers | 3 semanas | Semana 6 | Semana 8 |
| FASE 4: Services | 2 semanas | Semana 9 | Semana 10 |
| FASE 5: Views | 2 semanas | Semana 11 | Semana 12 |
| FASE 6: Roteamento | 1 semana | Semana 13 | Semana 13 |
| FASE 7: Helpers | 1 semana | Semana 14 | Semana 14 |
| FASE 8: Otimizações | 1 semana | Semana 15 | Semana 15 |
| FASE 9: Cleanup | 1 semana | Semana 16 | Semana 16 |

**TOTAL: ~4 meses (16 semanas)**

---

## 8. ESTRUTURA FINAL PROPOSTA

### 8.1. Organização de Diretórios (Pós-Migração)

```
src/
├── Controllers/              # Controllers REST/MVC
│   ├── Api/                  # API endpoints (futuro)
│   ├── AuthController.php    # ✅ Migrado
│   ├── ComumController.php
│   ├── DependenciaController.php
│   ├── PlanilhaController.php
│   ├── ProdutoController.php
│   ├── RelatorioController.php
│   └── UsuarioController.php
├── Core/                     # Classes fundamentais
│   ├── Configuracoes.php     # ✅ Existe
│   ├── ConnectionManager.php # NOVO - Gerencia PDO
│   ├── Container.php         # NOVO - DI Container
│   ├── Database.php          # ✅ Existe (refatorar)
│   ├── Renderizador.php      # ✅ Existe
│   ├── Request.php           # NOVO - HTTP Request
│   ├── Response.php          # NOVO - HTTP Response
│   └── Router.php            # NOVO - Roteamento avançado
├── Helpers/                  # Funções auxiliares
│   ├── AlertHelper.php       # Geração de alertas Bootstrap
│   ├── AuthHelper.php        # Migrar de auth_helper.php
│   ├── FormHelper.php        # Geração de formulários
│   ├── PaginationHelper.php  # Paginação
│   └── TextHelper.php        # Migrar de uppercase_helper.php
├── Middleware/               # Middleware HTTP
│   ├── AuthMiddleware.php    # Autenticação
│   ├── CsrfMiddleware.php    # Proteção CSRF
│   └── RateLimitMiddleware.php  # Rate limiting
├── Repositories/             # Acesso a dados
│   ├── BaseRepository.php    # Repositório abstrato
│   ├── ComumRepository.php   # Migrar comum_helper.php
│   ├── ConfiguracaoRepository.php
│   ├── DependenciaRepository.php
│   ├── ProdutoRepository.php
│   ├── TipoBemRepository.php
│   └── UsuarioRepository.php
├── Routes/                   # Definição de rotas
│   ├── api.php               # Rotas API (futuro)
│   ├── MapaRotas.php         # ✅ Existe (expandir)
│   └── web.php               # Rotas web (migrar MapaRotas)
├── Services/                 # Lógica de negócio
│   ├── AuthService.php       # ✅ Migrado
│   ├── ExcelParserService.php  # Migrar produto_parser_service.php
│   ├── JobManagerService.php  # Jobs assíncronos
│   ├── PlanilhaImportService.php  # Upload + validação
│   ├── ProdutoImportService.php   # Importação em lote
│   ├── Relatorio141Service.php    # Migrar Relatorio141Generator
│   └── ValidationService.php      # Validações reutilizáveis
└── Views/                    # Templates
    ├── auth/
    │   └── login.php         # ✅ Migrado
    ├── comuns/
    │   ├── criar.php
    │   ├── editar.php
    │   └── listar.php
    ├── dependencias/
    ├── layouts/
    │   ├── app.php           # Layout principal
    │   └── auth.php          # Layout login
    ├── partials/
    │   ├── footer.php
    │   ├── header.php
    │   └── menu.php
    ├── planilhas/
    ├── produtos/
    ├── relatorios/
    └── usuarios/
```

### 8.2. Comparação Antes/Depois

| Aspecto | ANTES | DEPOIS |
|---------|-------|--------|
| **Entry Points** | 4 arquivos na raiz | 1 arquivo (public/index.php) |
| **Roteamento** | Arquivos PHP diretos | MapaRotas.php centralizado |
| **Controllers** | app/controllers/ (CRUD folders) | src/Controllers/ (classes) |
| **Views** | app/views/ com `include` controllers | src/Views/ recebendo dados |
| **Database Access** | $conexao global + SQL direto | Repositories com DI |
| **Helpers** | Funções globais | Classes com métodos estáticos |
| **Business Logic** | Misturado em controllers | Services dedicados |
| **Testes** | Nenhum | >70% cobertura (meta) |
| **CSRF** | Não implementado | Middleware em todos POST |
| **Dependency Injection** | Não existe | Container PSR-11 |

---

## 9. MÉTRICAS DE QUALIDADE

### 9.1. Estado Atual (Estimado)

| Métrica | Valor | Avaliação |
|---------|-------|-----------|
| **Linhas de Código** | ~15.000 | ⚠️ GRANDE |
| **Arquivos PHP** | 162 | ⚠️ FRAGMENTADO |
| **Complexidade Ciclomática Média** | ~15-20 | ⚠️ ALTA (meta: <10) |
| **Cobertura de Testes** | 0% | 🔴 CRÍTICO |
| **Duplicação de Código** | ~15% (estimado) | ⚠️ MÉDIA (meta: <5%) |
| **Dívida Técnica** | ~60 dias (estimado) | 🔴 ALTA |
| **Acoplamento (Afferent Coupling)** | ~50 (global $conexao) | 🔴 MUITO ALTO |
| **Coesão (LCOM)** | Baixa (controllers monolíticos) | ⚠️ RUIM |

### 9.2. Metas Pós-Migração

| Métrica | Meta | Estratégia |
|---------|------|------------|
| **Complexidade Ciclomática** | <10 | Extrair métodos, Services |
| **Cobertura de Testes** | >70% | PHPUnit + testes integração |
| **Duplicação de Código** | <3% | DRY via Repositories/Services |
| **Dívida Técnica** | <10 dias | Refatoração contínua |
| **PSR Compliance** | 100% | PHP-CS-Fixer |
| **OWASP Top 10** | 0 vulnerabilidades conhecidas | CSRF, Rate Limit, Validação |

---

## 10. PRÓXIMOS PASSOS IMEDIATOS

### **Aguardando Aprovação do Usuário:**

1. ✅ **Análise arquitetural concluída** (este documento)  
2. ⏸️ **Aguardar feedback sobre:**  
   - Cronograma de 4 meses é viável?  
   - Priorizar alguma fase específica?  
   - Algum módulo NÃO deve ser refatorado?  
   - Orçamento para ferramentas adicionais (CI/CD, monitoring)?  
3. ⏸️ **Decisões críticas:**  
   - Durante migração, aceitar code freeze em novos features?  
   - Manter sistema legado funcionando em paralelo?  
   - Criar branch dedicado ou work-in-progress em main?  

### **Após Aprovação:**

**SEMANA 1-2:**
1. Configurar PHPUnit  
2. Criar testes de integração para:  
   - Login/logout  
   - CRUD de usuários  
   - Importação de planilha simples  
3. Configurar GitHub Actions (CI)  
4. Criar branch `refactor/architecture-migration`  

**SEMANA 3:**
1. Implementar ConnectionManager  
2. Criar BaseRepository  
3. Criar Request/Response classes  
4. Testes unitários para Core  

---

## 11. GLOSSÁRIO TÉCNICO

| Termo | Definição |
|-------|-----------|
| **Acoplamento (Coupling)** | Grau de dependência entre módulos. Alto acoplamento ($conexao global) dificulta manutenção. |
| **Coesão (Cohesion)** | Grau em que responsabilidades de um módulo são relacionadas. Controllers monolíticos têm baixa coesão. |
| **CSRF** | Cross-Site Request Forgery - ataque que força usuário autenticado a executar ações não intencionadas. |
| **DI (Dependency Injection)** | Padrão onde dependências são fornecidas via construtor/setter ao invés de hardcoded. |
| **LCOM** | Lack of Cohesion in Methods - métrica que mede coesão de uma classe. |
| **N+1 Query** | Anti-padrão onde query é executada N vezes em loop ao invés de 1 query com JOIN. |
| **PSR** | PHP Standard Recommendation - padrões da PHP-FIG (PSR-4 autoloading, PSR-12 code style). |
| **Repository Pattern** | Padrão que encapsula acesso a dados, fornecendo interface de coleção. |
| **SRP** | Single Responsibility Principle - classe deve ter apenas uma razão para mudar. |

---

## 12. CONCLUSÃO

### 12.1. Resumo Executivo

O sistema **Check Planilha Imobilizado CCB** é um software funcional com **dívida técnica significativa** acumulada. A arquitetura atual apresenta:

**✅ Pontos Fortes:**
- Funcionalidades completas e em produção
- Segurança básica implementada (prepared statements, password hashing)
- Estrutura inicial de separação de concerns (controllers/views)
- Infraestrutura Docker bem configurada

**⚠️ Problemas Críticos:**
- ⚠️ Variável global $conexao em 30+ arquivos
- ⚠️ Views incluindo controllers (inversão MVC)
- ⚠️ SQL direto em views (5+ arquivos)
- ⚠️ Controller monolítico de 1480 linhas
- 🔴 Zero testes automatizados
- 🔴 Sem proteção CSRF

**📊 Esforço de Migração:**
- **Duração:** 16 semanas (~4 meses)
- **Risco:** MÉDIO-ALTO (sem testes, alto acoplamento)
- **Benefícios:** Manutenibilidade, testabilidade, segurança, escalabilidade

### 12.2. Recomendação

**APROVAR MIGRAÇÃO GRADUAL** seguindo o plano de 9 fases proposto, com **PRIORIDADE MÁXIMA** para:
1. Criar testes de integração (Fase 0)
2. Implementar Repositories (Fase 2)
3. Migrar ImportacaoPlanilhaController (Fase 4)

**NÃO recomendado:**
- Reescrita completa (Big Bang) - risco muito alto
- Manter arquitetura atual - dívida técnica continuará crescendo

---

**Documento Gerado Por:** GitHub Copilot  
**Revisão:** Pendente (aguardando feedback do desenvolvedor)  
**Versão:** 1.0  
**Última Atualização:** 11/02/2025

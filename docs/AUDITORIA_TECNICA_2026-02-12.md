# 🔍 RELATÓRIO DE AUDITORIA TÉCNICA COMPLETA
**Sistema Check Planilha Imobilizado CCB**  
**Data da Auditoria:** 12 de fevereiro de 2026  
**Auditor:** GitHub Copilot (Claude Sonnet 4.5)  
**Escopo:** Análise completa de rotas, controllers, views, fluxos e conformidade arquitetural

---

## ETAPA 1 — VALIDAÇÃO CONTRA DOCUMENTAÇÃO

### 📊 Resumo Executivo

**Status da Migração Planejada vs Realizada:**

| Componente | Planejado | Implementado | Status | Gap |
|------------|-----------|--------------|--------|-----|
| **Rotas Centralizadas** | ✅ MapaRotas.php | ✅ MapaRotas.php | 🟢 CONFORME | - |
| **AuthController** | ✅ Completo | ✅ Completo | 🟢 CONFORME | - |
| **ComumController** | ✅ Completo c/ DI | ⚠️ Parcial | 🟡 PARCIAL | Views legadas sem migrar |
| **UsuarioController** | ✅ Completo c/ DI | ⚠️ Parcial | 🟡 PARCIAL | Renderiza formulários legados |
| **DependenciaController** | ✅ Completo c/ DI | ❌ Stub vazio | 🔴 NÃO CONFORME | Sem Service/Repository |
| **ProdutoController** | ✅ Completo c/ DI | ❌ Stub vazio | 🔴 NÃO CONFORME | Sem Service/Repository |
| **PlanilhaController** | ✅ Completo c/ DI | ❌ Stub vazio | 🔴 NÃO CONFORME | Sem Service/Repository |
| **RelatorioController** | ✅ Completo c/ DI | ❌ Stub vazio | 🔴 NÃO CONFORME | Sem Service/Repository |
| **Views Modernas** | ✅ Em src/Views/ | ⚠️ Mistas | 🟡 PARCIAL | Views legadas coexistem |
| **SQL em Views** | ❌ Proibido | ❌ Existe em 5+ arquivos | 🔴 VIOLAÇÃO | produto_check_view.php, etc. |
| **Autenticação Middleware** | ✅ AuthMiddleware | ⚠️ SKIP_AUTH Global | 🔴 CRÍTICO | Autenticação desabilitada! |

---

### 🚨 Inconsistências Críticas Identificadas

#### 1️⃣ **Autenticação Globalmente Desabilitada**
**Localização:** `public/index.php` linha 3
```php
define('SKIP_AUTH', true); // ⚠️ CRÍTICO!
```

**Documentação:** PLANO_MIGRACAO.md define autenticação via AuthMiddleware  
**Realidade:** Front controller desabilita completamente autenticação  
**Impacto:** **CRÍTICO** - Sistema 100% desprotegido

---

#### 2️⃣ **Controllers sem Implementação Real (4 de 8)**
**Documentação:** FASE3_MIGRATION_LOG.md indica migração completa  
**Realidade:** 
- `src/Controllers/DependenciaController.php` - apenas stubs
- `src/Controllers/ProdutoController.php` - apenas stubs  
- `src/Controllers/PlanilhaController.php` - apenas stubs
- `src/Controllers/RelatorioController.php` - apenas stubs

**Impacto:** **ALTO** - Rotas definidas mas não funcionais

---

#### 3️⃣ **SQL Direto em Views (Violação SOLID)**
**Documentação:** ARQUITETURA_SOLID.md proíbe SQL em views  
**Realidade:** 5+ views executam queries diretas

**Arquivos violadores:**
- `src/Views/planilhas/produto_check_view.php` linhas 35-61 - 2 queries (SELECT + UPDATE)
- `src/Views/planilhas/produto_copiar_etiquetas.php` linha 16 - múltiplas queries
- `src/Views/usuarios/usuario_ver.php` linha 12 - SELECT direto

**Impacto:** **ALTO** - Viola separação de responsabilidades

---

#### 4️⃣ **Dualidade de Renderização**
**Documentação:** Views devem usar ViewRenderer::render()  
**Realidade:** Controllers fazem require direto de views legadas

**Exemplo:** `src/Controllers/UsuarioController.php` linhas 109-111
```php
private function renderizarFormularioLegado(array $dados): void
{
    extract($dados);
    require __DIR__ . '/../../index.php'; // ⚠️ Violação MVC
}
```

**Impacto:** **MÉDIO** - Controllers acoplados a views legadas

---

#### 5️⃣ **Variável Global `$conexao` ainda em uso**
**Documentação:** PLANO_MIGRACAO.md planeja eliminar `$conexao` global  
**Realidade:** 
- `public/index.php` linhas 26-30 injeta `$conexao` global em controllers
- 5+ views dependem de `$conexao` global

**Impacto:** **MÉDIO** - Impede injeção de dependências pura

---

## ETAPA 2 — AUDITORIA FUNCIONAL COMPLETA

### 🔄 Análise Rota por Rota

#### **GRUPO 1: Autenticação** ✅ **FUNCIONAL**

| Rota | Método | Controller::Ação | Status | Validações | Tratamento Erro |
|------|--------|------------------|--------|------------|-----------------|
| `/` | GET | AuthController::login | ✅ | ❌ Nenhuma | ⚠️ Mínimo |
| `/login` | GET | AuthController::login | ✅ | ❌ Nenhuma | ⚠️ Mínimo |
| `/login` | POST | AuthController::authenticate | ✅ | ✅ Email/senha | ✅ Try/catch |
| `/logout` | GET | AuthController::logout | ✅ | ❌ Nenhuma | ✅ Simples |

**Simulação de Execução:**
```
USER → GET /login 
  → MapaRotas identifica AuthController::login
  → Instancia AuthController (sem DI)
  → Renderiza login.php via require direto ⚠️
  → Retorna HTML
```

**Problemas Identificados:**
- ❌ AuthController::login() usa `require` direto (linha 37)
- ❌ Não valida se usuário já está logado (permite re-login)
- ⚠️ Sem proteção CSRF em formulário

---

#### **GRUPO 2: Comuns** 🟡 **PARCIALMENTE FUNCIONAL**

| Rota | Método | Controller::Ação | Status | Validações | Service | Repository |
|------|--------|------------------|--------|------------|---------|------------|
| `/comuns` | GET | ComumController::index | ✅ | ✅ Sanitiza busca | ✅ ComumService | ✅ ComumRepository |
| `/comuns/editar` | GET | ComumController::edit | ⚠️ | ⚠️ ID > 0 | ❌ Não usa | ❌ Não usa |
| `/comuns/editar` | POST | ComumController::update | ❌ | ❌ Nenhuma | ❌ Não usa | ❌ Não usa |

**Simulação de Falha:**
```
USER → POST /comuns/editar?id=999
  → MapaRotas identifica ComumController::update
  → Controller verifica REQUEST_METHOD === POST ✅
  → Redireciona para /comuns?success=1 SEM FAZER NADA ❌
  → DADOS NÃO SÃO SALVOS! ❌
```

**Problemas Identificados:**
- ❌ `edit()` apenas renderiza view sem buscar dados (linha 215)
- ❌ `update()` apenas redireciona sem salvar (linha 222)
- ⚠️ `index()` gera HTML dentro do controller (linha 87)

---

#### **GRUPO 3: Usuários** 🟡 **PARCIALMENTE FUNCIONAL**

| Rota | Método | Controller::Ação | Status | Service | Repository | Problemas |
|------|--------|------------------|--------|---------|------------|-----------|
| `/usuarios` | GET | UsuarioController::index | ✅ | ✅ UsuarioService | ✅ UsuarioRepository | Nenhum |
| `/usuarios/criar` | GET | UsuarioController::create | ✅ | ❌ Não usa | ❌ Não usa | Renderiza view legacy |
| `/usuarios/criar` | POST | UsuarioController::store | ✅ | ✅ UsuarioService | ✅ UsuarioRepository | Renderiza formulário em erro |
| `/usuarios/editar` | GET | UsuarioController::edit | ⚠️ | ✅ UsuarioService | ✅ UsuarioRepository | Renderiza formulário legado |
| `/usuarios/editar` | POST | UsuarioController::update | ✅ | ✅ UsuarioService | ✅ UsuarioRepository | Renderiza formulário em erro |
| `/usuarios/deletar` | POST | UsuarioController::delete | ✅ | ✅ UsuarioService | ✅ UsuarioRepository | Nenhum |

**Problemas Identificados:**
- ⚠️ `renderizarFormularioLegado()` usa `require __DIR__/../../index.php` (linha 242)
- ❌ Validação de CPF não verifica algoritmo (aceita "00000000000")
- ❌ Sem proteção CSRF em formulários

---

#### **GRUPO 4: Dependências** ❌ **NÃO FUNCIONAL**

**Simulação de Execução:**
```
USER → POST /dependencias/criar (descricao=SALA 1)
  → DependenciaController::store()
  → Verifica REQUEST_METHOD === POST ✅
  → Redireciona para /dependencias?success=1 ❌
  → NENHUM DADO É SALVO! ❌
  → Usuário vê mensagem de sucesso FALSA! ❌❌❌
```

**Impacto:** **CRÍTICO** - Controller finge que funciona mas não faz nada!

---

#### **GRUPO 5: Produtos** ❌ **NÃO FUNCIONAL**

**Situação idêntica a Dependências** - todos métodos são stubs que redirecionam com `?success=1` SEM EXECUTAR NADA

**Impacto:** **CRÍTICO** - Funcionalidades core do sistema não funcionam!

---

#### **GRUPO 6: Planilhas** ❌ **NÃO FUNCIONAL**

**Simulação de Falha Crítica:**
```
USER → POST /planilhas/importar (arquivo.xlsx com 1000 produtos)
  → PlanilhaController::processarImportacao()
  → Verifica REQUEST_METHOD === POST ✅
  → Redireciona para /planilhas/importar?success=1 ❌
  → ARQUIVO NÃO É PROCESSADO! ❌
  → 1000 produtos NÃO SÃO IMPORTADOS! ❌
```

**Impacto:** **CRÍTICO** - Funcionalidade principal do sistema quebrada!

---

## ETAPA 3 — DETECÇÃO DE PROBLEMAS

### 🔥 PROBLEMA #1: Autenticação Desabilitada Globalmente
**Severidade:** ⛔ **CRÍTICA**

**Descrição Técnica:**  
`public/index.php` linha 3 define `SKIP_AUTH = true`, fazendo `src/Middleware/AuthMiddleware.php` linhas 34-36 abortar verificação.

**Impacto Real:**
- Sistema 100% acessível sem login
- Dados sensíveis expostos publicamente
- LGPD violada (dados pessoais desprotegidos)

**Trecho Exato:**
```php
// public/index.php (linha 3)
define('SKIP_AUTH', true); // ⚠️ REMOVE TODA PROTEÇÃO!
```

---

### 🔥 PROBLEMA #2: Controllers Farsantes (Fake Success)
**Severidade:** ⛔ **CRÍTICA**

**Descrição Técnica:**  
4 controllers implementados como stubs que:
1. Aceitam requisições
2. Redirecionam com `?success=1`
3. NÃO executam operação alguma

**Impacto Real:**
- Usuários perdem dados (acham que salvaram, mas dados são descartados)
- Importações de planilhas falham silenciosamente
- **Perda de confiança no sistema**

**Trecho Exato:**
```php
// src/Controllers/PlanilhaController.php (linhas 23-29)
public function processarImportacao(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirecionar('/planilhas/importar');
        return;
    }
    
    $this->redirecionar('/planilhas/importar?success=1'); // ⚠️ MENTIRA!
}
```

---

### 🔥 PROBLEMA #3: SQL Direto em Views
**Severidade:** 🔴 **ALTA**

**Descrição Técnica:**  
`src/Views/planilhas/produto_check_view.php` linhas 35-61 executa 2 queries SQL diretamente:
1. SELECT para buscar status
2. UPDATE para modificar checado

**Impacto Real:**
- Lógica de negócio espalhada fora de Services
- Views impossíveis de testar unitariamente
- Violação SOLID (Single Responsibility)

**Outros Arquivos Violadores:**
- `produto_copiar_etiquetas.php` - 4+ queries
- `usuario_ver.php` - 1 query
- `comuns_listar.php` - 1 query

---

### 🔥 PROBLEMA #4: Variável Global `$conexao`
**Severidade:** 🟠 **MÉDIA**

**Impacto Real:**
- Controllers não são testáveis unitariamente
- Impossível mockar conexão para testes
- Viola Dependency Inversion Principle

---

### 🔥 PROBLEMA #5: Renderização Dupla
**Severidade:** 🟠 **MÉDIA**

**Descrição Técnica:**  
Controllers fazem `require` direto de views legadas ao invés de usar ViewRenderer.

---

### 🔥 PROBLEMA #6: Ausência de Proteção CSRF
**Severidade:** 🟠 **MÉDIA**

**Impacto Real:**
- Sistema vulnerável a Cross-Site Request Forgery
- Atacante pode executar ações em nome de usuário autenticado

---

### 🔥 PROBLEMA #7: Validação Fraca de Dados
**Severidade:** 🟡 **MÉDIA**

**Descrição Técnica:**  
Validação de CPF apenas verifica 11 dígitos, não algoritmo validador.

**Impacto Real:**
- CPFs inválidos aceitos (00000000000, 11111111111)

---

### 🔥 PROBLEMA #8: Tratamento de Erros Inconsistente
**Severidade:** 🟡 **MÉDIA**

**Impacto Real:**
- Erros não capturados causam tela branca (500)
- Stack traces expostos em produção

---

### 🔥 PROBLEMA #9: Views Legadas Sem Encoding Fix
**Severidade:** 🟡 **MÉDIA**

**Impacto Real:**
- Dados exibidos incorretamente (Áƒ, ï¿½)

---

### 🔥 PROBLEMA #10: Paginação Gerada no Controller (HTML)
**Severidade:** 🟡 **BAIXA**

**Impacto Real:**
- Viola separação de concerns
- HTML acoplado ao controller

---

## ETAPA 4 — 5 FORMAS DE RESOLVER CADA PROBLEMA

### 🔧 SOLUÇÕES PARA PROBLEMA #1: Autenticação Desabilitada

#### **SOLUÇÃO 1.1: Hotfix Mínimo** ⭐ Trivial | 🟢 Risco Nenhum
Remover `define('SKIP_AUTH', true)` de `public/index.php`.

**Vantagens:**
- Implementação: 10 segundos
- Resolve problema imediatamente

**Desvantagens:**
- Não adiciona proteção CSRF
- Solução superficial

---

#### **SOLUÇÃO 1.2: Ajuste Controlado** ⭐⭐ Simples | 🟡 Risco Baixo
Manter `SKIP_AUTH` mas adicionar whitelist de rotas públicas.

---

#### **SOLUÇÃO 1.3: Variável de Ambiente** ⭐⭐ Simples | 🟡 Risco Médio
Mover controle para `.env`.

---

#### **SOLUÇÃO 1.4: Middleware Pipeline** ⭐⭐⭐⭐ Alta | 🟡 Risco Médio
Implementar pipeline de middlewares.

---

#### **SOLUÇÃO 1.5: Solução Ideal - Segurança em Camadas** ⭐⭐⭐⭐⭐ Muito Alta | 🟠 Risco Médio-Alto
Combinar:
1. Remover `SKIP_AUTH`
2. Middleware pipeline
3. CSRF protection
4. Rate limiting
5. Audit logging

---

### 🔧 SOLUÇÕES PARA PROBLEMA #2: Controllers Farsantes

#### **SOLUÇÃO 2.1: Hotfix - Retornar Erro 501** ⭐ Trivial | 🟢 Nenhum
Fazer controllers retornarem "Not Implemented".

**Implementação:**
```php
public function store(): void
{
    http_response_code(501);
    echo "Funcionalidade em implementação";
    exit;
}
```

---

#### **SOLUÇÃO 2.2: Implementação Mínima** ⭐⭐ Simples | 🟡 Baixo
SQL direto no controller (sem Service/Repository).

---

#### **SOLUÇÃO 2.3: Service Layer Simples** ⭐⭐⭐ Média | 🟡 Baixo
Criar Services sem Repository.

---

#### **SOLUÇÃO 2.4: Service + Repository Completo** ⭐⭐⭐⭐ Média-Alta | 🟡 Médio
Seguir arquitetura planejada (como UsuarioController).

---

#### **SOLUÇÃO 2.5: Geração Automatizada** ⭐⭐⭐⭐⭐ Muito Alta | 🟠 Médio
Criar generator CLI para scaffolding.

---

### 🔧 SOLUÇÕES PARA PROBLEMA #3: SQL Direto em Views

#### **SOLUÇÃO 3.1: Extrair para Functions** ⭐⭐ Simples | 🟢 Baixo
Mover queries para funções globais.

---

#### **SOLUÇÃO 3.2: Criar Repository** ⭐⭐⭐ Média | 🟡 Baixo
View usa Repository diretamente.

---

#### **SOLUÇÃO 3.3: Converter em Endpoint** ⭐⭐⭐⭐ Alta | 🟡 Médio
Transformar view em route + controller.

---

#### **SOLUÇÃO 3.4: Pré-carregar Dados** ⭐⭐⭐ Média | 🟡 Baixo
Controller busca todos dados antes de renderizar.

---

#### **SOLUÇÃO 3.5: ViewModel Pattern** ⭐⭐⭐⭐⭐ Muito Alta | 🟠 Médio
Criar ViewModels que encapsulam dados + lógica.

---

## ETAPA 5 — TESTE DE ROBUSTEZ

### 🧪 Cenários de Teste e Falhas Detectadas

#### **CENÁRIO 1: Acesso Direto por URL**
```
curl http://localhost/usuarios/editar?id=999
```
**Falhas:**
1. Usuário não autenticado acessa dados sensíveis
2. Sem log de tentativa

---

#### **CENÁRIO 2: Parâmetro Inválido (XSS)**
```
POST /dependencias/criar
descricao=<script>alert('XSS')</script>
```
**Falhas:**
1. Ausência total de validação
2. Possível XSS stored

---

#### **CENÁRIO 3: Parâmetro Ausente**
```
POST /usuarios/criar (sem campos)
```
**Status:** ✅ Validação funciona
**Falhas:** Erros não logados

---

#### **CENÁRIO 4: Usuário Não Autorizado**
**Falhas:**
1. Ausência total de autorização
2. Sem controle de permissões

---

#### **CENÁRIO 5: Dados Inesperados**
```
POST /usuarios/criar
cpf=00000000000
```
**Falhas:**
1. Validação aceita CPFs inválidos

---

#### **CENÁRIO 6: Falha de Dependência Externa**
**Status:** ⚠️ Parcial
**Falhas:**
1. Código HTTP incorreto (200 ao invés de 500)
2. UX ruim (página branca)

---

#### **CENÁRIO 7: Ambiente Produção vs Dev**
**Falhas:**
1. Sem verificação de APP_ENV
2. Possível vazamento de stack traces

---

#### **CENÁRIO 8: SQL Injection**
**Status:** ✅ Protegido (prepared statements)

---

## ETAPA 6 — CONCLUSÃO E PRIORIDADE

### 📋 Lista Priorizada de Problemas

| # | Problema | Severidade | Impacto | Facilidade | Prioridade |
|---|----------|------------|---------|------------|------------|
| 1 | **Autenticação Desabilitada** | ⛔ CRÍTICA | Sistema desprotegido | ⭐ Trivial | 🔥🔥🔥🔥🔥 **P0** |
| 2 | **Controllers Farsantes** | ⛔ CRÍTICA | Perda de dados | ⭐⭐⭐⭐ Alta | 🔥🔥🔥🔥 **P0** |
| 3 | **SQL em Views** | 🔴 ALTA | Violação SOLID | ⭐⭐⭐ Média | 🔥🔥🔥 **P1** |
| 4 | **Ausência de CSRF** | 🟠 MÉDIA | Vulnerabilidade | ⭐⭐ Simples | 🔥🔥🔥 **P1** |
| 5 | **Variável Global `$conexao`** | 🟠 MÉDIA | Testabilidade | ⭐⭐⭐ Média | 🔥🔥 **P2** |
| 6 | **Renderização Dupla** | 🟠 MÉDIA | Acoplamento | ⭐⭐⭐ Média | 🔥🔥 **P2** |
| 7 | **Validação Fraca** | 🟡 MÉDIA | Dados inválidos | ⭐⭐ Simples | 🔥 **P3** |
| 8 | **Tratamento Erro** | 🟡 MÉDIA | UX ruim | ⭐⭐ Simples | 🔥 **P3** |
| 9 | **Encoding UTF-8** | 🟡 MÉDIA | UX ruim | ⭐⭐⭐ Média | 🔥 **P3** |
| 10 | **HTML em Controller** | 🟡 BAIXA | Acoplamento | ⭐⭐ Simples | **P4** |

---

### 🏥 Avaliação Geral da Saúde do Sistema

**Score: 45/100** 🟡

| Categoria | Score | Status | Comentário |
|-----------|-------|--------|------------|
| **Segurança** | 20/100 | ⛔ CRÍTICO | Autenticação desabilitada, sem CSRF |
| **Funcionalidade** | 40/100 | 🔴 RUIM | 50% dos controllers não funcionam |
| **Arquitetura** | 60/100 | 🟡 MEDIANO | Parcialmente SOLID |
| **Testabilidade** | 30/100 | 🔴 RUIM | Globals impedem testes |
| **Manutenibilidade** | 55/100 | 🟡 MEDIANO | Código misto |
| **Performance** | 70/100 | 🟢 BOM | Queries otimizadas |
| **UX** | 50/100 | 🟡 MEDIANO | Parcialmente funcional |

**Diagnóstico:**
Sistema em **estado transitório crítico** - migração arquitetural **50% incompleta**.

---

### 🎯 Recomendações Estratégicas

#### **1. Hotfixes Urgentes (Hoje)**
1. Remover `SKIP_AUTH` de `public/index.php`
2. Adicionar erro 501 em controllers stub
3. Deploy imediato

#### **2. Sprint de Emergência (1 semana)**
**Objetivo:** Restaurar funcionalidades core

- Implementar DependenciaController completo
- Implementar ProdutoController completo
- Implementar PlanilhaController::processarImportacao()

**Entregáveis:**
- ✅ Sistema 100% funcional
- ✅ Sem mensagens enganosas

#### **3. Sprint de Segurança (2 semanas)**
- Implementar CsrfMiddleware
- Validação CPF/CNPJ robusta
- Rate limiting

#### **4. Sprint de Refatoração (3 semanas)**
- Mover SQL de views para repositories
- Eliminar variável global `$conexao`

---

### ⚠️ Riscos se Nada For Feito

#### **Curtíssimo Prazo (1 semana)**
- ❌ Vazamento de dados (LGPD)
- ❌ Usuários perdem dados
- ❌ Planilhas não importam

#### **Curto Prazo (1 mês)**
- ❌ Reputação arruinada
- ❌ Dados inconsistentes

#### **Médio Prazo (3 meses)**
- ❌ Custos de manutenção explodem

#### **Longo Prazo (6+ meses)**
- ❌ Reescrita total necessária
- ❌ Custo 10x maior

---

### 🚀 Próximos Passos Recomendados

#### **HOJE (Prioridade Máxima)**
1. ✅ Remover `define('SKIP_AUTH', true)`
2. ✅ Adicionar `http_response_code(501)` em stubs
3. ✅ Deploy
4. ✅ Monitorar logs

#### **ESTA SEMANA**
1. ✅ Implementar DependenciaService + Repository
2. ✅ Implementar DependenciaController CRUD
3. ✅ Replicar para ProdutoController

#### **ROADMAP 30 DIAS**
```
Semana 1: Hotfixes + DependenciaController
Semana 2: ProdutoController + PlanilhaController
Semana 3: Segurança (CSRF + Validações)
Semana 4: Refatoração (SQL em Views)
```

---

## 📎 ANEXOS

### ANEXO A: Mapa Completo de Rotas vs Implementação

| Rota | Controller | Status | SQL em View | Auth | CSRF |
|------|------------|--------|-------------|------|------|
| `GET /` | AuthController::login | ✅ | ❌ | ❌ | ❌ |
| `GET /login` | AuthController::login | ✅ | ❌ | ❌ | ❌ |
| `POST /login` | AuthController::authenticate | ✅ | ❌ | ❌ | ❌ |
| `GET /logout` | AuthController::logout | ✅ | ❌ | ❌ | ✅ |
| `GET /comuns` | ComumController::index | ✅ | ❌ | ❌ | ✅ |
| `GET /comuns/editar` | ComumController::edit | ⚠️ | ❌ | ❌ | ✅ |
| `POST /comuns/editar` | ComumController::update | ❌ | ❌ | ❌ | ❌ |
| `GET /usuarios` | UsuarioController::index | ✅ | ❌ | ❌ | ✅ |
| `POST /usuarios/criar` | UsuarioController::store | ✅ | ❌ | ❌ | ❌ |
| `POST /usuarios/editar` | UsuarioController::update | ✅ | ❌ | ❌ | ❌ |
| `POST /usuarios/deletar` | UsuarioController::delete | ✅ | ❌ | ❌ | ❌ |
| `GET /dependencias` | DependenciaController::index | ⚠️ | ✅ | ❌ | ✅ |
| `POST /dependencias/criar` | DependenciaController::store | ❌ | ❌ | ❌ | ❌ |
| `POST /produtos/check` | ProdutoController::check | ❌ | ✅ | ❌ | ❌ |
| `POST /planilhas/importar` | PlanilhaController::processarImportacao | ❌ | ❌ | ❌ | ❌ |

---

### ANEXO B: Arquivos com SQL Direto

| Arquivo | Linhas | Queries | Severidade |
|---------|--------|---------|------------|
| `produto_check_view.php` | 35-61 | 2 | 🔴 ALTA |
| `produto_copiar_etiquetas.php` | 16-80 | 4+ | 🔴 ALTA |
| `usuario_ver.php` | 12 | 1 | 🟡 MÉDIA |
| `comuns_listar.php` | 98 | 1 | 🟡 MÉDIA |

---

### ANEXO C: Estimativas de Esforço

| Tarefa | Horas | Devs | Prazo |
|--------|-------|------|-------|
| Remover SKIP_AUTH | 0.5 | 1 | Hoje |
| DependenciaController | 8 | 1 | 1 dia |
| ProdutoController | 16 | 1 | 2 dias |
| PlanilhaController | 24 | 2 | 3 dias |
| CsrfMiddleware | 8 | 1 | 1 dia |
| Tokens em formulários | 4 | 1 | 4h |
| Validação CPF/CNPJ | 2 | 1 | 2h |
| Mover SQL de views | 20 | 1 | 3 dias |
| Testes unitários | 40 | 2 | 5 dias |
| **TOTAL** | **122.5h** | - | **~3 semanas** |

---

## 🎯 CONCLUSÃO FINAL

O sistema está em **estado crítico transitório** resultante de migração arquitetural **50% completa**. 

**Ação Imediata Requerida:**
- **Hoje:** Habilitar autenticação
- **Esta semana:** Implementar controllers stub
- **Este mês:** Eliminar SQL de views + CSRF

**Prognóstico:**
- Com ações: ✅ Estabilizado em 3 semanas
- Sem ações: ❌ Colapso em 1-2 meses + reescrita (custo 10x)

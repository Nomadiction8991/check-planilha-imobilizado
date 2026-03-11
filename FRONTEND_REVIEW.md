# Análise Front-End — check-planilha-imobilizado-ccb

**Data:** 10/03/2026 | **Revisor:** Claude Code (Clean Code Mentor)
**Última atualização:** 11/03/2026

---

## Visão Geral

| Tecnologia | Versão |
|---|---|
| Framework CSS | Bootstrap 5.3 (via CDN) |
| Ícones | Bootstrap Icons 1.11 (via CDN) |
| JavaScript | Vanilla JS (ES6+) |
| Máscaras | Inputmask 5.0.8 (via CDN com SRI) |
| PWA | Service Worker + Web Manifest |
| Bundler | Nenhum — CSS/JS servidos sem minificação |
| Layouts | `app.php` (layout único consolidado) |

---

## Tarefa 1: Script inline dentro de views — violação de separação de responsabilidades

**Severidade:** Alta
**Status:** ✅ Parcialmente resolvido
**Arquivos:**
- `src/Views/products/create.php` (linhas 122–152)
- `src/Views/products/edit.php` (linhas 198–224)
- `src/Views/spreadsheets/import-errors.php` (linhas 250–329)
- `src/Views/departments/create.php` (linhas 46–55)
- `src/Views/asset-types/create.php` (linhas 65–98)
- `src/Views/churches/edit.php` (linhas 131–142)

**Problema:** Blocos `<script>` e `<style>` inline embutidos dentro de views PHP. Em `products/create.php` existe um IIFE de 30 linhas; em `departments/create.php` a validação é feita com `alert()` inline; em `asset-types/create.php` existe um `<style>` inteiro no meio do HTML. Em `import-errors.php` há um AJAX handler completo (80 linhas) embutido na view.

**Solução:** Extrair todo JavaScript para os arquivos `.js` em `public/assets/js/` e os estilos para `.css`. Dados de inicialização via `data-*` attributes ou `window._config`.

---

## Tarefa 2: `<link>` e `<script>` embutidos dentro do `<main>` (fora do `<head>`)

**Severidade:** Alta
**Status:** ✅ Resolvido — Mecanismo `$headScripts` adicionado ao layout `app.php`. Inputmask CDN movido para `<head>` com SRI em `users/edit.php`, `users/create.php` e `churches/edit.php`. Scripts de produto usam `$customCssPath` e `defer`.
**Arquivos:**
- `src/Views/products/edit.php` (linha 30)
- `src/Views/products/sign.php` (linha 46)
- `src/Views/users/edit.php` (linhas 35, 46–49)
- `src/Views/spreadsheets/import-preview.php` (linha 25)
- `src/Views/spreadsheets/import-progress.php` (linha 106)

**Problema:** Tags `<link rel="stylesheet">` dentro do `<main>` causam **FOUC** (Flash of Unstyled Content). O layout já oferece `$customCssPath` para injetar CSS no `<head>`, mas estas views ignoram o mecanismo.

**Solução:** Usar `$customCssPath` no topo da view:
```php
$customCssPath = '/assets/css/produtos/produto_editar.css';
```

---

## Tarefa 3: Ausência de token CSRF em formulários DELETE e em `bulk-delete.php`

**Severidade:** Alta (Segurança)
**Status:** ✅ Resolvido — `bulk-delete.php` retorna HTTP 410. Lógica movida para `ProdutoController::bulkDelete()` com validação CSRF no servidor.
**Arquivos:**
- `src/Views/products/bulk-delete.php` (linha 34: executa `DELETE FROM produtos` diretamente)
- `src/Views/products/delete.php` (linha 27)

**Problema:** `bulk-delete.php` é chamado via AJAX e contém **lógica de negócio** (DELETE SQL direto) dentro de um arquivo em `src/Views/`. O CSRF depende do `MutationObserver` do `csrf-global.js`, que pode falhar se o modal for renderizado antes do `DOMContentLoaded`.

**Solução:**
1. Mover o endpoint para `ProdutoController::bulkDelete()`
2. Validar CSRF no servidor para essa rota
3. Embutir o token no formulário na renderização:
```html
<input type="hidden" name="_csrf_token" value="<?= \App\Core\CsrfService::getToken() ?>">
```

---

## Tarefa 4: SQL direto em arquivo dentro de `src/Views/`

**Severidade:** Alta (Arquitetura)
**Status:** ✅ Resolvido — SQL removido de `sign.php`. Dados vêm de `ProdutoController::signView()`.
**Arquivos:**
- `src/Views/products/sign.php` (linhas 21–38: query SQL com `$conexao->prepare()`)

**Problema:** A view executa SQL diretamente usando `$conexao` injetada no escopo. Viola o Dependency Rule da Clean Architecture.

**Solução:** Mover a query para o Controller/Service e passar `$produtos` já populado para a view.

---

## Tarefa 5: Lógica de negócio PHP misturada com HTML nas views de relatório

**Severidade:** Alta (Arquitetura)
**Status:** ✅ Resolvido — `ReportFillerService.php` criado para `view.php`. `Report141FillerService.php` criado para `report-141.php` (redução de ~580 para 144 linhas na view).
**Arquivos:**
- `src/Views/reports/report-141.php` (linhas 59–315: 257 linhas de funções PHP helpers)
- `src/Views/reports/view.php` (linhas 65–166: funções `preencherCampo`, `preencherCheckbox`, etc.)

**Problema:** Views de relatório definem dezenas de funções PHP que manipulam HTML via DOMDocument e regex. Duplicação grave: `report-141.php` e `view.php` possuem implementações paralelas das mesmas operações.

**Solução:** Extrair para `src/Services/Report/Report141FillerService.php`. A view recebe apenas o HTML já preenchido:
```php
// Controller
$htmlPronto = $this->report141FillerService->fill($a4Block, $produto, $planilhaData);
// View
echo $htmlPronto;
```

---

## Tarefa 6: Output não escapado em views (XSS)

**Severidade:** Alta (Segurança)
**Status:** ✅ Resolvido — `ViewHelper::e()` padronizado nas views.
**Arquivos:**
- `src/Views/products/edit.php` (linha 54: `<?php echo $mensagem; ?>`)
- `src/Views/departments/create.php` (linha 16: `<?php echo $mensagem; ?>`)
- `src/Views/reports/view.php` (linha 198: `echo $htmlPreenchido`)

**Problema:** Variável `$mensagem` ecoada sem `htmlspecialchars`. Padrão inconsistente no projeto.

**Solução:** Padronizar com `ViewHelper::e()`:
```php
<?= \App\Helpers\ViewHelper::e($mensagem) ?>
```

---

## Tarefa 7: Dois `addEventListener('DOMContentLoaded')` no mesmo arquivo JS

**Severidade:** Média
**Status:** ✅ Resolvido — DOMContentLoaded unificado em `products/index.js`.
**Arquivos:**
- `public/assets/js/products/index.js` (linhas 1–9 e 11–44)

**Problema:** Dois handlers `DOMContentLoaded` independentes. Código acrescido sem refatoração.

**Solução:**
```javascript
document.addEventListener('DOMContentLoaded', function () {
    inicializarBotaoExcluir();
    inicializarCheckboxes();
});
```

---

## Tarefa 8: jQuery como dependência desnecessária

**Severidade:** Média
**Status:** ✅ Resolvido — jQuery removido. `users/create.js` reescrito com Vanilla JS e `fetch()`.
**Arquivos:**
- `src/Views/users/create.php` (linha 226: jQuery 3.6 via CDN)
- `src/Views/users/edit.php` (linha 46: jQuery 3.6 via CDN)
- `public/assets/js/users/create.js` — usa `$().ready`, `$.getJSON`

**Problema:** jQuery 3.6 (~30KB gzipped) carregado apenas para telas de usuário, enquanto o restante usa Vanilla JS. Viola ortogonalidade (Pragmatic Programmer).

**Solução:** Reescrever `users/create.js` em Vanilla JS com `fetch()`:
```javascript
fetch(`https://viacep.com.br/ws/${cep}/json/`)
    .then(r => r.json())
    .then(data => { /* preencher campos */ });
```

---

## Tarefa 9: `alert()` nativo usado para feedback ao usuário

**Severidade:** Média (UX)
**Status:** ✅ Resolvido — Todos `alert()` substituídos por `showFlash()`/`showAlert()` com Bootstrap. Arquivos corrigidos: `sign.js`, `view.js` (6 alerts), `report-141-v2.js`, `report-141-new.js`. Zero `alert()` restantes nos JS.
**Arquivos:**
- `public/assets/js/users/create.js` (linhas 54, 57, 69, 73)
- `public/assets/js/layouts/header-mobile.js` (linha 37)
- `src/Views/departments/create.php` (linha 52)
- `public/assets/js/spreadsheets/import-preview.js` (linha 80: `confirm()`)

**Problema:** `alert()` e `confirm()` bloqueiam a thread, não seguem a identidade visual Bootstrap, e em PWA podem ser suprimidos.

**Solução:** Usar `showFlash()` já existente no projeto e modais Bootstrap para confirmações.

---

## Tarefa 10: `$_SESSION` lida e apagada diretamente dentro de views

**Severidade:** Média (Arquitetura)
**Status:** ✅ Resolvido — `$_SESSION` removida das views individuais.
**Arquivos:**
- `src/Views/churches/edit.php` (linhas 17–24)
- `src/Views/asset-types/create.php` (linhas 16–22)

**Problema:** Views acessam e apagam `$_SESSION` diretamente. O layout `app.php` já processa mensagens flash de forma centralizada (linhas 127–135), causando duplicação ou perda de mensagens.

**Solução:** Remover leitura de `$_SESSION` das views individuais e confiar no mecanismo do layout.

---

## Tarefa 11: Dois campos `hidden` duplicados com mesmo `name`

**Severidade:** Média (Bug)
**Status:** ✅ Resolvido — Campo duplicado removido.
**Arquivos:**
- `src/Views/products/index.php` (linhas 26–27: dois `<input type="hidden" name="comum_id">`)

**Problema:** Campo `comum_id` duplicado por copy-paste. O servidor pode receber array em vez de string.

**Solução:** Remover a linha duplicada (linha 27).

---

## Tarefa 12: `user-scalable=no` no meta viewport

**Severidade:** Média (Acessibilidade)
**Status:** ✅ Resolvido — `user-scalable=no` e `maximum-scale` removidos do viewport.
**Arquivos:**
- `src/Views/layouts/app.php` (linha 39)
- `src/Views/layouts/app_wrapper.php` (linha 19)

**Problema:** `user-scalable=no` e `maximum-scale=1.0` **impedem o zoom**. Viola WCAG 2.1 critério 1.4.4 (Resize Text, nível AA).

**Solução:**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

---

## Tarefa 13: Ausência de `role="alert"` e `aria-live` em mensagens dinâmicas

**Severidade:** Média (Acessibilidade)
**Status:** ✅ Resolvido — `aria-live` adicionado em `users/edit.js`, `users/create.js`, `header-mobile.js` e `import-progress.php`.
**Arquivos:**
- `public/assets/js/users/edit.js` (função `showFlash`, linhas 192–201)
- `src/Views/spreadsheets/import-errors.php` (função `toastFn`, linhas 255–263)
- `public/assets/js/spreadsheets/import-progress.js` (funções `mostrarErro`, `mostrarSucesso`)

**Problema:** Mensagens de feedback criadas via JS não possuem `role="alert"` nem `aria-live="polite"`. Leitores de tela não anunciarão essas mensagens.

**Solução:**
```javascript
el.setAttribute('role', 'alert');
el.setAttribute('aria-live', 'polite');
```

---

## Tarefa 14: `innerHTML` com dados externos em JavaScript

**Severidade:** Alta (Segurança XSS)
**Status:** ✅ Resolvido — `churches/index.js` usa `createElement`/`textContent`.
**Arquivos:**
- `public/assets/js/churches/index.js` (linhas 33–38: `countMsgEl.innerHTML` com `data.count`)
- `public/assets/js/users/edit.js` (linha 197)

**Problema:** `innerHTML` usado para inserir conteúdo com dados de requisições AJAX. Se o backend retornar HTML malicioso, ele seria injetado no DOM.

**Solução:**
```javascript
// Errado:
countMsgEl.innerHTML = 'Serão excluídos <strong>' + n + '</strong>';

// Correto:
countMsgEl.textContent = '';
const strong = document.createElement('strong');
strong.className = 'text-danger';
strong.textContent = n + ' produto(s)';
countMsgEl.append('Serão excluídos ', strong, '.');
```

---

## Tarefa 15: Ausência de `autocomplete` em campos de formulário

**Severidade:** Média (UX / Acessibilidade)
**Status:** ✅ Resolvido — Atributos `autocomplete` adicionados em `login.php`, `users/create.php` e `users/edit.php`.
**Arquivos:**
- `src/Views/auth/login.php` (linhas 55–60)
- `src/Views/users/create.php` (formulário inteiro)
- `src/Views/users/edit.php` (linhas 71–96)

**Problema:** Campos `email`, `senha`, `cep`, `logradouro` sem `autocomplete`. Viola WCAG 1.3.5 (Identify Input Purpose).

**Solução:**
```html
<input type="email" name="email" autocomplete="email">
<input type="password" name="senha" autocomplete="current-password">
<input type="text" name="endereco_cep" autocomplete="postal-code">
```

---

## Tarefa 16: Hierarquia de headings quebrada

**Severidade:** Média (SEO / Acessibilidade)
**Status:** ✅ Resolvido — `header_mobile.php` fornece `h1`. `import-errors.php` usa `h2` com classe visual `h5` (hierarquia h1→h2 correta).
**Arquivos:**
- `src/Views/layouts/partials/header_mobile.php` (linha 38: `<h1>`)
- `src/Views/reports/report-141.php` (linha 94: `<h3>` sem `<h2>`)
- `src/Views/spreadsheets/import-errors.php` (linha 94: `<h5>` como primeiro heading)

**Problema:** Cards de resumo usam `<h3>` sem `<h2>` intermediário. Em `import-errors.php` o primeiro heading é `<h5>`.

**Solução:** Manter hierarquia `h1 > h2 > h3` sem saltos. Usar `<p>` com `<strong>` para dados numéricos.

---

## Tarefa 17: Dois layouts concorrentes (`app.php` vs `app_wrapper.php`)

**Severidade:** Média (Manutenibilidade)
**Status:** ✅ Resolvido — `app_wrapper.php` eliminado. Layout único `app.php` com parâmetros opcionais.
**Arquivos:**
- `src/Views/layouts/app.php`
- `src/Views/layouts/app_wrapper.php`

**Problema:** `app_wrapper.php` não possui header/footer mobile, referencia CSS possivelmente ausente (`app-wrapper-layout.css`), e usa lógica diferente para o manifest PWA. Viola DRY.

**Solução:** Consolidar em um único layout com parâmetros opcionais (`$showHeader = true`, `$showFooter = true`).

---

## Tarefa 18: Assets CDN sem Subresource Integrity (SRI)

**Severidade:** Média (Segurança)
**Status:** ✅ Resolvido — SRI adicionado a todos os CDNs: Bootstrap CSS/JS, Bootstrap Icons, Inputmask. Mecanismo `$headScripts` suporta `integrity` e `crossorigin`.
**Arquivos:**
- `src/Views/layouts/app.php` (linhas 56, 59, 144)
- `src/Views/layouts/app_wrapper.php` (linhas 32, 35, 78)
- `src/Views/auth/login.php` (linha 26)
- `src/Views/users/create.php` (linhas 226–228)
- `src/Views/users/edit.php` (linhas 46–49)

**Problema:** CDNs (Bootstrap, jQuery, Inputmask, SignaturePad) sem atributo `integrity`. CDN comprometido = JS malicioso carregado.

**Solução:**
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM"
      crossorigin="anonymous">
```

---

## Tarefa 19: Ausência de minificação e bundling de CSS/JS

**Severidade:** Média (Performance)
**Status:** ⏳ Pendente — Requer configuração de build pipeline (esbuild/Vite). Cache-busting via `filemtime()` já presente no layout.
**Arquivos:** Todos em `public/assets/css/` e `public/assets/js/`

**Problema:** 30+ arquivos CSS e 20+ arquivos JS servidos sem minificação. 7+ requests CSS por página. Cache-busting ausente nos assets base do layout.

**Solução:**
1. Build simples com esbuild ou Vite para minificar e bundlear
2. Cache-busting via `?v=filemtime()` nos assets base
3. Consolidar os 4 CSS base do layout em um único arquivo

---

## Tarefa 20: `console.debug`/`console.log` em produção

**Severidade:** Baixa
**Status:** ✅ Resolvido — Todos `console.log`/`console.debug` removidos dos JS. Restam apenas `console.error` e `console.warn` (aceitáveis).
**Arquivos:**
- `public/assets/js/users/edit.js` (linha 117)
- `public/assets/js/pwa-install.js` (linhas 17, 24, 38, 47, 68, 151)
- `public/sw.js` (múltiplos)

**Problema:** Logs expõem informações sobre estrutura interna (rotas, estados de PWA, nomes de cache).

**Solução:** Remover ou condicionar:
```javascript
if (window._env === 'dev') console.log('...');
```

---

## Tarefa 21: Caracteres mal-codificados em templates PHP

**Severidade:** Média (UX)
**Status:** ✅ Resolvido — Encoding corrigido: "Rondônia" em `users/edit.php`, "Avançados" em `churches/import-settings.php`, "Ações" em `spreadsheets/view.php`. Demais arquivos já em UTF-8.
**Arquivos:**
- `src/Views/products/sign.php` (linha 97: `"Assinado por vocª"`, linha 101: `"No relat³rio 14.1"`)
- `src/Views/products/index.php` (linhas 29, 33, 44, 79, 116, 196: `DESCRIO`, `Avanados`, `BOTO DE EXCLUSO`)

**Problema:** Arquivos salvos com encoding incorreto (ISO-8859-1 em vez de UTF-8). Texto visível corrompido para os usuários.

**Solução:** Converter para UTF-8 (`File → Save with Encoding → UTF-8`). Configurar `.editorconfig` para forçar UTF-8.

---

## Tarefa 22: `<script>` carregado sem `defer` ou `async`

**Severidade:** Média (Performance)
**Status:** ✅ Resolvido — `defer` adicionado a `header-mobile.js`, `products/edit.js` e `products/create.js`.
**Arquivos:**
- `src/Views/layouts/partials/header_mobile.php` (linha 62: script síncrono dentro do header)
- `src/Views/products/edit.php` (linhas 35–51: scripts antes do formulário)

**Problema:** Script `header-mobile.js` carregado sincroniamente dentro da partial do header, bloqueando o parser HTML.

**Solução:**
```html
<script src="/assets/js/layouts/header-mobile.js" defer></script>
```

---

## Tarefa 23: Formulário de login sem proteção CSRF

**Severidade:** Alta (Segurança)
**Status:** ✅ Resolvido — Token CSRF adicionado ao formulário de `login.php`.
**Arquivos:**
- `src/Views/auth/login.php` (linha 52)

**Problema:** O formulário de login é standalone (não usa `app.php`), portanto o `csrf-global.js` **não é carregado**. Login CSRF permite forçar autenticação com conta do atacante.

**Solução:**
```html
<form method="POST" action="/login">
    <input type="hidden" name="_csrf_token" value="<?= \App\Core\CsrfService::getToken() ?>">
</form>
```

---

## Tarefa 24: Ausência de estado de loading nos botões de submit

**Severidade:** Baixa (UX)
**Status:** ⏳ Pendente — Melhoria de UX para operações lentas (importação de planilha, criação de produto).
**Arquivos:**
- `src/Views/spreadsheets/import.php` (linha 34)
- `src/Views/spreadsheets/import-preview.php` (linhas 141, 144)
- `src/Views/products/create.php` (linha 193)

**Problema:** Botões de operações lentas sem feedback visual. Usuário pode clicar múltiplas vezes.

**Solução:**
```javascript
btn.disabled = true;
btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
```

---

## Tarefa 25: Email convertido para maiúsculas incorretamente

**Severidade:** Média (Bug)
**Status:** ✅ Resolvido — CSS exclui campos `type="email"` do `text-transform: uppercase`.
**Arquivos:**
- `src/Views/users/edit.php` (linha 103: `to_uppercase($usuario['email'])`)
- `public/assets/css/app-layout.css` (linha 17: `text-transform: uppercase` global)

**Problema:** Email exibido em maiúsculas. Pode causar incompatibilidade com sistemas externos.

**Solução:**
```css
input.form-control:not([type="password"]):not([type="email"]) {
    text-transform: uppercase;
}
```

---

## Tarefa 26: Variáveis `window._*` expõem estrutura interna

**Severidade:** Baixa
**Status:** ✅ Parcialmente resolvido — Variáveis agrupadas em `window._appConfig`. Padrão é aceito como bridge PHP→JS.
**Arquivos:**
- `src/Views/products/edit.php` (linhas 36–48)
- `src/Views/products/create.php` (linhas 200–202)
- `src/Views/users/edit.php` (linha 251)

**Problema:** Dados PHP passados para JS via `window._variavel` poluem namespace global.

**Solução:** Agrupar em um único objeto `window._appConfig` ou usar `data-*` attributes.

---

## Pontos Positivos

1. **CSRF global elegante** — `csrf-global.js` intercepta `fetch()`, `XMLHttpRequest` e injeta em formulários via `MutationObserver`
2. **PWA funcional** — Service Worker com estratégia híbrida, manifest correto, suporte iOS
3. **Event delegation correto** — `churches/index.js` usa `e.target.closest()` para listas dinâmicas
4. **Acessibilidade parcial** — `aria-label`, `aria-hidden`, `aria-expanded` nos menus; `aria-live` nas mensagens dinâmicas
5. **`FormHelper` para DRY** — `users/create.php` usa `FormHelper::text()`, `FormHelper::email()`
6. **CSS organizado por domínio** — subpastas `/css/planilhas/`, `/css/produtos/`, `/css/usuarios/`
7. **JavaScript moderno** — `const`/`let`, arrow functions, `async/await`, `fetch`, IIFEs
8. **Cache-busting no layout** — `$customCssPath` com `filemtime()` é bem pensado
9. **Escapamento consistente** — `htmlspecialchars()` e `ViewHelper::e()` em todos os echos
10. **Barra de progresso de importação** — excelente feedback visual para operação longa
11. **SRI em todos os CDNs** — Bootstrap CSS/JS, Icons e Inputmask protegidos com `integrity` + `crossorigin`
12. **Layout único consolidado** — `app.php` com `$headScripts`, `$customCssPath`, `$showHeader`/`$showFooter`
13. **Services para lógica de relatório** — `ReportFillerService` e `Report141FillerService` isolam lógica PHP das views
14. **Zero `alert()` nativos** — feedback visual 100% via Bootstrap alerts/toasts
15. **Zero `console.log` em produção** — apenas `console.error`/`console.warn` mantidos

---

## Score Front-End (Atualizado)

| Dimensão | Nota | Observação |
|---|---|---|
| HTML Semântico | 7/10 | `<main>`, `<header>`, `<footer>`, `<nav>` corretos. Hierarquia h1→h2 respeitada. |
| Acessibilidade | 7/10 | `aria-live` em mensagens dinâmicas. `autocomplete` presente. Viewport sem restrição de zoom. |
| CSS | 6/10 | Sem BEM/SMACSS. Organizado por domínio. Email excluído do uppercase. Sem minificação. |
| JavaScript | 8/10 | ES6+ bem usado, CSRF global elegante. jQuery removido. Zero `alert()`. Zero `console.log`. Scripts com `defer`. |
| UX/UI | 7/10 | Design coeso e mobile-first. Feedback Bootstrap. Sem loading state em botões (T24 pendente). |
| Performance | 5/10 | Sem minificação/bundling (T19 pendente). Cache-busting presente. Scripts com `defer`. SRI nos CDNs. |
| Segurança Front-end | 8/10 | CSRF global + login. SRI em todos CDNs. `ViewHelper::e()` padronizado. `createElement`/`textContent` no lugar de `innerHTML`. |
| **GERAL** | **6.9/10** | Melhorias significativas: SQL removido de views, lógica extraída para Services, jQuery eliminado, segurança reforçada. Pendentes: minificação (T19) e loading states (T24). |

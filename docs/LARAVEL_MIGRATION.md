# Laravel em `app/`

Primeira etapa concluída para migrar o projeto atual sem desligar o legado.

## O que foi criado

- Subprojeto Laravel em `app/`
- Configuração inicial compatível com o banco atual
- Dashboard inicial em `/` do Laravel
- Modelos Eloquent para tabelas legadas principais
- Serviço de inventário para orientar a migração por módulo
- Listagens em modo leitura para:
  - usuários
- CRUD de tipos de bem já operando no Laravel
- CRUD de dependências já operando no Laravel
- Edição de igrejas já operando no Laravel
- CRUD principal de produtos já operando no Laravel
- CRUD principal de usuários já operando no Laravel
- Importação de planilhas já inicia, analisa, salva ações granulares, processa com polling e trata erros pelo Laravel
- O núcleo de importação (`ConnectionManager`, parser, processamento, repositórios e value objects) já foi trazido para dentro de `app/app`, sem depender de autoload apontando para `../src`
- Relatórios 14.x já listam e renderizam prévia pelo Laravel com templates e fillers locais em `app/resources/legacy-reports`
- Histórico de alterações dos relatórios já filtra e imprime pelo Laravel
- Editor visual de células já abre por Blade própria do Laravel
- Editor visual de células já usa assets locais do subprojeto, sem depender de CDN para `interactjs`
- O CSS base dos templates da seção 14 também já foi publicado em `app/public`, sem depender de arquivos servidos por `public/` do legado
- Login do subprojeto Laravel já autentica contra a tabela legada de usuários
- A igreja/comum ativa já é mantida em sessão e compartilhada no topo da navegação do Laravel
- Áreas administrativas do `app/` já respeitam perfil admin no acesso às rotas
- Os módulos CRUD principais já deixaram de depender dos botões de fallback para o legado
- O dashboard Laravel já prioriza navegação interna e deixou de oferecer atalho operacional para o sistema antigo
- O entrypoint raiz em `public/index.php` agora aponta diretamente para o front controller do Laravel em `app/public/index.php`
- Os acessos públicos antigos (`/assinatura-publica` e `/logout-publico`) já têm rota própria no Laravel
- O módulo de relatórios já roda localmente no `app/`, sem incluir templates ou fillers diretamente de `src/Views/reports`
- Os utilitários de produto (`observation`, `check`, `label`, `sign` e `clear-edits`) já aceitam chamadas híbridas no Laravel com compatibilidade de CSRF para tokens do legado
- A tela `GET /products/label` já renderiza no Laravel a cópia de códigos para etiqueta com filtro por dependência
- Os endpoints antigos de igrejas para contagem e exclusão em massa de produtos já respondem no Laravel (`/churches/products-count` e `/churches/delete-products`)
- Os endpoints antigos de processamento de importação (`/spreadsheets/process-file` e `/spreadsheets/api/progress`) já respondem no Laravel com o contrato JSON legado
- O endpoint de consulta de CNPJ (`/api/cnpj-lookup`) já responde no Laravel, com validação de payload e preenchimento automático da edição de igreja
- Os callbacks antigos da prévia de importação (`/spreadsheets/preview/save-actions`, `/spreadsheets/preview/bulk-action`, `/spreadsheets/confirm` e `/spreadsheets/import-errors*`) já têm compatibilidade no Laravel enquanto a migração final não é concluída

## Como subir

```bash
cd app
php artisan serve
```

## Entrypoint raiz

- O `public/index.php` da raiz agora apenas encaminha para o front controller do Laravel em `app/public/index.php`.
- O front controller antigo da raiz já foi removido.

## Estratégia recomendada

1. Migrar primeiro:
   - os pontos restantes que ainda usam o legado apenas como inventário ou metadado
2. Depois migrar:
   - refinamentos de UX e permissões por usuário mais granulares além do perfil admin
   - limpeza dos últimos artefatos de inventário e bridges visuais residuais
3. Use `hybrid` apenas quando precisar comparar comportamento legado versus Laravel; o padrão principal já deve permanecer em `laravel`.

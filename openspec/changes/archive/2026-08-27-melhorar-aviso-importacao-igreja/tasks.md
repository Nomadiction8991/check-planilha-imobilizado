## 1. Testes (TDD - Red Phase)

- [x] 1.1 Criar teste de feature para verificar banner vermelho na tela de importação
- [x] 1.2 Criar teste de feature para verificar banner vermelho na prévia de importação
- [x] 1.3 Rodar testes e confirmar falha (RED)

## 2. Implementação - Tela de Importação

- [x] 2.1 Alterar `resources/views/spreadsheets/import.blade.php`: mudar cores do banner para vermelho
- [x] 2.2 Atualizar texto do banner: título "A importação processa a **igreja inteira** (todas as dependências), não apenas um setor"
- [x] 2.3 Manter nota secundária de performance como parágrafo menor

## 3. Implementação - Prévia de Importação

- [x] 3.1 Alterar `resources/views/spreadsheets/preview.blade.php`: adicionar novo banner vermelho antes da seção "Igrejas detectadas"
- [x] 3.2 Usar estilo consistente com o banner da tela de importação
- [x] 3.3 Texto: "A importação processa a **igreja inteira** — ao confirmar, todos os setores da(s) igreja(s) selecionada(s) serão importados"

## 4. Testes (Green Phase) e Validação

- [x] 4.1 Rodar testes de feature e confirmar passagem (GREEN)
- [x] 4.2 Rodar `php -l` nos arquivos alterados
- [x] 4.3 Testar manualmente: acessar `/spreadsheets/import` e verificar banner vermelho
- [x] 4.4 Testar manualmente: acessar prévia de importação e verificar banner vermelho antes de "Igrejas detectadas"

## 5. Commit e Deploy

- [x] 5.1 Commit seguindo conventional commits
- [x] 5.2 Push para main (dispara deploy automático)
- [x] 5.3 Verificar saúde da produção (curl 200 no login)
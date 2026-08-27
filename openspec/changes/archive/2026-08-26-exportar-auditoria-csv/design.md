# Design: Exportar auditoria em CSV

## Contexto

A tela de auditoria consulta um serviço que lê eventos de um log JSONL e devolve
um paginador. Os relatórios já possuem exportação CSV com padrão consolidado:
BOM UTF-8, separador ponto e vírgula, geração via stream em memória e resposta
de download no controller. Esta mudança segue exatamente o mesmo padrão.

## Decisões

1. **Serviço**: novo método `exportCsv` na interface de trilha de auditoria,
   recebendo os mesmos filtros/escopo do `paginate` e devolvendo
   `array{filename: string, content: string}` — mesmo contrato dos relatórios.
   A exportação varre TODAS as entradas que passam nos filtros (sem paginação).
2. **Controller**: nova ação `export` no controller de auditoria. Recebe os
   mesmos parâmetros de filtro da listagem (`busca`, `modulo`, `data_inicio`,
   `data_fim`), chama a exportação e responde com download em streaming. Sem
   eventos: redireciona de volta à listagem mantendo os filtros, com mensagem
   de status amigável (padrão já usado nas telas do sistema).
3. **Rota**: GET `/audits/export`, protegida pelo middleware
   `legacy.permission:audits.view` (mesma permissão da listagem), nomeada para
   o grupo de rotas de migração.
4. **View**: botão "Exportar CSV" dentro do formulário de filtros como link
   montado com os parâmetros atuais da query — preserva busca, módulo e datas.
5. **CSV**: colunas Data/Hora, Usuário, E-mail, Administração, Igreja, Módulo,
   Ação, Descrição, Rota, Caminho, Método, HTTP e IP. Células vazias para
   valores ausentes. Nome do arquivo com prefixo fixo e timestamp.

## Riscos

- Arquivos de log muito grandes: a leitura atual carrega tudo em memória;
  aceito neste estágio (mesmo comportamento do `paginate` existente) e fora do
  escopo desta mudança otimizar streaming incremental.
- PHP 8.5 cita campos com espaço no CSV: testes fazem round-trip com leitura
  de volta usando separador ponto e vírgula, nunca comparação por string crua.

## Testes

- Unitário do serviço: exportação contém todas as linhas filtradas; escopo
  aplicado; arquivo vazio gera sinalização para mensagem amigável.
- Feature do controller: download OK com conteúdo esperado; sem eventos
  redireciona com status; rota exige permissão; botão presente na view.

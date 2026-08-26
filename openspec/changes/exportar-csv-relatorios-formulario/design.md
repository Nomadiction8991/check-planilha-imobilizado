# Design: Exportar CSV dos Relatórios de Formulário

## Decisões

### Camada de serviço
- Novo método `downloadFormularioCsv(int $churchId, string $formulario): array`
  em `LegacyReportService` (+ entrada na `LegacyReportServiceInterface`),
  retornando `{filename, content}` como o backup da posição já faz.
- Reaproveita `loadChurchData()` e `loadProductsByForm()` — mesma fonte de
  dados da prévia, garantindo consistência entre tela e CSV.
- Montagem do CSV com `fopen('php://temp')`, BOM UTF-8 (`\xEF\xBB\xBF`) e
  `fputcsv(..., ';')` — mesmo padrão de `buildVerificationPositionCsv()`.
- Nome: `relatorio_14.1_12-3456_Ymd_His.csv` via `formatShortCode()`.

### Colunas
- 14.1: Código; Condicao; Descricao original; Descricao atual; Dependencia;
  Tipo nota (1=com documento, 2=sem documento, 3=até cinco anos com
  documento); Numero nota; Data nota; Valor nota; Fornecedor.
  Condição mapeada para rótulo legível; nota fiscal só preenchida nas
  condições 1 e 3 (espelha o filler do template).
- 14.6: Codigo; Descricao original; Descricao atual; Tipo bem original;
  Tipo bem editado; Dependencia original; Dependencia editada.

### Rota e controller
- `GET /reports/{formulario}/export` → `exportFormulario`, nome
  `migration.reports.export`, middleware `legacy.permission:reports.view`,
  registrado no mesmo grupo de `/reports/{formulario}`. A rota específica
  fica antes, sem colidir com `whereNumber` existentes.
- Controller valida igreja (`<=0` → redirect), formulário inválido e
  ausência de itens via `RuntimeException` do serviço → redirect com flash.

### View
- Botão "Baixar CSV" na `reports/show.blade.php`, ao lado de "Editar células"
  e "Imprimir", apontando para a rota nova com `comum_id`.

## Riscos
- SQL idêntico ao da prévia já roda em produção (MySQL) e nos testes
  (SQLite): expressões CAST verificadas para ambos os drivers.
- Nenhum dado novo persistido; leitura apenas.

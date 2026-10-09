# Proposta: Suporte Universal de Importação (CSV, XLS e XLSX)

## Motivação e Contexto
O sistema `checkplanilha` recebia relatórios de bens imobilizados exportados de sistemas corporativos (como o sistema da CCB) em múltiplos formatos: `.csv` delimitado por vírgula com cabeçalho expandido, arquivos binários clássicos do Excel `.xls` (BIFF8 / OLE2) e planilhas modernas `.xlsx`.

Anteriormente:
1. O `StoreSpreadsheetImportRequest` bloqueava o envio de arquivos `.xls` e `.xlsx` com a regra estrita `mimes:csv,txt`.
2. A leitura em `CsvParserService` dependia exclusivamente de `League\Csv\Reader`, falhando ao tentar processar arquivos binários do Excel ou planilhas com cabeçalhos deslocados e colunas intercaladas vazias.

## Objetivos
1. Criar `App\Support\UniversalSpreadsheetReader` em PHP puro (zero dependências externas) capaz de decodificar:
   - Arquivos `.csv` e `.txt` com detecção de delimitador (`,` ou `;`) e conversão de charset (Windows-1252 / ISO-8859-1 para UTF-8).
   - Arquivos `.xls` binários do Excel (BIFF8 OLE2) lendo registros `LABEL` e `NUMBER` com conversão de datas seriais do Excel.
   - Arquivos `.xlsx` modernos via `ZipArchive` e `SimpleXML`.
2. Habilitar auto-detecção dinâmica do cabeçalho da planilha (localizando as colunas reais de `Código`, `Nome`, `Dependência`, etc.), ignorando linhas de espaçamento vazias e metadados de cabeçalho.
3. Permitir o upload de extensões `.csv`, `.txt`, `.xls` e `.xlsx` no formulário e na validação da requisição.

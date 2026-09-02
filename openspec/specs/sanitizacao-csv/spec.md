# sanitizacao-csv Specification

## Purpose
TBD - created by archiving change centralizar-sanitizacao-csv. Update Purpose after archive.
## Requirements
### Requirement: Centralização da Neutralização de Fórmulas em CSV
O sistema SHALL fornecer uma classe utilitária `App\Support\LegacyCsvSanitizer` para neutralizar strings e linhas completas contra injeção de fórmulas CSV (CWE-1236).

#### Scenario: Texto iniciado por caractere de fórmula
- GIVEN um valor de texto iniciado por `=SUM(A1)`, `+123`, `-cmd`, `@SUM` ou tabulação/retorno de carro
- WHEN o método `LegacyCsvSanitizer::sanitizeText` é invocado
- THEN o retorno SHALL iniciar com aspa simples `'` prefixada ao valor original.

#### Scenario: Texto seguro ou vazio
- GIVEN um valor de texto seguro (ex.: `CADEIRA DE MADEIRA`) ou nulo/vazio
- WHEN o método `LegacyCsvSanitizer::sanitizeText` é invocado
- THEN o retorno SHALL preservar o texto intacto ou retornar string vazia para nulo.

#### Scenario: Sanitização de linha inteira
- GIVEN um array associativo ou posicional contendo campos textuais e numéricos
- WHEN o método `LegacyCsvSanitizer::sanitizeRow` é invocado
- THEN cada campo textual da linha SHALL ser neutralizado individualmente preservando a ordem dos campos.


# Glossário de Domínio (GLOSSARY.md)

Este documento define os termos e conceitos de negócio utilizados no sistema **Check Planilha Imobilizado**, alinhados aos princípios de design de código (*codebase-design*).

---

## 1. Importação de Planilhas (Spreadsheet Import)

- **Importação (`importacoes`):** Entidade agregadora que representa um ciclo de leitura e processamento de um relatório patrimonial (em formatos CSV, TXT, XLS ou XLSX). Possui status (`aguardando`, `processando`, `concluida`, `erro`), contadores de linhas e vínculo com a administração e usuário responsável.
- **Análise Prévia (Spreadsheet Preview / Diff):** Comparação executada entre os dados contidos na planilha enviada e o cadastro existente no banco de dados. Classifica cada registro como `novo`, `atualizar`, `sem_alteracao`, `excluir` ou `erro`.
- **Ações da Prévia (`importacao_acoes`):** Intenções registradas pelo operador na tela de prévia antes da execução definitiva, determinando se uma igreja, dependência ou item específico deve ser importado (`importar`), ignorado (`pular`) ou removido (`excluir`).
- **Lote de Processamento (Batch Processing):** Execução atômica em grupos (tamanho padrão de 100 registros) que persiste as alterações aprovadas na tabela de produtos e registra falhas pontuais na tabela de erros.
- **Erros de Importação (`import_erros`):** Falhas em linhas específicas da planilha (ex.: produto sem nome, código inválido), mantidas para resolução manual ou exportação de relatório corretivo.

---

## 2. Estrutura Organizacional e Patrimônio

- **Administração (`administracoes`):** Unidade administrativa regional que agrupa congregações e estabelece o escopo multi-tenant de acesso e visualização.
- **Comum / Congregação (`comums`):** Igreja local identificada por um código padrão (ex.: `09-0565`), pertencente a uma administração.
- **Dependência (`dependencias`):** Setor, sala ou dependência física interna de uma congregação (ex.: `Templo`, `Sala de Reunião`).
- **Tipo de Bem (`tipos_bens`):** Classificação padronizada do patrimônio (ex.: `4 - Cadeira`, `7 - Mesa`).
- **Produto / Bem (`produtos`):** Item do patrimônio inventariado, identificado por código único, vinculado a uma congregação, tipo e dependência.

---

## 3. Arquitetura e Pontos de Contato (Seams)

- **Pipeline de Importação (`LegacySpreadsheetImportServiceInterface`):** Módulo profundo que esconde a complexidade de conversão de formatos (XLS/CSV), diff comparativo, persistência relacional de ações e processamento transacional em lotes.
- **Seam de Banco de Dados:** Ponto de contato único utilizando o gerenciador de conexões nativo do Laravel (`DB::connection()->getPdo()`), garantindo transações atômicas consistentes e compatibilidade entre MySQL em produção e SQLite em testes.

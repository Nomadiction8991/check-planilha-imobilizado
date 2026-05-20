# Estrutura Interna e Processos

Este documento descreve a arquitetura técnica e os processos internos do sistema.

Para a organização por telas:

- [Visual por tela](visual/telas.md)
- [Passo a passo por tela](passo-a-passo/telas.md)

## 1. Stack Tecnológica
- **Backend**: Laravel 10/11 (PHP 8.2+).
- **Frontend**: Blade Templates, Vanilla JavaScript (AJAX/Fetch), CSS Moderno.
- **Banco de Dados**: PostgreSQL/MySQL (Compatível com esquema legado).
- **Assets**: Vite para compilação de recursos.

## 2. Arquitetura de Software
O sistema segue padrões modernos de desenvolvimento Laravel para garantir manutenibilidade durante a migração:
- **Camada de Serviços (Services)**: Toda a lógica de negócio pesada (como processamento de planilhas e regras de produtos) reside em `app/Services`.
- **Contratos (Interfaces)**: Uso intensivo de `app/Contracts` para desacoplar a implementação da lógica, facilitando a substituição de componentes legados.
- **DTOs (Data Transfer Objects)**: Utilizados para trafegar dados de filtros e inputs de forma tipada.
- **Middleware de Bridge**: O `legacy.bridge` e `legacy.auth` garantem que a sessão e autenticação sejam compartilhadas entre o código novo (Laravel) e o banco/sessão preexistente.

## 3. Processo de Importação
O motor de importação é o coração do sistema:
1. **Upload & Parse**: O `CsvParserService` lê o arquivo e valida o cabeçalho.
2. **Análise de Diferença**: O sistema compara os dados da planilha com o banco de dados atual.
3. **Persistência de Ações**: Antes de processar, as intenções (Criar, Atualizar, Excluir) são salvas em uma tabela de `importacao_acoes`.
4. **Processamento Assíncrono/Polling**: O processamento é feito em blocos para evitar timeout, com o frontend consultando o progresso via endpoint de API.

## 4. Sistema de Permissões
A autorização é baseada em permissões granulares (`legacy.permission` middleware):
- As permissões são carregadas da sessão legada e validadas nas rotas do Laravel.
- Exemplos: `products.view`, `spreadsheets.import`, `users.permissions.manage`.

## 5. Auditoria
- **Audit Logging**: O middleware `legacy.audit` registra ações importantes (quem fez o quê e quando) para conformidade e rastreabilidade de alterações no patrimônio.

## 6. Sincronização de Verificação
- A tela de verificação física utiliza um endpoint de "sync" (`syncVerification`) que permite salvar cada item individualmente via JSON, garantindo que o usuário não perca o progresso de uma conferência longa.

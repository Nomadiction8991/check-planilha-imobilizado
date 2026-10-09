# Proposta: Sistema de Diagnóstico e Manutenção Preventiva

## Motivação e Contexto
O aplicativo `check-planilha-imobilizado` manipula rotineiramente importações de arquivos CSV/planilhas, sessões de atendimento público e consultas a banco de dados com escopos administrativos.
Com o uso contínuo, arquivos temporários podem se acumular em `storage/`, e operações de manutenção do servidor (deploy, migrações) exigem diagnósticos rápidos de integridade e modos de manutenção controlados.

## Objetivos
1. Fornecer comando de diagnóstico do sistema (`system:health-check`) que valida:
   - Conectividade com o banco de dados.
   - Permissões de escrita e saúde dos diretórios em `storage/` e `bootstrap/cache`.
   - Espaço livre em disco para garantir segurança em novas importações.
2. Fornecer comando de limpeza e manutenção preventiva (`system:storage-cleanup`):
   - Higienização segura de arquivos temporários antigos em `storage/tmp` e `storage/app/tmp`.
   - Respeito à preservação de análises ativas e importações pendentes.
3. Registrar rotinas de manutenção agendadas no `routes/console.php`.

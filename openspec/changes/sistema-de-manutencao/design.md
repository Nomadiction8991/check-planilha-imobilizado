# Design Técnico: Sistema de Manutenção

## Componentes

### 1. `App\Console\Commands\SystemHealthCheckCommand`
- **Assinatura:** `system:health-check {--json : Retorna o diagnóstico em formato JSON}`
- **Funções:**
  - Checa conexão ativa com a base de dados via Eloquent / PDO.
  - Checa diretórios críticos (`storage/app`, `storage/framework`, `storage/logs`, `bootstrap/cache`).
  - Reporta métricas de disco e espaço livre.
  - Retorna código `Command::SUCCESS` ou `Command::FAILURE`.

### 2. `App\Console\Commands\SystemStorageCleanupCommand`
- **Assinatura:** `system:storage-cleanup {--days=7 : Dias de retenção para arquivos temporários} {--dry-run : Apenas simula sem deletar}`
- **Funções:**
  - Varre pastas de arquivos temporários (`storage/tmp`, `storage/app/tmp`).
  - Remove arquivos temporários mais antigos que o período de retenção.
  - Notifica quantidade de arquivos e bytes liberados.

### 3. Agendamento (`routes/console.php`)
- Agendamento da rotina de limpeza de órfãos e manutenção periódica diária fora do horário de pico.

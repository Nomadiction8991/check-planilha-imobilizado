<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migração para suportar importação multi-igreja.
 * 
 * 1. comums.codigo: INT → VARCHAR(50) para suportar códigos como "09-0038"
 * 2. importacoes.comum_id: NOT NULL → NULL para importações multi-igreja
 */
final class SupportMultiIgrejaImport extends AbstractMigration
{
    public function up(): void
    {
        // Consolidado em 20260211120000_initial_schema.php — no-op
        return;
    }

    public function down(): void
    {
        // No-op (consolidado)
        return;
    }
}

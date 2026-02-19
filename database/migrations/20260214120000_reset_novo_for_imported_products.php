<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige o campo 'novo' para produtos importados.
 *
 * O campo 'novo' deve ser 1 APENAS para produtos cadastrados manualmente.
 * Produtos vindos de importação de planilha devem ter novo = 0.
 * Anteriormente a importação setava novo = 1 por engano.
 */
final class ResetNovoForImportedProducts extends AbstractMigration
{
    public function up(): void
    {
        // Consolidado no esquema inicial — no-op
        return;
    }

    public function down(): void
    {
        // No-op (consolidado)
        return;
    }
}

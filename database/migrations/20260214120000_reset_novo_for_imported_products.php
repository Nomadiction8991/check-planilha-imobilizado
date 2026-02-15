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
        // Todos os produtos existentes vieram de importação, então novo = 0
        $this->execute('UPDATE produtos SET novo = 0 WHERE novo = 1');
    }

    public function down(): void
    {
        // Reverter: marcar todos como novo = 1 (estado anterior)
        $this->execute('UPDATE produtos SET novo = 1 WHERE novo = 0');
    }
}

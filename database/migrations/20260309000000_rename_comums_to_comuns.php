<?php

use Phinx\Migration\AbstractMigration;

class RenameComumsToComuns extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('RENAME TABLE `comums` TO `comuns`');
    }

    public function down(): void
    {
        $this->execute('RENAME TABLE `comuns` TO `comums`');
    }
}

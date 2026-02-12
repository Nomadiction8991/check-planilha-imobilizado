<?php

use Phinx\Migration\AbstractMigration;

class AddDescricaoVelhaToProdutos extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE produtos 
            ADD COLUMN descricao_velha TEXT NULL AFTER descricao_completa
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE produtos 
            DROP COLUMN descricao_velha
        ");
    }
}

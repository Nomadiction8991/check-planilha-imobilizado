<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImportacaoFieldsToProdutos extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $this->execute(<<<SQL
            ALTER TABLE produtos
            ADD COLUMN bem_identificado TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Indica se o bem foi identificado corretamente na importação (1=sim, 0=não identificado)',
            ADD COLUMN nome_planilha TEXT NULL COMMENT 'Nome original do item vindo da planilha (ex: 1x - Banco 2,50m [Banheiro])'
        SQL);
    }
}

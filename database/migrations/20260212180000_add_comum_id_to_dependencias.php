<?php

use Phinx\Migration\AbstractMigration;

class AddComumIdToDependencias extends AbstractMigration
{
    public function up()
    {
        // Adicionar coluna comum_id à tabela dependencias
        $this->execute("
            ALTER TABLE `dependencias` 
            ADD COLUMN `comum_id` int NULL AFTER `id`,
            ADD KEY `idx_dependencias_comum_id` (`comum_id`),
            ADD CONSTRAINT `fk_dependencias_comum` 
                FOREIGN KEY (`comum_id`) REFERENCES `comums` (`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE
        ");

        // Remover unique key de descricao (agora pode repetir entre comuns diferentes)
        $this->execute("ALTER TABLE `dependencias` DROP INDEX `descricao`");
        
        // Adicionar unique composto (comum_id + descricao)
        $this->execute("
            ALTER TABLE `dependencias` 
            ADD UNIQUE KEY `uk_comum_descricao` (`comum_id`, `descricao`)
        ");
    }

    public function down()
    {
        $this->execute("ALTER TABLE `dependencias` DROP FOREIGN KEY `fk_dependencias_comum`");
        $this->execute("ALTER TABLE `dependencias` DROP INDEX `uk_comum_descricao`");
        $this->execute("ALTER TABLE `dependencias` DROP INDEX `idx_dependencias_comum_id`");
        $this->execute("ALTER TABLE `dependencias` DROP COLUMN `comum_id`");
        $this->execute("ALTER TABLE `dependencias` ADD UNIQUE KEY `descricao` (`descricao`)");
    }
}

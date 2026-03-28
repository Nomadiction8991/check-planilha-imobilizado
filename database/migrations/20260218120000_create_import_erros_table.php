<?php

use Phinx\Migration\AbstractMigration;

class CreateImportErrosTable extends AbstractMigration
{
    public function up()
    {
        $this->execute(
            <<<SQL
CREATE TABLE IF NOT EXISTS `import_erros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `importacao_id` int NOT NULL,
  `linha_csv` varchar(50) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `localidade` varchar(255) DEFAULT NULL,
  `codigo_comum` varchar(50) DEFAULT NULL,
  `descricao_csv` text DEFAULT NULL,
  `bem` varchar(255) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `dependencia` varchar(255) DEFAULT NULL,
  `mensagem_erro` text NOT NULL,
  `resolvido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_import_erros_importacao` (`importacao_id`),
  KEY `idx_import_erros_resolvido` (`resolvido`),
  CONSTRAINT `fk_import_erros_importacao` FOREIGN KEY (`importacao_id`) REFERENCES `importacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    public function down()
    {
        $this->execute("DROP TABLE IF EXISTS `import_erros`");
    }
}

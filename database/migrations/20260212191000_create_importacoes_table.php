<?php

use Phinx\Migration\AbstractMigration;

class CreateImportacoesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
CREATE TABLE importacoes (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  comum_id INT NOT NULL,
  arquivo_nome VARCHAR(255) NOT NULL,
  arquivo_caminho VARCHAR(500) NOT NULL,
  total_linhas INT NOT NULL DEFAULT 0,
  linhas_processadas INT NOT NULL DEFAULT 0,
  linhas_sucesso INT NOT NULL DEFAULT 0,
  linhas_erro INT NOT NULL DEFAULT 0,
  porcentagem DECIMAL(5,2) NOT NULL DEFAULT 0,
  status ENUM('aguardando', 'processando', 'concluida', 'erro') NOT NULL DEFAULT 'aguardando',
  mensagem_erro TEXT NULL,
  iniciada_em TIMESTAMP NULL,
  concluida_em TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_usuario (usuario_id),
  KEY idx_comum (comum_id),
  KEY idx_status (status),
  KEY idx_created (created_at),
  CONSTRAINT fk_importacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_importacoes_comum FOREIGN KEY (comum_id) REFERENCES comums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS importacoes");
    }
}

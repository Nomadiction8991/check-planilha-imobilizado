<?php

use Phinx\Migration\AbstractMigration;

class InitialSchema extends AbstractMigration
{
        public function up()
        {
                // Criar tabelas na ordem de dependências (esquema consolidado — sem seeds)

                // comums.codigo como VARCHAR(50) (suporte multi-igreja)
                $this->execute(<<<SQL
CREATE TABLE `comums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `cnpj` varchar(255) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `administracao` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `setor` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                $this->execute(<<<SQL
CREATE TABLE `configuracoes` (
  `posicao_data` varchar(255) NOT NULL DEFAULT 'D13',
  `pulo_linhas` varchar(255) NOT NULL DEFAULT '25',
  `mapeamento_colunas` varchar(255) NOT NULL DEFAULT 'codigo=A;complemento=D;dependencia=P;localidade=K',
  `data_importacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
SQL
                );

                // dependencias agora pertence a uma comum (comum_id) e tem unique composto
                $this->execute(<<<SQL
CREATE TABLE `dependencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comum_id` int DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dependencias_comum_id` (`comum_id`),
  UNIQUE KEY `uk_comum_descricao` (`comum_id`, `descricao`),
  CONSTRAINT `fk_dependencias_comum` FOREIGN KEY (`comum_id`) REFERENCES `comums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                $this->execute(<<<SQL
CREATE TABLE `tipos_bens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                // usuarios com comum_id (nullable)
                $this->execute(<<<SQL
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `casado` tinyint(1) NOT NULL DEFAULT '0',
  `nome_conjuge` varchar(150) DEFAULT NULL,
  `cpf_conjuge` varchar(14) DEFAULT NULL,
  `rg_conjuge` varchar(20) DEFAULT NULL,
  `rg_conjuge_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `telefone_conjuge` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `rg_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `endereco_cep` varchar(10) DEFAULT NULL,
  `endereco_logradouro` varchar(255) DEFAULT NULL,
  `endereco_numero` varchar(10) DEFAULT NULL,
  `endereco_complemento` varchar(100) DEFAULT NULL,
  `endereco_bairro` varchar(100) DEFAULT NULL,
  `endereco_cidade` varchar(100) DEFAULT NULL,
  `endereco_estado` varchar(2) DEFAULT NULL,
  `comum_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_usuarios_rg` (`rg`),
  KEY `idx_usuarios_cpf_conjuge` (`cpf_conjuge`),
  KEY `idx_usuarios_cpf` (`cpf`),
  KEY `idx_usuario_comum` (`comum_id`),
  CONSTRAINT `fk_usuarios_comum` FOREIGN KEY (`comum_id`) REFERENCES `comums` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                // produtos — adicionadas colunas consolidadas e valores default apropriados
                $this->execute(<<<SQL
CREATE TABLE `produtos` (
  `comum_id` int NOT NULL,
  `id_produto` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `descricao_completa` varchar(255) NOT NULL,
  `descricao_velha` TEXT DEFAULT NULL,
  `editado_descricao_completa` varchar(255) NOT NULL,
  `tipo_bem_id` int NOT NULL,
  `editado_tipo_bem_id` int NOT NULL,
  `bem` text NOT NULL,
  `editado_bem` varchar(255) NOT NULL,
  `complemento` text DEFAULT NULL,
  `editado_complemento` varchar(255) NOT NULL,
  `dependencia_id` int NOT NULL,
  `editado_dependencia_id` int NOT NULL,
  `novo` int NOT NULL DEFAULT 0,
  `checado` int NOT NULL,
  `editado` int NOT NULL,
  `imprimir_etiqueta` int NOT NULL,
  `imprimir_14_1` int NOT NULL,
  `condicao_14_1` varchar(255) DEFAULT NULL,
  `observacao` varchar(255) NOT NULL,
  `nota_numero` int DEFAULT NULL,
  `nota_data` date DEFAULT NULL,
  `nota_valor` varchar(255) DEFAULT NULL,
  `nota_fornecedor` varchar(255) DEFAULT NULL,
  `administrador_acessor_id` int DEFAULT NULL,
  `doador_conjugue_id` int DEFAULT NULL,
  `bem_identificado` tinyint(1) NOT NULL DEFAULT 1,
  `nome_planilha` TEXT DEFAULT NULL,
  `ativo` int NOT NULL,
  PRIMARY KEY (`id_produto`),
  KEY `idx_produtos_codigo` (`codigo`),
  KEY `idx_produtos_comum_id` (`comum_id`),
  KEY `idx_produtos_tipo_bem_id` (`tipo_bem_id`),
  KEY `idx_produtos_dependencia_id` (`dependencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                // importacoes (consolidada)
                $this->execute(<<<SQL
CREATE TABLE `importacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `comum_id` int DEFAULT NULL,
  `arquivo_nome` varchar(255) NOT NULL,
  `arquivo_caminho` varchar(500) NOT NULL,
  `total_linhas` int NOT NULL DEFAULT 0,
  `linhas_processadas` int NOT NULL DEFAULT 0,
  `linhas_sucesso` int NOT NULL DEFAULT 0,
  `linhas_erro` int NOT NULL DEFAULT 0,
  `porcentagem` decimal(5,2) NOT NULL DEFAULT 0,
  `status` enum('aguardando','processando','concluida','erro') NOT NULL DEFAULT 'aguardando',
  `mensagem_erro` text NULL,
  `iniciada_em` timestamp NULL DEFAULT NULL,
  `concluida_em` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_comum` (`comum_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_importacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_importacoes_comum` FOREIGN KEY (`comum_id`) REFERENCES `comums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                $this->execute(<<<SQL
CREATE TABLE `import_job_processed` (
  `job_id` varchar(128) NOT NULL,
  `id_produto` int NOT NULL,
  `comum_id` int NOT NULL,
  PRIMARY KEY (`job_id`,`id_produto`),
  KEY `idx_comum` (`comum_id`,`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
SQL
                );

                // seeds removidos — todas as migrations foram consolidadas no esquema acima
        }

        public function down()
        {
                // Drop tables (ordem inversa das dependências)
                $this->execute("DROP TABLE IF EXISTS importacoes");
                $this->execute("DROP TABLE IF EXISTS import_job_processed");
                $this->execute("DROP TABLE IF EXISTS produtos");
                $this->execute("DROP TABLE IF EXISTS usuarios");
                $this->execute("DROP TABLE IF EXISTS tipos_bens");
                $this->execute("DROP TABLE IF EXISTS dependencias");
                $this->execute("DROP TABLE IF EXISTS configuracoes");
                $this->execute("DROP TABLE IF EXISTS comums");
        }
}

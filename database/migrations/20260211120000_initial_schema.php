<?php

use Phinx\Migration\AbstractMigration;

class InitialSchema extends AbstractMigration
{
        public function up()
        {
                $this->execute(
                        <<<SQL
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

                $this->execute(
                        <<<SQL
CREATE TABLE `configuracoes` (
  `posicao_data` varchar(255) NOT NULL DEFAULT 'D13',
  `pulo_linhas` varchar(255) NOT NULL DEFAULT '25',
  `mapeamento_colunas` varchar(255) NOT NULL DEFAULT 'codigo=A;complemento=D;dependencia=P;localidade=K',
  `data_importacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
SQL
                );

                $this->execute(
                        <<<SQL
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

                $this->execute(
                        <<<SQL
CREATE TABLE `tipos_bens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
                );

                $this->execute(
                        <<<SQL
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

                $this->execute(
                        <<<SQL
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


                $this->execute(
                        <<<SQL
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

                $this->execute(
                        <<<SQL
CREATE TABLE `import_job_processed` (
  `job_id` varchar(128) NOT NULL,
  `id_produto` int NOT NULL,
  `comum_id` int NOT NULL,
  PRIMARY KEY (`job_id`,`id_produto`),
  KEY `idx_comum` (`comum_id`,`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
SQL
                );

                $this->execute(
                        <<<SQL
CREATE TABLE `import_erros` (
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

                $this->execute(
                        <<<SQL
INSERT INTO tipos_bens (codigo, descricao) VALUES
(1, 'BANCO DE MADEIRA/GENUFLEXORIO'),
(2, 'TRIBUNA/CRIADO MUDO'),
(3, 'POLTRONA / SOFA'),
(4, 'CADEIRA'),
(5, 'GRADE DE MADEIRA P/ ORGAO'),
(6, 'REFRIGERADOR/FREEZER/FRIGOBAR'),
(7, 'MESA'),
(8, 'ARMARIO'),
(9, 'EQUIPAMENTOS DE LIMPEZA'),
(10, 'ARQUIVO / GAVETEIRO'),
(11, 'PRATELEIRA / ESTANTE'),
(12, 'BALCAO/BANCADA'),
(13, 'BEBEDOURO DAGUA / PURIFICADOR DE AGUA'),
(14, 'VENTILADOR'),
(15, 'RELOGIO DE PAREDE'),
(16, 'PAINEL DE CONTROLE DE SOM'),
(17, 'CAIXA DE SOM'),
(18, 'MICROFONE'),
(19, 'COMPUTADOR (CPU+MOUSE+TECLADO) / NOTEBOOK'),
(20, 'IMPRESSORA'),
(21, 'ORGAO E INSTRUMENTOS'),
(22, 'CALCULADORA'),
(23, 'EQUIPAMENTO DE ESCRITORIO'),
(24, 'MAQUINAS E EQUIPAMENTOS DE COSTURA'),
(25, 'EQUIPAMENTOS DE JARDINAGEM'),
(26, 'FORNO / FOGAO / MICROONDAS'),
(50, 'TERRENO'),
(51, 'EQUIPAMENTO MEDICO HOSPITALAR'),
(52, 'APARELHO TELEFONICO / APARELHO DE FAX'),
(53, 'COPIADORA (XEROX) / SCANNER'),
(54, 'COFRE'),
(55, 'ESCADA'),
(56, 'EXTINTOR'),
(57, 'LAVADORAS / TANQUE ELETRICO'),
(58, 'ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL'),
(59, 'INVERSOR (NO-BREAK) / ESTABILIZADOR / CARREGADOR'),
(60, 'CONSTRUCAO'),
(61, 'CAIXA DE COLETA'),
(62, 'BANQUETA'),
(63, 'MONITOR / DATA SHOW'),
(64, 'ANDAIME - LATERAL/TRAVA/RODA'),
(65, 'FERRAMENTAS E MAQUINAS'),
(66, 'CAMAS / BELICHES'),
(67, 'TROCADOR PARA BEBE'),
(68, 'EQUIPAMENTOS DE CLIMATIZACAO'),
(69, 'SOFTWARE'),
(70, 'REFORMA'),
(80, 'INSTALACOES'),
(99, 'DIVERSOS');
SQL
                );

                $this->execute("INSERT INTO usuarios (nome, email, senha, ativo) VALUES ('Administrador', 'admin@checkplanilha.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);");
        }

        public function down()
        {
                $this->execute("DROP TABLE IF EXISTS import_erros");
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

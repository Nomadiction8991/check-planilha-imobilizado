<?php

use Phinx\Migration\AbstractMigration;

class InitialSchema extends AbstractMigration
{
    public function up()
    {
        // Criar tabelas na ordem de dependências
        $this->execute("
CREATE TABLE `comums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `cnpj` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `administracao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `setor` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->execute("
CREATE TABLE `configuracoes` (
  `posicao_data` varchar(255) NOT NULL DEFAULT 'D13',
  `pulo_linhas` varchar(255) NOT NULL DEFAULT '25',
  `mapeamento_colunas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'codigo=A;complemento=D;dependencia=P;localidade=K',
  `data_importacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $this->execute("
CREATE TABLE `dependencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `descricao` (`descricao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->execute("
CREATE TABLE `tipos_bens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->execute("
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `casado` tinyint(1) NOT NULL DEFAULT '0',
  `nome_conjuge` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf_conjuge` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_conjuge` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_conjuge_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `telefone_conjuge` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `endereco_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_logradouro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_numero` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_usuarios_rg` (`rg`),
  KEY `idx_usuarios_cpf_conjuge` (`cpf_conjuge`),
  KEY `idx_usuarios_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->execute("
CREATE TABLE `produtos` (
  `comum_id` int NOT NULL,
  `id_produto` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao_completa` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `editado_descricao_completa` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_bem_id` int NOT NULL,
  `editado_tipo_bem_id` int NOT NULL,
  `bem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `editado_bem` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `complemento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `editado_complemento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dependencia_id` int NOT NULL,
  `editado_dependencia_id` int NOT NULL,
  `novo` int NOT NULL,
  `checado` int NOT NULL,
  `editado` int NOT NULL,
  `imprimir_etiqueta` int NOT NULL,
  `imprimir_14_1` int NOT NULL,
  `condicao_14_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nota_numero` int DEFAULT NULL,
  `nota_data` date DEFAULT NULL,
  `nota_valor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota_fornecedor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `administrador_acessor_id` int DEFAULT NULL,
  `doador_conjugue_id` int DEFAULT NULL,
  `ativo` int NOT NULL,
  PRIMARY KEY (`id_produto`),
  KEY `idx_produtos_codigo` (`codigo`),
  KEY `idx_produtos_comum_id` (`comum_id`),
  KEY `idx_produtos_tipo_bem_id` (`tipo_bem_id`),
  KEY `idx_produtos_dependencia_id` (`dependencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->execute("
CREATE TABLE `import_job_processed` (
  `job_id` varchar(128) NOT NULL,
  `id_produto` int NOT NULL,
  `comum_id` int NOT NULL,
  PRIMARY KEY (`job_id`,`id_produto`),
  KEY `idx_comum` (`comum_id`,`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // Inserir dados de teste
        $this->execute("
INSERT INTO comums (codigo, cnpj, descricao, administracao, cidade, setor) VALUES
(1, '12345678000100', 'Comum Exemplo 1', 'Administração Municipal', 'Cuiabá', 1),
(2, '98765432000199', 'Comum Exemplo 2', 'Administração Estadual', 'Várzea Grande', 2);
        ");

        $this->execute("
INSERT INTO configuracoes (posicao_data, pulo_linhas, mapeamento_colunas, data_importacao) VALUES
('D13', '25', 'codigo=A;complemento=D;dependencia=P;localidade=K', '2026-02-11');
        ");

        $this->execute("
INSERT INTO dependencias (descricao) VALUES
('Dependência 1'),
('Dependência 2'),
('Dependência 3');
        ");

        $this->execute("
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
        ");

        $this->execute("
INSERT INTO usuarios (nome, email, senha, ativo) VALUES ('Administrador', 'admin@checkplanilha.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
        ");

        $this->execute("
INSERT INTO usuarios (nome, email, senha, ativo) VALUES
('Usuário Teste', 'teste@checkplanilha.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
        ");

        $this->execute("
INSERT INTO produtos (comum_id, codigo, descricao_completa, editado_descricao_completa, tipo_bem_id, editado_tipo_bem_id, bem, editado_bem, complemento, editado_complemento, dependencia_id, editado_dependencia_id, novo, checado, editado, imprimir_etiqueta, imprimir_14_1, observacao, ativo) VALUES
(1, 'PROD001', 'Produto de Teste 1', '', 1, 0, 'Bem móvel de teste', '', 'Complemento 1', '', 1, 0, 1, 1, 0, 1, 1, 'Observação teste', 1),
(2, 'PROD002', 'Produto de Teste 2', '', 2, 0, 'Bem imóvel de teste', '', 'Complemento 2', '', 2, 0, 1, 1, 0, 1, 1, 'Observação teste 2', 1);
        ");

        // import_job_processed pode ficar vazio para teste
    }

    public function down()
    {
        $this->execute("DROP TABLE IF EXISTS import_job_processed");
        $this->execute("DROP TABLE IF EXISTS produtos");
        $this->execute("DROP TABLE IF EXISTS usuarios");
        $this->execute("DROP TABLE IF EXISTS tipos_bens");
        $this->execute("DROP TABLE IF EXISTS dependencias");
        $this->execute("DROP TABLE IF EXISTS configuracoes");
        $this->execute("DROP TABLE IF EXISTS comums");
    }
}

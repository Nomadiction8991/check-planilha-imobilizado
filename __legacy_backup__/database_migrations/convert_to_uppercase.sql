-- Script SQL para Converter Dados Existentes para UPPERCASE
-- Este script converte todos os campos de texto críticos para MAIÚSCULAS
-- Use com cuidado em produção - sempre faça backup primeiro!

-- =====================================================
-- TABELA: usuarios
-- =====================================================
UPDATE usuarios SET 
    nome = UPPER(nome),
    tipo = UPPER(tipo),
    assinatura = UPPER(COALESCE(assinatura, '')),
    nome_conjuge = UPPER(COALESCE(nome_conjuge, '')),
    assinatura_conjuge = UPPER(COALESCE(assinatura_conjuge, '')),
    endereco_logradouro = UPPER(COALESCE(endereco_logradouro, '')),
    endereco_numero = UPPER(COALESCE(endereco_numero, '')),
    endereco_complemento = UPPER(COALESCE(endereco_complemento, '')),
    endereco_bairro = UPPER(COALESCE(endereco_bairro, '')),
    endereco_cidade = UPPER(COALESCE(endereco_cidade, '')),
    endereco_estado = UPPER(COALESCE(endereco_estado, ''))
WHERE ativo = 1;

-- =====================================================
-- TABELA: comums
-- =====================================================
UPDATE comums SET
    descricao = UPPER(descricao),
    administracao = UPPER(COALESCE(administracao, '')),
    cidade = UPPER(COALESCE(cidade, ''))
WHERE id > 0;

-- =====================================================
-- TABELA: dependencias
-- =====================================================
UPDATE dependencias SET
    descricao = UPPER(descricao)
WHERE id > 0;

-- =====================================================
-- TABELA: produtos
-- =====================================================
UPDATE produtos SET
    bem = UPPER(COALESCE(bem, '')),
    complemento = UPPER(COALESCE(complemento, '')),
    descricao_completa = UPPER(COALESCE(descricao_completa, '')),
    observacao = UPPER(COALESCE(observacao, ''))
WHERE 1=1;

-- =====================================================
-- VALIDAÇÃO: Conferir dados convertidos
-- =====================================================
-- Descomente as queries abaixo para validar:

-- SELECT COUNT(*) as total_usuarios, 
--        SUM(CASE WHEN nome = UPPER(nome) THEN 1 ELSE 0 END) as uppercase_nome
-- FROM usuarios;

-- SELECT COUNT(*) as total_comuns,
--        SUM(CASE WHEN descricao = UPPER(descricao) THEN 1 ELSE 0 END) as uppercase_descricao
-- FROM comums;

-- SELECT COUNT(*) as total_dependencias,
--        SUM(CASE WHEN descricao = UPPER(descricao) THEN 1 ELSE 0 END) as uppercase_descricao
-- FROM dependencias;

-- SELECT COUNT(*) as total_produtos,
--        SUM(CASE WHEN bem = UPPER(bem) THEN 1 ELSE 0 END) as uppercase_bem
-- FROM produtos;

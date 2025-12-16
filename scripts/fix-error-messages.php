<?php
/**
 * Script para corrigir mensagens de erro no UsuarioUpdateController
 * Converte todas para UPPERCASE e UTF-8 correto
 */

$file = dirname(__DIR__) . '/app/controllers/update/UsuarioUpdateController.php';
$content = file_get_contents($file);

// Mapa de substituições de mensagens de erro
$replacements = [
    // Original (com encoding corrompido) => Novo (UPPERCASE + UTF-8)
    "throw new Exception('Usuário não encontrado.');" => "throw new Exception('USUÁRIO NÃO ENCONTRADO.');",
    "throw new Exception('O nome é obrigatório.');" => "throw new Exception('O NOME É OBRIGATÓRIO.');",
    "throw new Exception('O email é obrigatório.');" => "throw new Exception('O EMAIL É OBRIGATÓRIO.');",
    "throw new Exception('Email inválido.');" => "throw new Exception('EMAIL INVÁLIDO.');",
    "throw new Exception('CPF inválido. Deve conter 11 dígitos.');" => "throw new Exception('CPF INVÁLIDO. DEVE CONTER 11 DÍGITOS.');",
    "throw new Exception('Este CPF já está cadastrado por outro usuário.');" => "throw new Exception('ESTE CPF JÁ ESTÁ CADASTRADO POR OUTRO USUÁRIO.');",
    "throw new Exception('Telefone inválido. Deve conter 10 ou 11 dígitos.');" => "throw new Exception('TELEFONE INVÁLIDO. DEVE CONTER 10 OU 11 DÍGITOS.');",
    "throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');" => "throw new Exception('O RG É OBRIGATÓRIO E DEVE TER AO MENOS 2 DÍGITOS.');",
    "throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');" => "throw new Exception('TODOS OS CAMPOS DE ENDEREÇO (CEP, LOGRADOURO, NÚMERO, BAIRRO, CIDADE E ESTADO) SÃO OBRIGATÓRIOS.');",
    "throw new Exception('O nome do cônjuge é obrigatório.');" => "throw new Exception('O NOME DO CÔNJUGE É OBRIGATÓRIO.');",
    "throw new Exception('CPF do cônjuge inválido.');" => "throw new Exception('CPF DO CÔNJUGE INVÁLIDO.');",
    "throw new Exception('Telefone do cônjuge inválido.');" => "throw new Exception('TELEFONE DO CÔNJUGE INVÁLIDO.');",
    "throw new Exception('O RG do cônjuge deve ter ao menos 2 dígitos.');" => "throw new Exception('O RG DO CÔNJUGE DEVE TER AO MENOS 2 DÍGITOS.');",
    "throw new Exception('Este email já está cadastrado por outro usuário.');" => "throw new Exception('ESTE EMAIL JÁ ESTÁ CADASTRADO POR OUTRO USUÁRIO.');",
    "throw new Exception('A senha deve ter no mínimo 6 caracteres.');" => "throw new Exception('A SENHA DEVE TER NO MÍNIMO 6 CARACTERES.');",
    "throw new Exception('As senhas não conferem.');" => "throw new Exception('AS SENHAS NÃO CONFEREM.');",
];

foreach ($replacements as $old => $new) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        echo "✅ Corrigido: $old\n";
    }
}

file_put_contents($file, $content);
echo "\n✅ Arquivo atualizado com sucesso!\n";

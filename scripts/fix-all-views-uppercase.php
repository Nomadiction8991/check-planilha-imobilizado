<?php
/**
 * SCRIPT DE FIX GLOBAL: Converte todas as views para UPPERCASE com UTF-8 correto
 * Processa recursivamente em app/views/ e aplica as conversões necessárias
 */

$basePath = __DIR__ . '/../app/views';
$replacements = [
    // Autenticação
    'Autenticação' => 'AUTENTICAÇÃO',
    
    // Dados e Seções
    'Dados Básicos' => 'DADOS BÁSICOS',
    'Dados básicos' => 'DADOS BÁSICOS',
    'Dados Cadastrais' => 'DADOS CADASTRAIS',
    'Dados Pessoais' => 'DADOS PESSOAIS',
    'Dados Profissionais' => 'DADOS PROFISSIONAIS',
    
    // Cônjuge
    'Cônjuge' => 'CÔNJUGE',
    'cônjuge' => 'CÔNJUGE',
    'do Cônjuge' => 'DO CÔNJUGE',
    'do cônjuge' => 'DO CÔNJUGE',
    
    // Campos
    'Nome Completo' => 'NOME COMPLETO',
    'Nome completo' => 'NOME COMPLETO',
    'CPF' => 'CPF',
    'RG' => 'RG',
    'Telefone' => 'TELEFONE',
    'Email' => 'EMAIL',
    'Endereço' => 'ENDEREÇO',
    'Logradouro' => 'LOGRADOURO',
    'Número' => 'NÚMERO',
    'Bairro' => 'BAIRRO',
    'Cidade' => 'CIDADE',
    'Estado' => 'ESTADO',
    'CEP' => 'CEP',
    'Complemento' => 'COMPLEMENTO',
    
    // Ações/Botões
    'Salvar' => 'SALVAR',
    'Cancelar' => 'CANCELAR',
    'Fechar' => 'FECHAR',
    'Editar' => 'EDITAR',
    'Visualizar' => 'VISUALIZAR',
    'Deletar' => 'DELETAR',
    'Atualizar' => 'ATUALIZAR',
    'Limpar' => 'LIMPAR',
    'Buscar' => 'BUSCAR',
    
    // Bibliotecas
    'jQuery' => 'JQUERY',
    'SignaturePad' => 'SIGNATUREPAD',
    'InputMask' => 'INPUTMASK',
    
    // Mensagens
    'Campo obrigatório' => 'CAMPO OBRIGATÓRIO',
    'Campo oculto' => 'CAMPO OCULTO',
    'Botão' => 'BOTÃO',
    
    // Seções de formulário
    'Assinatura Digital' => 'ASSINATURA DIGITAL',
    'Estado Civil' => 'ESTADO CIVIL',
    'Endereço' => 'ENDEREÇO',
];

// Função recursiva para processar arquivos PHP
function processFiles($dir, $replacements) {
    $files = scandir($dir);
    $count = 0;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = $dir . '/' . $file;
        
        if (is_dir($filepath)) {
            // Recursivamente processar subdiretórios
            $count += processFiles($filepath, $replacements);
        } elseif (pathinfo($filepath, PATHINFO_EXTENSION) === 'php') {
            // Pular certos diretórios
            if (strpos($filepath, 'shared') !== false || strpos($filepath, 'layouts') !== false) {
                continue;
            }
            
            if (!is_file($filepath)) continue;
            
            $content = file_get_contents($filepath);
            $original = $content;
            
            // Aplicar replacements
            foreach ($replacements as $from => $to) {
                $content = str_replace($from, $to, $content);
            }
            
            // Se teve mudanças, salvar
            if ($content !== $original) {
                file_put_contents($filepath, $content);
                $count++;
                echo "✅ " . str_replace(dirname(__DIR__), '', $filepath) . "\n";
            }
        }
    }
    
    return $count;
}

$totalCount = processFiles($basePath, $replacements);
echo "\n🎉 Total de arquivos processados: $totalCount\n";

// Executar
$totalCount = processFiles($basePath, $replacements);
?>

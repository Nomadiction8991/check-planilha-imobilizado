<?php
// Script para remover validações obrigatórias do cônjuge

// Update Controller
$updateFile = dirname(__DIR__) . '/app/controllers/update/UsuarioUpdateController.php';
$updateContent = file_get_contents($updateFile);

// Remove mandatory spouse validation from UpdateController
$pattern1 = '/\/\/ Se casado, validar dados completos do c.njuge\s+if \(\$casado\) \{\s+if \(empty\(\$nome_conjuge\)\) \{\s+throw new Exception\(\'O nome do c.njuge .* obrigat.rio\.\'\);\s+\}\s+\$cpf_conjuge_num = preg_replace\(\'\/\\\\D\/\',\'\', \$cpf_conjuge\);\s+if \(strlen\(\$cpf_conjuge_num\) !== 11\) \{\s+throw new Exception\(\'CPF do c.njuge inv.lido\.\'\);\s+\}\s+\$tel_conj_num = preg_replace\(\'\/\\\\D\/\',\'\', \$telefone_conjuge\);\s+if \(strlen\(\$tel_conj_num\) < 10 \|\| strlen\(\$tel_conj_num\) > 11\) \{\s+throw new Exception\(\'Telefone do c.njuge inv.lido\.\'\);\s+\}\s+if \(\$rg_conjuge_igual_cpf\) \{ \$rg_conjuge = \$cpf_conjuge; \} else if \(!empty\(\$rg_conjuge\)\) \{ \$rg_conjuge = \$formatarRg\(\$rg_conjuge\); \}\s+if \(!empty\(\$rg_conjuge\)\) \{\s+\$rnums = preg_replace\(\'\/\\\\D\/\',\'\', \$rg_conjuge\);\s+if \(strlen\(\$rnums\) < 2\) \{ throw new Exception\(\'O RG do c.njuge deve ter ao menos 2 d.gitos\.\'\); \}\s+\}\s+\} else \{/';

$replacement1 = '// Se casado, apenas formatar RG se fornecido (dados do cônjuge são opcionais)
        if ($casado) {
            if ($rg_conjuge_igual_cpf && !empty($cpf_conjuge)) { 
                $rg_conjuge = $cpf_conjuge; 
            } else if (!empty($rg_conjuge)) { 
                $rg_conjuge = $formatarRg($rg_conjuge); 
            }
        } else {';

$updateContent = preg_replace($pattern1, $replacement1, $updateContent);
file_put_contents($updateFile, $updateContent);
echo "✅ UsuarioUpdateController fixed\n";

// Create Controller
$createFile = dirname(__DIR__) . '/app/controllers/create/UsuarioCreateController.php';
$createContent = file_get_contents($createFile);

// Search and replace in create controller - the block is slightly different
$startPos = strpos($createContent, "// Se casado, validar dados completos do cônjuge");
if ($startPos === false) {
    // Try with corrupted encoding
    $startPos = strpos($createContent, "// Se casado, validar dados completos do c");
}

if ($startPos !== false) {
    // Find the else block
    $elsePos = strpos($createContent, "} else {", $startPos);
    $segment = substr($createContent, $startPos, $elsePos - $startPos + 8);
    
    $newSegment = "// Se casado, apenas formatar RG se fornecido (dados do cônjuge são opcionais)
        if (\$casado) {
            if (\$rg_conjuge_igual_cpf && !empty(\$cpf_conjuge)) {
                \$rg_conjuge = \$cpf_conjuge; // mantém máscara de CPF no RG do cônjuge
            } else if (!empty(\$rg_conjuge)) {
                \$rg_conjuge = \$formatarRg(\$rg_conjuge);
            }
        } else {";
    
    $createContent = str_replace($segment, $newSegment, $createContent);
    file_put_contents($createFile, $createContent);
    echo "✅ UsuarioCreateController fixed\n";
} else {
    echo "❌ Could not find spouse validation block in CreateController\n";
}

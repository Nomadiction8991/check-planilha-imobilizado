#!/usr/bin/env php
<?php
/**
 * Script de correção em lote: re-parseia BEM/COMPLEMENTO de todos os produtos.
 * 
 * O problema: na importação original, o campo "Nome" do CSV ficava inteiro no campo `bem`,
 * sem separar BEM e COMPLEMENTO corretamente.
 * 
 * Exemplo: "4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
 *   ANTES:  bem = "CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO", complemento = ""
 *   DEPOIS: bem = "CADEIRA", complemento = "TRIBUNA ALMOFADADA PULPITO"
 * 
 * Uso: docker exec <container> php /var/www/html/scripts/fix_bem_complemento.php [--dry-run]
 */

require __DIR__ . '/../config/bootstrap.php';

use App\Core\ConnectionManager;
use App\Services\CsvParserService;

$dryRun = in_array('--dry-run', $argv ?? []);
$pdo = ConnectionManager::getConnection();
$parser = new CsvParserService($pdo);

echo "=== Correção BEM/COMPLEMENTO ===\n";
echo $dryRun ? "[DRY-RUN] Nenhuma alteração será gravada.\n" : "[EXECUÇÃO REAL] Alterações serão gravadas no banco.\n";
echo "\n";

// Buscar todos os produtos com nome_planilha (importados via CSV)
$stmt = $pdo->query('
    SELECT p.id_produto, p.bem, p.editado_bem, p.complemento, p.editado_complemento, 
           p.nome_planilha, tb.codigo as tb_cod, tb.descricao as tb_desc
    FROM produtos p
    LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
    WHERE p.nome_planilha IS NOT NULL AND p.nome_planilha != ""
    ORDER BY p.id_produto ASC
');

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($produtos);
echo "Produtos a processar: {$total}\n\n";

if ($total === 0) {
    echo "Nenhum produto para corrigir.\n";
    exit(0);
}

// Usar reflection para acessar o método privado parsearNome
$refMethod = new ReflectionMethod(CsvParserService::class, 'parsearNome');
$refMethod->setAccessible(true);

$updateStmt = $pdo->prepare('
    UPDATE produtos 
    SET bem = :bem, 
        editado_bem = :editado_bem, 
        complemento = :complemento,
        editado_complemento = :editado_complemento
    WHERE id_produto = :id
');

$atualizados = 0;
$semMudanca = 0;
$erros = 0;

foreach ($produtos as $i => $prod) {
    $idProduto = $prod['id_produto'];
    $nomePlanilha = $prod['nome_planilha'];

    try {
        // Re-parsear o nome da planilha usando a lógica corrigida
        $parsed = $refMethod->invoke($parser, $nomePlanilha);

        $novoBem = $parsed['bem'] ?? $prod['bem'];
        $novoComplemento = $parsed['complemento'] ?? '';

        // Verificar se houve mudança
        $mudou = ($novoBem !== $prod['bem']) || ($novoComplemento !== $prod['complemento']);

        if ($mudou) {
            // Determinar editado_bem: se era cópia do bem antigo, atualizar também
            $novoEditadoBem = $prod['editado_bem'];
            if ($prod['editado_bem'] === $prod['bem'] || empty($prod['editado_bem'])) {
                $novoEditadoBem = $novoBem;
            }

            // Determinar editado_complemento: se era cópia do complemento antigo, atualizar também
            $novoEditadoCompl = $prod['editado_complemento'];
            if ($prod['editado_complemento'] === $prod['complemento'] || empty($prod['editado_complemento'])) {
                $novoEditadoCompl = $novoComplemento;
            }

            if (!$dryRun) {
                $updateStmt->execute([
                    ':bem'                  => $novoBem,
                    ':editado_bem'          => $novoEditadoBem,
                    ':complemento'          => $novoComplemento,
                    ':editado_complemento'  => $novoEditadoCompl,
                    ':id'                   => $idProduto,
                ]);
            }

            $atualizados++;

            // Mostrar os primeiros 20 para acompanhamento
            if ($atualizados <= 20) {
                echo "  #{$idProduto}: bem=[{$novoBem}] compl=[{$novoComplemento}]\n";
                echo "         ANTES: bem=[{$prod['bem']}] compl=[{$prod['complemento']}]\n";
            }
        } else {
            $semMudanca++;
        }
    } catch (Exception $e) {
        $erros++;
        echo "  ERRO ID={$idProduto}: {$e->getMessage()}\n";
    }

    // Progresso a cada 500
    if (($i + 1) % 500 === 0) {
        echo "  ... processados " . ($i + 1) . "/{$total}\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "Total processados: {$total}\n";
echo "Atualizados: {$atualizados}\n";
echo "Sem mudança: {$semMudanca}\n";
echo "Erros: {$erros}\n";

if ($dryRun) {
    echo "\n[DRY-RUN] Nenhuma alteração foi gravada. Execute sem --dry-run para aplicar.\n";
} else {
    echo "\nConcluído!\n";
}

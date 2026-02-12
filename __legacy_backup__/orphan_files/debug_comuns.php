<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Proteção simples: exija ?debug=1 para evitar exposição acidental
if (!isset($_GET['debug']) || $_GET['debug'] != '1') {
    http_response_code(403);
    echo "Forbidden. Add ?debug=1 to the URL to view debug info.";
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Debug Comuns</title>
    
    <style>
body {
            font-family: system-ui, Arial, Helvetica, sans-serif;
            padding: 16px;
        }


        body pre {
            background: #f6f8fa;
            padding: 12px;
            border-radius: 6px;
            overflow: auto
        }


body {
            font-family: system-ui, Arial, Helvetica, sans-serif;
            padding: 16px;
        }


        body pre {
            background: #f6f8fa;
            padding: 12px;
            border-radius: 6px;
            overflow: auto
        }
    </style>
</head>

<body>
    <h2>Debugagem - Comuns</h2>
    <?php
    try {
        echo '<p><strong>Detectando nome da tabela (comums | comuns)</strong></p>';
        $table = detectar_tabela_comuns($conexao);
        echo '<p><strong>Tabela detectada:</strong> ' . htmlspecialchars($table, ENT_QUOTES, 'UTF-8') . '</p>';

        // Contagens diretas
        $c1 = @($conexao->query('SELECT COUNT(*) FROM `comums`')->fetchColumn());
        $c2 = @($conexao->query('SELECT COUNT(*) FROM `comuns`')->fetchColumn());
        echo '<p>Contagens (se existir): comums=' . (($c1 === false) ? 'NA' : (int)$c1) . ' | comuns=' . (($c2 === false) ? 'NA' : (int)$c2) . '</p>';

        // Total usando helper
        $total = contar_comuns($conexao, '');
        echo '<p><strong>Total (contar_comuns):</strong> ' . (int)$total . '</p>';

        // Mostrar primeiras linhas
        $limit = 20;
        $stmt = $conexao->prepare("SELECT id, codigo, descricao, cnpj, administracao, cidade FROM `{$table}` ORDER BY id DESC LIMIT :l");
        $stmt->bindValue(':l', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<h3>Exemplo de linhas (' . count($rows) . ')</h3>';
        if (empty($rows)) {
            echo '<p><em>Nenhuma linha encontrada na tabela detectada.</em></p>';
        } else {
            echo '<pre>' . htmlspecialchars(print_r($rows, true), ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        // Mostrar último erro SQL (se existir em log PHP)
        echo '<h3>Últimos logs de erro (se disponíveis)</h3>';
        $logPaths = [];
        // busca comum por possíveis locais de logs
        $candidates = [__DIR__ . '/../../storage/logs', '/var/log/apache2', '/var/log/httpd', '/var/log/php-fpm'];
        foreach ($candidates as $dir) {
            if (is_dir($dir)) {
                $files = glob(rtrim($dir, '/') . '/*');
                foreach ($files as $f) {
                    if (is_file($f) && filesize($f) > 0) {
                        $logPaths[] = $f;
                    }
                }
            }
        }
        if (empty($logPaths)) {
            echo '<p>Nenhum arquivo de log detectado nessas pastas padrão.</p>';
        } else {
            echo '<p>Arquivos detectados (mostrando últimos 200 linhas do primeiro disponível):</p>';
            $first = $logPaths[0];
            echo '<p><strong>' . htmlspecialchars($first) . '</strong></p>';
            $content = shell_exec('tail -n 200 ' . escapeshellarg($first) . ' 2>&1');
            echo '<pre>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    } catch (Exception $e) {
        echo '<h3>Erro</h3>';
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    ?>
</body>

</html>

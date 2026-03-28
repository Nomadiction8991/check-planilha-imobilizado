<?php

declare(strict_types=1);

namespace App\Services\Notificacoes;

use App\Core\Database;
use App\Views\Notificacoes\VersaoClientes as VersaoView;
use App\Helpers\NotificadorTelegram;
use App\Core\Configuracoes;
use PDOException;

class VerificadorVersaoClientes
{
    public static function obterDesatualizados(): array
    {
        try {
            $pdo = Database::getConnection();
        } catch (PDOException $ex) {
            return [];
        }

        $sql = 'SELECT versao, nome_fantasia FROM clientes ' .
            'WHERE ativo = 1 AND versao IS NOT NULL AND TRIM(versao) != ""';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $versaoAtual = trim((string) Configuracoes::pegar('versao'));

        $grupos = [];

        foreach ($rows as $row) {
            $versaoCliente = trim((string) ($row['versao'] ?? ''));
            if ($versaoCliente === '') {
                continue;
            }

            if ($versaoCliente === $versaoAtual) {
                continue;
            }

            $nome = strtoupper(trim($row['nome_fantasia'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $versaoKey = $versaoCliente;

            if (!isset($grupos[$versaoKey])) {
                $grupos[$versaoKey] = [];
            }

            $grupos[$versaoKey][] = $nome;
        }

        foreach ($grupos as $vers => $nomes) {
            $nomes = array_values(array_unique($nomes));
            sort($nomes);
            $grupos[$vers] = $nomes;
        }

        uksort($grupos, function ($a, $b) {
            return version_compare(trim((string) $b), trim((string) $a));
        });

        return $grupos;
    }

    public static function construirMensagem(array $grupos): ?string
    {
        if (empty($grupos)) {
            return null;
        }

        return VersaoView::render($grupos);
    }

    public static function enviarSeExistirDesatualizados(): bool
    {
        $grupos = self::obterDesatualizados();
        if (empty($grupos)) {
            return false;
        }

        $mensagem = self::construirMensagem($grupos);
        if ($mensagem === null) {
            return false;
        }

        $enviado = NotificadorTelegram::enviarMensagem($mensagem, 'Markdown');

        if ($enviado) {
            Configuracoes::atualizar('ultimo_envio_versao_clientes', date('Y/m/d'));
        }

        return $enviado;
    }
}

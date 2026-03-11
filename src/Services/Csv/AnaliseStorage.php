<?php

declare(strict_types=1);

namespace App\Services\Csv;

use Exception;

/**
 * AnaliseStorage — Persiste e recupera o resultado de análise de CSV em disco (JSON).
 *
 * Grava arquivos em storage/tmp/analise_{id}.json usando JSON compacto
 * (sem PRETTY_PRINT) para reduzir tamanho em disco.
 */
class AnaliseStorage
{
    private string $diretorio;

    public function __construct(string $diretorio = '')
    {
        $this->diretorio = $diretorio !== ''
            ? $diretorio
            : dirname(__DIR__, 3) . '/storage/tmp';
    }

    /**
     * Salva resultado da análise como JSON no diretório de storage.
     *
     * @return string Caminho completo do arquivo gravado
     * @throws Exception Se não for possível serializar ou gravar
     */
    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        if (!is_dir($this->diretorio)) {
            mkdir($this->diretorio, 0755, true);
        }

        $caminho = $this->caminhoArquivo($importacaoId);

        $json = json_encode($analise, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new Exception('Erro ao serializar análise: ' . json_last_error_msg());
        }

        file_put_contents($caminho, $json);

        return $caminho;
    }

    /**
     * Carrega análise salva previamente.
     * Retorna null se o arquivo não existir ou o conteúdo for inválido.
     */
    public function carregarAnalise(int $importacaoId): ?array
    {
        $caminho = $this->caminhoArquivo($importacaoId);

        if (!file_exists($caminho)) {
            return null;
        }

        $json  = file_get_contents($caminho);
        $dados = json_decode((string) $json, true);

        return is_array($dados) ? $dados : null;
    }

    /**
     * Remove o arquivo de análise (limpeza pós-importação).
     */
    public function limparAnalise(int $importacaoId): void
    {
        $caminho = $this->caminhoArquivo($importacaoId);

        if (file_exists($caminho)) {
            unlink($caminho);
        }
    }

    // ─── Auxiliar ───

    private function caminhoArquivo(int $importacaoId): string
    {
        return $this->diretorio . '/analise_' . $importacaoId . '.json';
    }
}

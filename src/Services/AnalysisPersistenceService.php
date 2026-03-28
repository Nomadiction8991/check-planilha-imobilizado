<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * AnalysisPersistenceService — Gerencia persistência de análises de CSV.
 * Responsabilidade única: salvar, carregar e limpar dados de análise.
 *
 * Separa a persistência da lógica de análise (CsvParserService).
 */
class AnalysisPersistenceService
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? (__DIR__ . '/../../storage/tmp');
        $this->ensureStorageDir();
    }

    /**
     * Salva resultado da análise como JSON.
     */
    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        $caminho = $this->getAnalisePath($importacaoId);

        $json = json_encode($analise, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new Exception('Erro ao serializar análise: ' . json_last_error_msg());
        }

        if (@file_put_contents($caminho, $json) === false) {
            throw new Exception('Falha ao escrever arquivo de análise: ' . $caminho);
        }

        return $caminho;
    }

    /**
     * Carrega análise salva previamente.
     */
    public function carregarAnalise(int $importacaoId): ?array
    {
        $caminho = $this->getAnalisePath($importacaoId);

        if (!file_exists($caminho)) {
            return null;
        }

        $json = @file_get_contents($caminho);
        if ($json === false) {
            return null;
        }

        $dados = json_decode($json, true);
        return is_array($dados) ? $dados : null;
    }

    /**
     * Remove arquivo de análise.
     */
    public function limparAnalise(int $importacaoId): bool
    {
        $caminho = $this->getAnalisePath($importacaoId);

        if (!file_exists($caminho)) {
            return true;
        }

        return @unlink($caminho);
    }

    /**
     * Verifica se análise existe.
     */
    public function existeAnalise(int $importacaoId): bool
    {
        return file_exists($this->getAnalisePath($importacaoId));
    }

    /**
     * Retorna caminho do arquivo de análise.
     */
    private function getAnalisePath(int $importacaoId): string
    {
        return $this->storageDir . '/analise_' . $importacaoId . '.json';
    }

    /**
     * Garante que diretório de armazenamento existe.
     */
    private function ensureStorageDir(): void
    {
        if (!is_dir($this->storageDir)) {
            if (@mkdir($this->storageDir, 0777, true) === false) {
                throw new Exception('Falha ao criar diretório de storage: ' . $this->storageDir);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * ProcessingResult — Encapsula resultado de processamento de importação.
 * Substitui arrays associativos complexos por uma abstração tipada e mais segura.
 */
class ProcessingResult
{
    private int $sucesso = 0;
    private int $erro = 0;
    private int $pulados = 0;
    private int $excluidos = 0;
    private array $erros = [];

    public function __construct(int $sucesso = 0, int $erro = 0, int $pulados = 0, int $excluidos = 0, array $erros = [])
    {
        $this->sucesso = max(0, $sucesso);
        $this->erro = max(0, $erro);
        $this->pulados = max(0, $pulados);
        $this->excluidos = max(0, $excluidos);
        $this->erros = $erros;
    }

    public static function criar(): self
    {
        return new self();
    }

    public function getSucesso(): int
    {
        return $this->sucesso;
    }

    public function setSucesso(int $valor): self
    {
        $this->sucesso = max(0, $valor);
        return $this;
    }

    public function adicionarSucesso(int $quantidade = 1): self
    {
        $this->sucesso += max(0, $quantidade);
        return $this;
    }

    public function getErro(): int
    {
        return $this->erro;
    }

    public function setErro(int $valor): self
    {
        $this->erro = max(0, $valor);
        return $this;
    }

    public function adicionarErro(int $quantidade = 1): self
    {
        $this->erro += max(0, $quantidade);
        return $this;
    }

    public function getPulados(): int
    {
        return $this->pulados;
    }

    public function adicionarPulados(int $quantidade = 1): self
    {
        $this->pulados += max(0, $quantidade);
        return $this;
    }

    public function getExcluidos(): int
    {
        return $this->excluidos;
    }

    public function adicionarExcluidos(int $quantidade = 1): self
    {
        $this->excluidos += max(0, $quantidade);
        return $this;
    }

    public function getErros(): array
    {
        return $this->erros;
    }

    public function adicionarErroMsg(int $linha, string $mensagem): self
    {
        $this->erros[] = [
            'linha' => $linha,
            'mensagem' => $mensagem,
        ];
        return $this;
    }

    public function mesclar(ProcessingResult $outro): self
    {
        $this->sucesso += $outro->sucesso;
        $this->erro += $outro->erro;
        $this->excluidos += $outro->excluidos;
        $this->erros = array_merge($this->erros, $outro->erros);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'sucesso' => $this->sucesso,
            'erro' => $this->erro,
            'pulados' => $this->pulados,
            'excluidos' => $this->excluidos,
            'erros' => $this->erros,
        ];
    }

    public function total(): int
    {
        return $this->sucesso + $this->erro + $this->pulados + $this->excluidos;
    }

    public function temErros(): bool
    {
        return $this->erro > 0 || !empty($this->erros);
    }
}

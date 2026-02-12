<?php

namespace App\Helpers;

/**
 * Helper com utilitários gerais para views
 * 
 * Funções auxiliares para formatação, escape, URLs, etc.
 */
class ViewHelper
{
    /**
     * Escapa string para HTML (previne XSS)
     */
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Converte para uppercase (UTF-8 safe)
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Formata data do banco (Y-m-d H:i:s) para exibição (d/m/Y H:i)
     */
    public static function formatarData(string $data, string $formato = 'd/m/Y H:i'): string
    {
        try {
            $dt = new \DateTime($data);
            return $dt->format($formato);
        } catch (\Exception $e) {
            return $data;
        }
    }

    /**
     * Formata CPF (11111111111 → 111.111.111-11)
     */
    public static function formatarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' .
            substr($cpf, 3, 3) . '.' .
            substr($cpf, 6, 3) . '-' .
            substr($cpf, 9, 2);
    }

    /**
     * Formata RG (11111111 → 11.111.111)
     */
    public static function formatarRg(string $rg): string
    {
        $rg = preg_replace('/\D/', '', $rg);

        if (strlen($rg) < 7) {
            return $rg;
        }

        // 9 dígitos: XX.XXX.XXX-X
        if (strlen($rg) === 9) {
            return substr($rg, 0, 2) . '.' .
                substr($rg, 2, 3) . '.' .
                substr($rg, 5, 3) . '-' .
                substr($rg, 8, 1);
        }

        // 8 dígitos: XX.XXX.XXX
        return substr($rg, 0, 2) . '.' .
            substr($rg, 2, 3) . '.' .
            substr($rg, 5, 3);
    }

    /**
     * Formata CNPJ (11111111111111 → 11.111.111/1111-11)
     */
    public static function formatarCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2) . '.' .
            substr($cnpj, 2, 3) . '.' .
            substr($cnpj, 5, 3) . '/' .
            substr($cnpj, 8, 4) . '-' .
            substr($cnpj, 12, 2);
    }

    /**
     * Retorna badge de status (ativo/inativo)
     */
    public static function badgeStatus(bool $ativo): string
    {
        if ($ativo) {
            return '<span class="badge bg-success">ATIVO</span>';
        }

        return '<span class="badge bg-secondary">INATIVO</span>';
    }

    /**
     * Retorna classe CSS para linha de tabela baseada em status
     */
    public static function classeLinhaStatus(bool $ativo): string
    {
        return $ativo ? '' : 'table-secondary';
    }

    /**
     * Trunca texto com reticências
     */
    public static function truncar(string $texto, int $limite = 50): string
    {
        if (mb_strlen($texto, 'UTF-8') <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite, 'UTF-8') . '...';
    }

    /**
     * Gera URL preservando query string atual
     */
    public static function urlComQuery(string $base, array $novosParams = []): string
    {
        $queryAtual = [];

        // Preservar parâmetros atuais
        foreach ($_GET as $key => $value) {
            if (!isset($novosParams[$key])) {
                $queryAtual[$key] = $value;
            }
        }

        // Mesclar com novos parâmetros
        $params = array_merge($queryAtual, $novosParams);

        // Remover vazios
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        if (empty($params)) {
            return $base;
        }

        $separator = strpos($base, '?') !== false ? '&' : '?';
        return $base . $separator . http_build_query($params);
    }

    /**
     * Verifica se array associativo está vazio ou todos valores são vazios
     */
    public static function dadosVazios(array $dados): bool
    {
        $valores = array_filter($dados, function ($value) {
            return $value !== '' && $value !== null;
        });

        return empty($valores);
    }

    /**
     * Retorna atributo 'checked' se condição for verdadeira
     */
    public static function checked(bool $condicao): string
    {
        return $condicao ? 'checked' : '';
    }

    /**
     * Retorna atributo 'selected' se valores coincidirem
     */
    public static function selected($valor1, $valor2): string
    {
        return $valor1 == $valor2 ? 'selected' : '';
    }

    /**
     * Retorna atributo 'disabled' se condição for verdadeira
     */
    public static function disabled(bool $condicao): string
    {
        return $condicao ? 'disabled' : '';
    }
}

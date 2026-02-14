<?php

declare(strict_types=1);

namespace App\Helpers;


class ViewHelper
{
    
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    
    public static function formatarData(string $data, string $formato = 'd/m/Y H:i'): string
    {
        try {
            $dt = new \DateTime($data);
            return $dt->format($formato);
        } catch (\Exception $e) {
            return $data;
        }
    }

    
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

    
    public static function formatarRg(string $rg): string
    {
        $rg = preg_replace('/\D/', '', $rg);

        if (strlen($rg) < 7) {
            return $rg;
        }

        
        if (strlen($rg) === 9) {
            return substr($rg, 0, 2) . '.' .
                substr($rg, 2, 3) . '.' .
                substr($rg, 5, 3) . '-' .
                substr($rg, 8, 1);
        }

        
        return substr($rg, 0, 2) . '.' .
            substr($rg, 2, 3) . '.' .
            substr($rg, 5, 3);
    }

    
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

    
    public static function badgeStatus(bool $ativo): string
    {
        if ($ativo) {
            return '<span class="badge bg-success">ATIVO</span>';
        }

        return '<span class="badge bg-secondary">INATIVO</span>';
    }

    
    public static function classeLinhaStatus(bool $ativo): string
    {
        return $ativo ? '' : 'table-secondary';
    }

    
    public static function truncar(string $texto, int $limite = 50): string
    {
        if (mb_strlen($texto, 'UTF-8') <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite, 'UTF-8') . '...';
    }

    
    public static function urlComQuery(string $base, array $novosParams = []): string
    {
        $queryAtual = [];

        
        foreach ($_GET as $key => $value) {
            if (!isset($novosParams[$key])) {
                $queryAtual[$key] = $value;
            }
        }

        
        $params = array_merge($queryAtual, $novosParams);

        
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        if (empty($params)) {
            return $base;
        }

        $separator = strpos($base, '?') !== false ? '&' : '?';
        return $base . $separator . http_build_query($params);
    }

    
    public static function dadosVazios(array $dados): bool
    {
        $valores = array_filter($dados, function ($value) {
            return $value !== '' && $value !== null;
        });

        return empty($valores);
    }

    
    public static function checked(bool $condicao): string
    {
        return $condicao ? 'checked' : '';
    }

    
    public static function selected($valor1, $valor2): string
    {
        return $valor1 == $valor2 ? 'selected' : '';
    }

    
    public static function disabled(bool $condicao): string
    {
        return $condicao ? 'disabled' : '';
    }
}

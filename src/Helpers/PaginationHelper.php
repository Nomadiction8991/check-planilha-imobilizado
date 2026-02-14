<?php

declare(strict_types=1);

namespace App\Helpers;


class PaginationHelper
{
    
    public static function render(
        int $paginaAtual,
        int $totalPaginas,
        string $baseUrl,
        array $queryParams = [],
        int $maxLinks = 5
    ): string {
        if ($totalPaginas <= 1) {
            return '';
        }

        $html = '<nav aria-label="Paginação">';
        $html .= '<ul class="pagination justify-content-center">';

        
        if ($paginaAtual > 1) {
            $prevUrl = self::buildUrl($baseUrl, array_merge($queryParams, ['pagina' => $paginaAtual - 1]));
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$prevUrl}\">ANTERIOR</a></li>";
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">ANTERIOR</span></li>';
        }

        
        $inicio = max(1, $paginaAtual - floor($maxLinks / 2));
        $fim = min($totalPaginas, $inicio + $maxLinks - 1);

        
        if ($fim - $inicio < $maxLinks - 1) {
            $inicio = max(1, $fim - $maxLinks + 1);
        }

        
        if ($inicio > 1) {
            $firstUrl = self::buildUrl($baseUrl, array_merge($queryParams, ['pagina' => 1]));
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$firstUrl}\">1</a></li>";

            if ($inicio > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        
        for ($i = $inicio; $i <= $fim; $i++) {
            if ($i == $paginaAtual) {
                $html .= "<li class=\"page-item active\"><span class=\"page-link\">{$i}</span></li>";
            } else {
                $pageUrl = self::buildUrl($baseUrl, array_merge($queryParams, ['pagina' => $i]));
                $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$pageUrl}\">{$i}</a></li>";
            }
        }

        
        if ($fim < $totalPaginas) {
            if ($fim < $totalPaginas - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            $lastUrl = self::buildUrl($baseUrl, array_merge($queryParams, ['pagina' => $totalPaginas]));
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$lastUrl}\">{$totalPaginas}</a></li>";
        }

        
        if ($paginaAtual < $totalPaginas) {
            $nextUrl = self::buildUrl($baseUrl, array_merge($queryParams, ['pagina' => $paginaAtual + 1]));
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$nextUrl}\">PRÓXIMO</a></li>";
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">PRÓXIMO</span></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    
    private static function buildUrl(string $baseUrl, array $params): string
    {
        
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        if (empty($params)) {
            return $baseUrl;
        }

        $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
        return $baseUrl . $separator . http_build_query($params);
    }

    
    public static function info(int $total, int $paginaAtual, int $itensPorPagina): string
    {
        if ($total === 0) {
            return '<p class="text-muted text-center">NENHUM RESULTADO ENCONTRADO</p>';
        }

        $inicio = ($paginaAtual - 1) * $itensPorPagina + 1;
        $fim = min($paginaAtual * $itensPorPagina, $total);

        return "<p class=\"text-muted text-center\">EXIBINDO {$inicio} - {$fim} DE {$total} RESULTADOS</p>";
    }
}

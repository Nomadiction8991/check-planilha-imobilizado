<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class LegacyReportTemplateService
{
    public function templatePath(string $formulario): string
    {
        return resource_path('legacy-reports/templates/' . basename($formulario) . '.php');
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function loadTemplateParts(string $formulario): array
    {
        $templatePath = $this->templatePath($formulario);

        if (!is_file($templatePath)) {
            throw new RuntimeException('Template do formulário não encontrado.');
        }

        $template = file_get_contents($templatePath);

        if ($template === false) {
            throw new RuntimeException('Não foi possível ler o template do relatório.');
        }

        $start = strpos($template, '<!-- A4-START -->');
        $end = strpos($template, '<!-- A4-END -->');

        if ($start === false || $end === false) {
            throw new RuntimeException('Template do relatório está incompleto.');
        }

        $a4Block = trim(substr($template, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));
        $styleContent = '';
        $backgroundImageUrl = '';

        if (preg_match('/<style>(.*?)<\/style>/s', $template, $matches) === 1) {
            $styleContent = $this->rewriteAssetPaths($matches[1]);
        }

        if (preg_match('/background:\s*#?[^;]*url\([\'"]?([^\'"()]+)[\'"]?\)/', $template, $matches) === 1) {
            $backgroundImageUrl = $this->normalizeAssetUrl($matches[1]);
        }

        return [$a4Block, $styleContent, $backgroundImageUrl];
    }

    public function renderFilledTemplate(string $formulario, string $html, array $produto, array $planilha): string
    {
        $bootstrapPath = resource_path('legacy-reports/fillers/bootstrap.php');
        if (!is_file($bootstrapPath)) {
            throw new RuntimeException('Bootstrap de preenchimento não encontrado.');
        }

        require_once $bootstrapPath;

        if (!function_exists('appReportAplicarPreenchimentoRelatorio')) {
            throw new RuntimeException('Bootstrap de preenchimento do relatório está indisponível.');
        }

        return appReportAplicarPreenchimentoRelatorio($formulario, $html, $produto, $planilha);
    }

    public function extractBackgroundImageUrl(string $formulario): string
    {
        [, , $backgroundImageUrl] = $this->loadTemplateParts($formulario);

        return $backgroundImageUrl;
    }

    private function normalizeAssetUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        if (str_starts_with($url, '/assets/')) {
            return asset(ltrim($url, '/'));
        }

        return $url;
    }

    private function rewriteAssetPaths(string $content): string
    {
        return (string) preg_replace_callback(
            '#url\(([\'"]?)(/assets/[^)\'"]+)\1\)#',
            fn (array $matches): string => 'url(' . $matches[1] . $this->normalizeAssetUrl($matches[2]) . $matches[1] . ')',
            $content,
        );
    }
}

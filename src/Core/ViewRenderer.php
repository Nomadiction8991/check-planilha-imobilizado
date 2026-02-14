<?php

declare(strict_types=1);

namespace App\Core;

class ViewRenderer
{
    private static string $viewsPath = __DIR__ . '/../Views/';
    private static string $layoutsPath = __DIR__ . '/../Views/layouts/';
    private static string $partialsPath = __DIR__ . '/../Views/partials/';
    private static string $defaultLayout = 'app';

    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        $layout = $layout ?? self::$defaultLayout;

        extract($data, EXTR_SKIP);

        ob_start();
        $viewPath = self::resolvePath(self::$viewsPath, $view);

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        require $viewPath;
        $content = ob_get_clean();

        if ($layout) {
            $layoutPath = self::resolvePath(self::$layoutsPath, $layout);

            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout não encontrado: {$layout}");
            }

            require $layoutPath;
        } else {
            echo $content;
        }
    }

    public static function renderView(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        $viewPath = self::resolvePath(self::$viewsPath, $view);

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        require $viewPath;
        return ob_get_clean() ?: '';
    }

    public static function partial(string $partial, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        $partialPath = self::resolvePath(self::$partialsPath, $partial);

        if (!file_exists($partialPath)) {
            throw new \RuntimeException("Partial não encontrado: {$partial}");
        }

        require $partialPath;
        return ob_get_clean() ?: '';
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function jsonError(string $message, int $status = 400): void
    {
        self::json(['error' => true, 'message' => $message], $status);
    }

    public static function setDefaultLayout(string $layout): void
    {
        self::$defaultLayout = $layout;
    }

    /**
     * Sanitize view path to prevent path traversal attacks.
     */
    private static function resolvePath(string $basePath, string $name): string
    {
        // Remove path traversal attempts
        $sanitized = str_replace(['../', '..\\', "\0"], '', $name);
        $sanitized = ltrim($sanitized, '/\\');

        $fullPath = realpath($basePath . $sanitized . '.php');
        $realBase = realpath($basePath);

        // Ensure resolved path is within base directory
        if ($fullPath === false || $realBase === false || !str_starts_with($fullPath, $realBase)) {
            return $basePath . $sanitized . '.php';
        }

        return $fullPath;
    }
}

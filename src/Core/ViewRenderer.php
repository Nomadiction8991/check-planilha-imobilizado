<?php

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
        $viewPath = self::$viewsPath . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }

        require $viewPath;
        $content = ob_get_clean();

        
        if ($layout) {
            $layoutPath = self::$layoutsPath . $layout . '.php';

            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout não encontrado: {$layoutPath}");
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
        $viewPath = self::$viewsPath . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }

        require $viewPath;
        return ob_get_clean();
    }

    
    public static function partial(string $partial, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        $partialPath = self::$partialsPath . $partial . '.php';

        if (!file_exists($partialPath)) {
            throw new \RuntimeException("Partial não encontrado: {$partialPath}");
        }

        require $partialPath;
        return ob_get_clean();
    }

    
    public static function json($data, int $status = 200): void
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
}

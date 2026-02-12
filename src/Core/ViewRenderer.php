<?php

namespace App\Core;

/**
 * ViewRenderer - Renderizador de Views
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS renderização de views
 * - Open/Closed: Extensível via métodos, fechado para modificação
 * - Dependency Inversion: Não depende de implementações concretas
 * 
 * Responsabilidades:
 * - Renderização de views com layouts
 * - Suporte a partials (componentes reutilizáveis)
 * - Respostas JSON para APIs
 * - Isolamento de dados via extract()
 * 
 * @package App\Core
 */
class ViewRenderer
{
    /**
     * Diretório base das views
     */
    private static string $viewsPath = __DIR__ . '/../Views/';

    /**
     * Diretório base dos layouts
     */
    private static string $layoutsPath = __DIR__ . '/../Views/layouts/';

    /**
     * Diretório base dos partials
     */
    private static string $partialsPath = __DIR__ . '/../Views/partials/';

    /**
     * Layout padrão
     */
    private static string $defaultLayout = 'app';

    /**
     * Renderiza uma view com layout
     * 
     * @param string $view Nome da view (ex: 'comuns/index')
     * @param array $data Dados a passar para a view
     * @param string|null $layout Nome do layout (null para usar padrão)
     * @return void
     */
    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        $layout = $layout ?? self::$defaultLayout;

        // Extrair variáveis para o escopo da view
        extract($data, EXTR_SKIP);

        // Capturar o conteúdo da view
        ob_start();
        $viewPath = self::$viewsPath . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }

        require $viewPath;
        $content = ob_get_clean();

        // Renderizar com layout
        if ($layout) {
            $layoutPath = self::$layoutsPath . $layout . '.php';

            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout não encontrado: {$layoutPath}");
            }

            require $layoutPath;
        } else {
            // Sem layout, apenas o conteúdo
            echo $content;
        }
    }

    /**
     * Renderiza apenas a view sem layout
     * 
     * @param string $view Nome da view
     * @param array $data Dados a passar
     * @return string Conteúdo renderizado
     */
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

    /**
     * Renderiza um partial (componente reutilizável)
     * 
     * @param string $partial Nome do partial (ex: 'menu', 'search-bar')
     * @param array $data Dados a passar
     * @return string Conteúdo renderizado
     */
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

    /**
     * Renderiza JSON
     * 
     * @param mixed $data Dados a converter para JSON
     * @param int $status Código de status HTTP
     * @return void
     */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Renderiza erro JSON
     * 
     * @param string $message Mensagem de erro
     * @param int $status Código de status HTTP
     * @return void
     */
    public static function jsonError(string $message, int $status = 400): void
    {
        self::json(['error' => true, 'message' => $message], $status);
    }

    /**
     * Define layout padrão
     * 
     * @param string $layout Nome do layout
     * @return void
     */
    public static function setDefaultLayout(string $layout): void
    {
        self::$defaultLayout = $layout;
    }
}

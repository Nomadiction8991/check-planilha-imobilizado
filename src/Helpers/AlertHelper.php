<?php

namespace App\Helpers;

/**
 * Helper para geração de alertas Bootstrap
 * 
 * Centraliza a exibição de mensagens de sucesso, erro, aviso e informação.
 */
class AlertHelper
{
    /**
     * Gera alerta de sucesso
     */
    public static function success(string $message, bool $dismissible = true): string
    {
        return self::render('success', $message, $dismissible);
    }
    
    /**
     * Gera alerta de erro
     */
    public static function error(string $message, bool $dismissible = true): string
    {
        return self::render('danger', $message, $dismissible);
    }
    
    /**
     * Gera alerta de aviso
     */
    public static function warning(string $message, bool $dismissible = true): string
    {
        return self::render('warning', $message, $dismissible);
    }
    
    /**
     * Gera alerta de informação
     */
    public static function info(string $message, bool $dismissible = true): string
    {
        return self::render('info', $message, $dismissible);
    }
    
    /**
     * Renderiza alerta genérico
     */
    private static function render(string $type, string $message, bool $dismissible): string
    {
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';
        
        $html = "<div class=\"alert alert-{$type}{$dismissClass}\">";
        $html .= $escapedMessage;
        
        if ($dismissible) {
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Exibe alertas baseado em query string (?success=1, ?error=msg, etc.)
     */
    public static function fromQuery(): string
    {
        $html = '';
        
        // Sucesso genérico
        if (isset($_GET['success'])) {
            $message = $_GET['success'] === '1' 
                ? 'OPERAÇÃO REALIZADA COM SUCESSO!' 
                : mb_strtoupper($_GET['success'], 'UTF-8');
            $html .= self::success($message);
        }
        
        // Criado com sucesso
        if (isset($_GET['created'])) {
            $html .= self::success('REGISTRO CRIADO COM SUCESSO!');
        }
        
        // Atualizado com sucesso
        if (isset($_GET['updated'])) {
            $html .= self::success('REGISTRO ATUALIZADO COM SUCESSO!');
        }
        
        // Deletado com sucesso
        if (isset($_GET['deleted'])) {
            $html .= self::success('REGISTRO DELETADO COM SUCESSO!');
        }
        
        // Erro
        if (isset($_GET['error'])) {
            $message = mb_strtoupper($_GET['error'], 'UTF-8');
            $html .= self::error($message);
        }
        
        // Aviso
        if (isset($_GET['warning'])) {
            $message = mb_strtoupper($_GET['warning'], 'UTF-8');
            $html .= self::warning($message);
        }
        
        return $html;
    }
}

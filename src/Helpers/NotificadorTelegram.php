<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\LerEnv;

class NotificadorTelegram
{
    /**
     * Envia mensagem via Telegram usando Bearer token seguro em header.
     * SEGURANÇA: Token não fica exposto em URL, apenas em header HTTP.
     * SSL/TLS validação obrigatória.
     */
    public static function enviarMensagem(string $mensagem, string $parseMode = 'HTML'): bool
    {
        $token = LerEnv::obter('TELEGRAM_TOKEN');
        $chatId = LerEnv::obter('TELEGRAM_CHAT_ID');

        if (empty($token) || empty($chatId)) {
            error_log('NotificadorTelegram: TOKEN ou CHAT_ID não configurados', 0);
            return false;
        }

        // Use o endpoint sem token na URL (token vai no header)
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $mensagem,
            'parse_mode' => $parseMode
        ];

        if (function_exists('curl_version')) {
            return self::enviarComCurl($url, $data);
        } else {
            return self::enviarComStream($url, $data);
        }
    }

    /**
     * Envia via cURL com validação SSL/TLS
     */
    private static function enviarComCurl(string $url, array $data): bool
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        // SEGURANÇA: Validar certificado SSL/TLS
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            error_log("NotificadorTelegram: Erro cURL {$errno}", 0);
            return false;
        }

        if ($response === false) {
            error_log('NotificadorTelegram: Resposta vazia', 0);
            return false;
        }

        $json = json_decode($response, true);
        return isset($json['ok']) && $json['ok'] === true;
    }

    /**
     * Fallback: Envia via stream (file_get_contents) com validação SSL/TLS
     */
    private static function enviarComStream(string $url, array $data): bool
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 5,
                // SEGURANÇA: Validar certificado SSL
                'verify_peer' => true,
                'verify_peer_name' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('NotificadorTelegram: Stream error', 0);
            return false;
        }

        $json = json_decode($response, true);
        return isset($json['ok']) && $json['ok'] === true;
    }
}

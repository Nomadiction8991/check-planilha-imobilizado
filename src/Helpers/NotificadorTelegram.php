<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\LerEnv;

class NotificadorTelegram
{
    public static function enviarMensagem(string $mensagem, string $parseMode = 'HTML'): bool
    {
        $token = LerEnv::obter('TELEGRAM_TOKEN');
        $chatId = LerEnv::obter('TELEGRAM_CHAT_ID');

        if (empty($token) || empty($chatId)) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $mensagem,
            'parse_mode' => $parseMode
        ];

        if (function_exists('curl_version')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($errno) {
                return false;
            }
        } else {
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\n",
                    'content' => http_build_query($data),
                    'timeout' => 5
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return false;
            }
        }

        $json = json_decode($response, true);
        return isset($json['ok']) && $json['ok'] === true;
    }
}

<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Modo igreja
    |--------------------------------------------------------------------------
    |
    | Liga a cópia local do sistema no notebook da igreja. No servidor fica desligado.
    |
    */

    'ativo' => (bool) env('MODO_IGREJA_ATIVO', false),

    /*
    | Porta do servidor local. Acima de 1024, para não exigir administrador no Linux.
    */
    'porta' => (int) env('MODO_IGREJA_PORTA', 8080),

    /*
    | Nome da rede Wi-Fi que o notebook cria para os celulares.
    */
    'rede_nome' => env('MODO_IGREJA_REDE_NOME', 'CheckPlanilha'),

    /*
    | Endereço do servidor online. Usado pela sincronização.
    */
    'servidor_url' => env('MODO_IGREJA_SERVIDOR_URL'),

    // Endereço gravado na configuração do notebook quando nenhum outro for informado.
    'servidor_padrao' => env('MODO_IGREJA_SERVIDOR_PADRAO', 'https://checkplanilha.anvy.com.br'),

    // Endereço usado nos links do servidor; se estiver vazio, usa o endereço de acesso.
    'servidor_publico' => env('MODO_IGREJA_SERVIDOR_PUBLICO') ?: env('MODO_IGREJA_SERVIDOR_URL'),

    // Sincronização, saúde e rotas locais nunca são repassadas ao servidor.
    'sem_repasse' => ['api/*', 'up', 'modo-igreja/*'],

    /*
    | Código de acesso deste notebook no servidor. É um segredo: não versionar.
    */
    'aparelho_token' => env('MODO_IGREJA_APARELHO_TOKEN'),

];

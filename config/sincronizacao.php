<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tabelas que sincronizam
    |--------------------------------------------------------------------------
    |
    | Todas as tabelas de dados do negócio. Sessões, cache, tokens e a fila de
    | tarefas ficam de fora: são de cada máquina ou de cada execução.
    |
    */

    'tabelas' => [
        'administracoes',
        'comums',
        'configuracoes',
        'dependencias',
        'importacao_acoes',
        'importacoes',
        'import_erros',
        'import_job_processed',
        'produtos',
        'tipos_bens',
        'usuarios',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tabela de registro
    |--------------------------------------------------------------------------
    |
    | Onde os gatilhos gravam cada insert, update e delete.
    |
    */

    'tabela_registro' => 'sincronizacao_alteracoes',

];

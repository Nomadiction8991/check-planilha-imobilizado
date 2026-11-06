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

    /*
    |--------------------------------------------------------------------------
    | Tabelas em que o servidor vence
    |--------------------------------------------------------------------------
    |
    | Usuários, permissões, administrações e configurações. Se o mesmo campo mudar
    | nos dois lados, a mudança do notebook é descartada e não vira conflito.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Ordem da cópia inicial
    |--------------------------------------------------------------------------
    |
    | Pai antes de filho, para o notebook gravar sem violar chaves estrangeiras.
    | Na hora de esvaziar, a ordem é a inversa.
    |
    */

    'ordem_da_copia' => [
        'administracoes',
        'comums',
        'usuarios',
        'configuracoes',
        'tipos_bens',
        'dependencias',
        'importacoes',
        'importacao_acoes',
        'import_erros',
        'import_job_processed',
        'produtos',
    ],

    'servidor_vence' => [
        'usuarios',
        'administracoes',
        'configuracoes',
    ],

];

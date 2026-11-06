<?php

// Instaladores que o sistema entrega para download. Os arquivos não vão para o Git: ficam na pasta
// abaixo, que pode ser trocada pela variável INSTALADORES_PASTA.
return [
    'pasta' => env('INSTALADORES_PASTA', storage_path('app/instaladores')),

    // Chave usada na URL => nome do arquivo dentro da pasta.
    'arquivos' => [
        'windows' => 'CheckPlanilhaIgreja-0.1.0-setup.exe',
        'linux' => 'check-planilha-igreja_0.1.0_amd64.deb',
    ],
];

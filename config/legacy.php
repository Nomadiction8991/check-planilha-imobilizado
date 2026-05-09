<?php

declare(strict_types=1);

use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use App\Models\Legacy\Usuario;

return [
    'root_path' => dirname(__DIR__, 2),
    'public_url' => env('LEGACY_PUBLIC_URL', 'http://localhost'),
    'paths' => [
        'controllers' => 'app/app/Http/Controllers',
        'services' => 'app/app/Services',
        'repositories' => 'app/app/Repositories',
        'views' => 'app/resources/views',
        'public' => 'app/public',
    ],
    'modules' => [
        [
            'key' => 'churches',
            'title' => 'Igrejas',
            'category' => 'Estrutura',
            'tone' => 'structure',
            'description' => 'Base das igrejas e seus vínculos com a unidade ativa.',
            'legacy_path' => '/churches',
            'target' => 'app/Http/Controllers/LegacyChurchController.php',
            'model' => Comum::class,
        ],
        [
            'key' => 'products',
            'title' => 'Produtos',
            'category' => 'Inventário',
            'tone' => 'inventory',
            'description' => 'Inventário de bens com conferência e atualização de status.',
            'legacy_path' => '/products/view',
            'target' => 'app/Http/Controllers/LegacyProductController.php',
            'model' => Produto::class,
        ],
        [
            'key' => 'products-new',
            'title' => 'Produtos novos',
            'category' => 'Inventário',
            'tone' => 'inventory',
            'description' => 'Itens recém-marcados aguardando revisão do inventário.',
            'legacy_path' => '/products/novo',
            'target' => 'app/Http/Controllers/LegacyProductController.php',
            'model' => Produto::class,
            'scope' => 'newProducts',
        ],
        [
            'key' => 'departments',
            'title' => 'Dependências',
            'category' => 'Estrutura',
            'tone' => 'structure',
            'description' => 'Dependências físicas vinculadas a cada igreja.',
            'legacy_path' => '/departments',
            'target' => 'app/Http/Controllers/LegacyDepartmentController.php',
            'model' => Dependencia::class,
        ],
        [
            'key' => 'asset-types',
            'title' => 'Tipos de bem',
            'category' => 'Catálogo',
            'tone' => 'catalog',
            'description' => 'Catálogo dos tipos de bem usados no cadastro.',
            'legacy_path' => '/asset-types',
            'target' => 'app/Http/Controllers/LegacyAssetTypeController.php',
            'model' => TipoBem::class,
        ],
        [
            'key' => 'users',
            'title' => 'Usuários',
            'category' => 'Acesso',
            'tone' => 'access',
            'description' => 'Contas, perfis e vínculo com a igreja ativa.',
            'legacy_path' => '/users',
            'target' => 'app/Http/Controllers/LegacyUserController.php',
            'model' => Usuario::class,
        ],
        [
            'key' => 'reports',
            'title' => 'Relatórios',
            'category' => 'Fluxo',
            'tone' => 'flow',
            'description' => 'Geração, impressão e posição de estoque da igreja.',
            'legacy_path' => '/reports',
            'target' => 'app/Http/Controllers/LegacyReportController.php',
        ],
        [
            'key' => 'audits',
            'title' => 'Auditoria',
            'category' => 'Governança',
            'tone' => 'audit',
            'description' => 'Rastreamento e consulta das ações registradas no sistema.',
            'legacy_path' => '/audits',
            'target' => 'app/Http/Controllers/LegacyAuditController.php',
        ],
        [
            'key' => 'spreadsheets',
            'title' => 'Importação de planilhas',
            'category' => 'Fluxo',
            'tone' => 'flow',
            'description' => 'Importação, análise e consolidação de planilhas.',
            'legacy_path' => '/spreadsheets/import',
            'target' => 'app/Http/Controllers/SpreadsheetImportController.php',
        ],
    ],
    'audit' => [
        'storage_file' => env(
            'LEGACY_AUDIT_LOG_PATH',
            sys_get_temp_dir() . '/check-planilha-imobilizado/audits/audit-log.jsonl'
        ),
        'modules' => [
            'Sistema',
            'Sessão',
            'Produtos',
            'Igrejas',
            'Dependências',
            'Tipos de bem',
            'Administrações',
            'Usuários',
            'Relatórios',
            'Importação',
        ],
    ],
    'permissions' => [
        'defaults' => [
            'dashboard.view' => true,
            'products.view' => true,
            'products.create' => true,
            'products.edit' => true,
            'products.delete' => false,
            'churches.view' => false,
            'churches.create' => false,
            'churches.edit' => false,
            'churches.delete' => false,
            'departments.view' => false,
            'departments.create' => false,
            'departments.edit' => false,
            'departments.delete' => false,
            'asset-types.view' => false,
            'asset-types.create' => false,
            'asset-types.edit' => false,
            'asset-types.delete' => false,
            'administrations.view' => false,
            'administrations.create' => false,
            'administrations.edit' => false,
            'administrations.delete' => false,
            'users.view' => false,
            'users.create' => false,
            'users.edit' => false,
            'users.delete' => false,
            'users.manage_other_administrations' => false,
            'users.permissions.manage' => false,
            'reports.view' => true,
            'reports.changes.view' => false,
            'reports.editor' => false,
            'audits.view' => false,
            'spreadsheets.import' => true,
            'spreadsheets.errors.view' => true,
            'spreadsheets.errors.export' => true,
            'spreadsheets.errors.resolve' => false,
        ],
        'aliases' => [
            'products.manage' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
            ],
            'churches.manage' => [
                'churches.view',
                'churches.create',
                'churches.edit',
                'churches.delete',
            ],
            'departments.manage' => [
                'departments.view',
                'departments.create',
                'departments.edit',
                'departments.delete',
            ],
            'asset-types.manage' => [
                'asset-types.view',
                'asset-types.create',
                'asset-types.edit',
                'asset-types.delete',
            ],
            'administrations.manage' => [
                'administrations.view',
                'administrations.create',
                'administrations.edit',
                'administrations.delete',
            ],
            'users.manage' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.manage_other_administrations',
                'users.permissions.manage',
            ],
        ],
        'groups' => [
            [
                'key' => 'dashboard',
                'title' => 'Painel',
                'description' => 'Acesso à tela inicial e ao resumo operacional.',
                'abilities' => [
                    [
                        'key' => 'dashboard.view',
                        'label' => 'Abrir painel',
                        'description' => 'Libera a tela inicial do sistema.',
                    ],
                ],
            ],
            [
                'key' => 'products',
                'title' => 'Produtos',
                'description' => 'Consulta, cadastro e manutenção do inventário.',
                'abilities' => [
                    [
                        'key' => 'products.view',
                        'label' => 'Visualizar produtos',
                        'description' => 'Libera a listagem, os filtros e a navegação de produtos.',
                    ],
                    [
                        'key' => 'products.create',
                        'label' => 'Cadastrar produtos',
                        'description' => 'Libera o formulário de novo produto.',
                    ],
                    [
                        'key' => 'products.edit',
                        'label' => 'Editar e verificar',
                        'description' => 'Libera edição, verificação, observação e ajustes de produtos.',
                    ],
                    [
                        'key' => 'products.delete',
                        'label' => 'Excluir produtos',
                        'description' => 'Libera exclusão de produtos quando o recurso existir.',
                    ],
                ],
            ],
            [
                'key' => 'churches',
                'title' => 'Igrejas',
                'description' => 'Cadastro e manutenção das igrejas cadastradas.',
                'abilities' => [
                    [
                        'key' => 'churches.view',
                        'label' => 'Visualizar igrejas',
                        'description' => 'Libera a listagem e os filtros de igrejas.',
                    ],
                    [
                        'key' => 'churches.create',
                        'label' => 'Cadastrar igrejas',
                        'description' => 'Libera o formulário de nova igreja.',
                    ],
                    [
                        'key' => 'churches.edit',
                        'label' => 'Editar igrejas',
                        'description' => 'Libera a edição do cadastro de igrejas.',
                    ],
                    [
                        'key' => 'churches.delete',
                        'label' => 'Excluir produtos da igreja',
                        'description' => 'Libera a exclusão em lote dos produtos vinculados.',
                    ],
                ],
            ],
            [
                'key' => 'departments',
                'title' => 'Dependências',
                'description' => 'Cadastro das dependências vinculadas às igrejas.',
                'abilities' => [
                    [
                        'key' => 'departments.view',
                        'label' => 'Visualizar dependências',
                        'description' => 'Libera a listagem e os filtros de dependências.',
                    ],
                    [
                        'key' => 'departments.create',
                        'label' => 'Cadastrar dependências',
                        'description' => 'Libera o formulário de nova dependência.',
                    ],
                    [
                        'key' => 'departments.edit',
                        'label' => 'Editar dependências',
                        'description' => 'Libera a edição de dependências cadastradas.',
                    ],
                    [
                        'key' => 'departments.delete',
                        'label' => 'Excluir dependências',
                        'description' => 'Libera a remoção de dependências que ainda podem ser apagadas.',
                    ],
                ],
            ],
            [
                'key' => 'asset-types',
                'title' => 'Tipos de bem',
                'description' => 'Catálogo de classificação dos bens do inventário.',
                'abilities' => [
                    [
                        'key' => 'asset-types.view',
                        'label' => 'Visualizar tipos de bem',
                        'description' => 'Libera a listagem e os filtros do catálogo.',
                    ],
                    [
                        'key' => 'asset-types.create',
                        'label' => 'Cadastrar tipos de bem',
                        'description' => 'Libera o formulário de novo tipo de bem.',
                    ],
                    [
                        'key' => 'asset-types.edit',
                        'label' => 'Editar tipos de bem',
                        'description' => 'Libera a edição do catálogo de tipos de bem.',
                    ],
                    [
                        'key' => 'asset-types.delete',
                        'label' => 'Excluir tipos de bem',
                        'description' => 'Libera a exclusão do tipo de bem quando permitida.',
                    ],
                ],
            ],
            [
                'key' => 'administrations',
                'title' => 'Administrações',
                'description' => 'Cadastro das administrações usadas na importação.',
                'abilities' => [
                    [
                        'key' => 'administrations.view',
                        'label' => 'Visualizar administrações',
                        'description' => 'Libera a listagem e os filtros das administrações.',
                    ],
                    [
                        'key' => 'administrations.create',
                        'label' => 'Cadastrar administrações',
                        'description' => 'Libera o formulário de nova administração.',
                    ],
                    [
                        'key' => 'administrations.edit',
                        'label' => 'Editar administrações',
                        'description' => 'Libera a edição de administrações cadastradas.',
                    ],
                    [
                        'key' => 'administrations.delete',
                        'label' => 'Excluir administrações',
                        'description' => 'Libera a remoção de administrações cadastradas.',
                    ],
                ],
            ],
            [
                'key' => 'users',
                'title' => 'Usuários',
                'description' => 'Cadastro, edição e controle de acesso dos usuários.',
                'abilities' => [
                    [
                        'key' => 'users.view',
                        'label' => 'Visualizar usuários',
                        'description' => 'Libera a listagem de usuários e o acesso ao módulo.',
                    ],
                    [
                        'key' => 'users.create',
                        'label' => 'Cadastrar usuários',
                        'description' => 'Libera o formulário de novo usuário.',
                    ],
                    [
                        'key' => 'users.edit',
                        'label' => 'Editar usuários',
                        'description' => 'Libera a edição de usuários cadastrados.',
                    ],
                    [
                        'key' => 'users.delete',
                        'label' => 'Excluir usuários',
                        'description' => 'Libera a exclusão de usuários cadastrados.',
                    ],
                    [
                        'key' => 'users.manage_other_administrations',
                        'label' => 'Gerenciar outras administrações',
                        'description' => 'Permite cadastrar e editar usuários fora da própria administração.',
                    ],
                    [
                        'key' => 'users.permissions.manage',
                        'label' => 'Editar permissões',
                        'description' => 'Permite alterar o pacote de permissões de outro usuário.',
                    ],
                ],
            ],
            [
                'key' => 'reports',
                'title' => 'Relatórios',
                'description' => 'Navegação e edição dos formulários de relatório.',
                'abilities' => [
                    [
                        'key' => 'reports.view',
                        'label' => 'Visualizar relatórios',
                        'description' => 'Libera a lista e as prévias dos relatórios.',
                    ],
                    [
                        'key' => 'reports.changes.view',
                        'label' => 'Ver posição de estoque',
                        'description' => 'Libera a tela de posição de estoque e backup da verificação.',
                    ],
                    [
                        'key' => 'reports.editor',
                        'label' => 'Abrir editor de relatórios',
                        'description' => 'Libera o editor dos formulários 14.x.',
                    ],
                ],
            ],
            [
                'key' => 'audits',
                'title' => 'Auditoria',
                'description' => 'Consulta da trilha de eventos registrados no sistema.',
                'abilities' => [
                    [
                        'key' => 'audits.view',
                        'label' => 'Visualizar auditoria',
                        'description' => 'Libera a tela com os eventos auditáveis do sistema.',
                    ],
                ],
            ],
            [
                'key' => 'spreadsheets',
                'title' => 'Importação',
                'description' => 'Importação, rastreamento e tratamento de erros.',
                'abilities' => [
                    [
                        'key' => 'spreadsheets.import',
                        'label' => 'Importar planilhas',
                        'description' => 'Libera o fluxo de importação e processamento.',
                    ],
                    [
                        'key' => 'spreadsheets.errors.view',
                        'label' => 'Visualizar erros',
                        'description' => 'Libera a lista de erros encontrados na importação.',
                    ],
                    [
                        'key' => 'spreadsheets.errors.export',
                        'label' => 'Exportar erros',
                        'description' => 'Libera a exportação CSV dos erros da importação.',
                    ],
                    [
                        'key' => 'spreadsheets.errors.resolve',
                        'label' => 'Resolver erros',
                        'description' => 'Libera a resolução manual dos erros da importação.',
                    ],
                ],
            ],
        ],
    ],
];

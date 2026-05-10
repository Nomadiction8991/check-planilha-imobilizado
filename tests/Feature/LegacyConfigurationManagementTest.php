<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyMailConfigurationServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyConfigurationManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'ADMIN@LOCALHOST',
                'comum_id' => null,
                'administracao_id' => null,
                'administracoes_permitidas' => [],
                'is_admin' => true,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
    }

    public function testIndexPageRendersConfigurationForm(): void
    {
        $this->instance(LegacyMailConfigurationServiceInterface::class, new class implements LegacyMailConfigurationServiceInterface
        {
            public function current(): LegacyMailConfigurationData
            {
                return LegacyMailConfigurationData::fromArray([
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'scheme' => 'tls',
                    'username' => 'contato@gmail.com',
                    'password' => null,
                    'fromAddress' => 'contato@gmail.com',
                    'fromName' => 'Check Planilha',
                ]);
            }

            public function save(LegacyMailConfigurationData $data): void
            {
            }

            public function configureRuntimeMailer(): void
            {
            }
        });

        $this->instance(LegacyNavigationServiceInterface::class, new class implements LegacyNavigationServiceInterface
        {
            public function currentOrder(): LegacyNavigationOrderData
            {
                return LegacyNavigationOrderData::fromArray([
                    'items' => ['verification', 'products', 'labels', 'configuracoes'],
                ]);
            }

            public function saveOrder(LegacyNavigationOrderData $data): void
            {
            }

            public function navigation(array $permissions, bool $isAdmin): array
            {
                return [
                    [
                        'key' => 'verification',
                        'label' => 'Verificação',
                        'route' => '/verificacao',
                        'active_patterns' => ['migration.products.verification'],
                    ],
                    [
                        'key' => 'products',
                        'label' => 'Produtos',
                        'route' => '/produtos',
                        'active_patterns' => ['migration.products.index'],
                    ],
                    [
                        'key' => 'labels',
                        'label' => 'Etiquetas',
                        'route' => '/produtos/etiquetas',
                        'active_patterns' => ['migration.labels.*', 'migration.compat.products.copy-labels'],
                    ],
                    [
                        'key' => 'configuracoes',
                        'label' => 'Configurações',
                        'route' => '/configuracoes',
                        'active_patterns' => ['migration.configuracoes.*'],
                    ],
                ];
            }

            public function editorItems(): array
            {
                return [
                    [
                        'key' => 'verification',
                        'label' => 'Verificação',
                        'subtitle' => 'Permissão: products.edit',
                        'admin_only' => false,
                    ],
                    [
                        'key' => 'products',
                        'label' => 'Produtos',
                        'subtitle' => 'Permissão: products.view',
                        'admin_only' => false,
                    ],
                    [
                        'key' => 'labels',
                        'label' => 'Etiquetas',
                        'subtitle' => 'Permissão: products.view',
                        'admin_only' => false,
                    ],
                    [
                        'key' => 'configuracoes',
                        'label' => 'Configurações',
                        'subtitle' => 'Apenas administradores',
                        'admin_only' => true,
                    ],
                ];
            }

            public function availableKeys(): array
            {
                return ['verification', 'products', 'labels', 'configuracoes'];
            }
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@LOCALHOST',
            'is_admin' => true,
        ])->get(route('migration.configuracoes.index'));

        $response->assertOk();
        $response->assertSee('Host SMTP');
        $response->assertSee('smtp.gmail.com');
        $response->assertSee('Salvar e-mail');
        $response->assertSee('Salvar menu');
        $response->assertSee('Ordem dos menus');
        $response->assertSeeInOrder(['Verificação', 'Produtos', 'Etiquetas', 'Configurações']);
    }

    public function testUpdateMailConfigurationPersistsValues(): void
    {
        $this->mock(LegacyMailConfigurationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('save')
                ->once()
                ->withArgs(static function (LegacyMailConfigurationData $data): bool {
                    return $data->host === 'smtp.gmail.com'
                        && $data->port === 587
                        && $data->scheme === 'tls'
                        && $data->username === 'contato@gmail.com'
                        && $data->password === 'senha-do-app'
                        && $data->fromAddress === 'contato@gmail.com'
                        && $data->fromName === 'Check Planilha';
                });
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
            $mock->shouldReceive('saveOrder')->never();
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@LOCALHOST',
            'is_admin' => true,
        ])->post(route('migration.configuracoes.update'), [
            'config_section' => 'mail',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_scheme' => 'tls',
            'mail_username' => 'contato@gmail.com',
            'mail_password' => 'senha-do-app',
            'mail_from_address' => 'contato@gmail.com',
            'mail_from_name' => 'Check Planilha',
        ]);

        $response->assertRedirect(route('migration.configuracoes.index'));
        $response->assertSessionHas('status', 'Configurações salvas com sucesso.');
    }

    public function testUpdateMenuConfigurationPersistsValues(): void
    {
        $this->mock(LegacyMailConfigurationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('save')->never();
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
            $mock->shouldReceive('saveOrder')
                ->once()
                ->withArgs(static function (LegacyNavigationOrderData $data): bool {
                    return $data->items === ['products', 'verification', 'labels', 'configuracoes'];
                });
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@LOCALHOST',
            'is_admin' => true,
        ])->post(route('migration.configuracoes.update'), [
            'config_section' => 'menu',
            'menu_order' => ['products', 'verification', 'labels', 'configuracoes'],
        ]);

        $response->assertRedirect(route('migration.configuracoes.index'));
        $response->assertSessionHas('status', 'Configurações salvas com sucesso.');
    }
}

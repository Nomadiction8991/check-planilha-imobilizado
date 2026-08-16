<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyMailConfigurationServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyConfigurationControllerTest extends TestCase
{
    private const array AUTH_SESSION = [
        '_enforce_legacy_auth' => true,
        'usuario_id' => 1,
        'usuario_nome' => 'Administrador',
        'usuario_email' => 'ADMIN@LOCALHOST',
        'is_admin' => true,
    ];

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

    /* ───────── Index endpoint ───────── */

    public function testGuestRedirectsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.configuracoes.index'));

        $response->assertRedirect(route('migration.login'));
    }

    public function testNonAdminRedirectsToDashboard(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'is_admin' => false,
        ])->get(route('migration.configuracoes.index'));

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('status_type', 'error');
    }

    public function testIndexRendersConfigurationForm(): void
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
                return [];
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

        $response = $this->withSession(self::AUTH_SESSION)
            ->get(route('migration.configuracoes.index'));

        $response->assertOk();
        $response->assertSee('Host SMTP');
        $response->assertSee('smtp.gmail.com');
        $response->assertSee('Salvar e-mail');
        $response->assertSee('Salvar menu');
        $response->assertSee('Ordem dos menus');
        $response->assertSeeInOrder(['Verificação', 'Produtos', 'Etiquetas', 'Configurações']);
    }

    /* ───────── Update endpoint ───────── */

    public function testUpdateMailConfigurationPersists(): void
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

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
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
        $response->assertSessionHas('status_type', 'success');
    }

    public function testUpdateMenuConfigurationPersists(): void
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

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'menu',
                'menu_order' => ['products', 'verification', 'labels', 'configuracoes'],
            ]);

        $response->assertRedirect(route('migration.configuracoes.index'));
        $response->assertSessionHas('status', 'Configurações salvas com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testUpdateHandlesRuntimeExceptionOnMail(): void
    {
        $this->mock(LegacyMailConfigurationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('save')
                ->once()
                ->andThrow(new RuntimeException('Falha ao conectar no SMTP.'));
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
            $mock->shouldReceive('saveOrder')->never();
        });

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'mail',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_scheme' => 'tls',
                'mail_username' => 'contato@gmail.com',
                'mail_password' => 'senha-do-app',
                'mail_from_address' => 'contato@gmail.com',
                'mail_from_name' => 'Check Planilha',
            ]);

        $response->assertSessionHas('status', 'Falha ao conectar no SMTP.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testUpdateHandlesRuntimeExceptionOnMenu(): void
    {
        $this->mock(LegacyMailConfigurationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('save')->never();
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
            $mock->shouldReceive('saveOrder')
                ->once()
                ->andThrow(new RuntimeException('Ordem do menu inválida.'));
        });

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'menu',
                'menu_order' => ['products', 'verification', 'labels', 'configuracoes'],
            ]);

        $response->assertSessionHas('status', 'Ordem do menu inválida.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testUpdateInputExcludesPasswordOnError(): void
    {
        $this->mock(LegacyMailConfigurationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('save')
                ->once()
                ->andThrow(new RuntimeException('SMTP indisponível.'));
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
            $mock->shouldReceive('saveOrder')->never();
        });

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'mail',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_scheme' => 'tls',
                'mail_username' => 'contato@gmail.com',
                'mail_password' => 'senha-secreta-123',
                'mail_from_address' => 'contato@gmail.com',
                'mail_from_name' => 'Check Planilha',
            ]);

        $response->assertSessionMissing('mail_password');
    }

    public function testUpdateReturnsValidationErrors(): void
    {
        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('availableKeys')->andReturn(['verification', 'products', 'labels', 'configuracoes']);
        });

        $response = $this->withSession(self::AUTH_SESSION)
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'mail',
                'mail_host' => '',
                'mail_port' => 'not-a-number',
                'mail_scheme' => 'invalid',
                'mail_username' => 'not-an-email',
                'mail_from_address' => '',
                'mail_from_name' => '',
            ]);

        $response->assertSessionHasErrors([
            'mail_host',
            'mail_port',
            'mail_scheme',
            'mail_username',
            'mail_from_address',
            'mail_from_name',
        ]);
    }

    public function testUpdateGuestRedirectsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->post(route('migration.configuracoes.update'), [
                'config_section' => 'mail',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_scheme' => 'tls',
                'mail_username' => 'contato@gmail.com',
                'mail_password' => 'senha-do-app',
                'mail_from_address' => 'contato@gmail.com',
                'mail_from_name' => 'Check Planilha',
            ]);

        $response->assertRedirect(route('migration.login'));
    }
}

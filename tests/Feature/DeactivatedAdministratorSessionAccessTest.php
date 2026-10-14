<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyProductManagementServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use App\DTO\UserMutationData;
use App\Http\Controllers\LegacyProductController;
use App\Http\Middleware\RequireLegacyPermission;
use App\Http\Middleware\RequireLegacySession;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Produto;
use App\Models\Legacy\Usuario;
use App\Services\LegacyAuthSessionService;
use App\Services\LegacyNativeSessionBridge;
use App\Services\LegacyPermissionService;
use App\Services\LegacyProductBrowserService;
use App\Services\LegacyUserManagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class DeactivatedAdministratorSessionAccessTest extends TestCase
{
    private string $previousSessionSavePath;

    private string|false $previousUseCookies;

    private array $previousCookies;

    private bool $hadPreviousSession;

    private mixed $previousSession;

    private string $nativeSessionDirectory;

    private string $previousEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousEnvironment = $this->app->environment();
        $this->previousSessionSavePath = session_save_path();
        $this->previousUseCookies = ini_get('session.use_cookies');
        $this->previousCookies = $_COOKIE;
        $this->hadPreviousSession = isset($_SESSION);
        $this->previousSession = $_SESSION ?? null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->nativeSessionDirectory = storage_path('framework/testing/native-session-'.bin2hex(random_bytes(6)));
        mkdir($this->nativeSessionDirectory, 0777, true);
        session_save_path($this->nativeSessionDirectory);
        ini_set('session.use_cookies', '1');
        session_id('');

        Schema::create('administracoes', function (Blueprint $table): void {
            $table->id();
            $table->string('descricao')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cidade')->nullable();
            $table->string('cnpj')->nullable();
        });

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->json('administracoes_permitidas')->nullable();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('senha')->nullable();
            $table->integer('ativo')->default(1);
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->integer('rg_igual_cpf')->default(0);
            $table->string('telefone')->nullable();
            $table->integer('casado')->default(0);
            $table->string('nome_conjuge')->nullable();
            $table->string('cpf_conjuge')->nullable();
            $table->string('rg_conjuge')->nullable();
            $table->integer('rg_conjuge_igual_cpf')->default(0);
            $table->string('telefone_conjuge')->nullable();
            $table->string('endereco_cep')->nullable();
            $table->string('endereco_logradouro')->nullable();
            $table->string('endereco_numero')->nullable();
            $table->string('endereco_complemento')->nullable();
            $table->string('endereco_bairro')->nullable();
            $table->string('endereco_cidade')->nullable();
            $table->string('endereco_estado')->nullable();
            $table->string('tipo')->nullable();
            $table->json('permissions')->nullable();
            $table->json('ui_preferences')->nullable();
        });

        Schema::create('comums', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('estado', 2)->nullable();
        });

        Schema::create('dependencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->string('descricao')->nullable();
        });

        Schema::create('tipos_bens', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
        });

        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->id();
            $table->text('menu_order')->nullable();
        });

        Schema::create('produtos', function (Blueprint $table): void {
            $table->id('id_produto');
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('dependencia_id')->nullable();
            $table->unsignedBigInteger('tipo_bem_id')->nullable();
            $table->unsignedBigInteger('editado_tipo_bem_id')->nullable();
            $table->unsignedBigInteger('editado_dependencia_id')->nullable();
            $table->string('codigo')->nullable();
            $table->string('bem')->nullable();
            $table->string('complemento')->nullable();
            $table->integer('novo')->default(0);
            $table->integer('editado')->default(0);
            $table->integer('ativo')->default(1);
            $table->integer('imprimir_14_1')->default(0);
            $table->string('nota_numero')->nullable();
        });
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        session_save_path($this->previousSessionSavePath);
        if ($this->previousUseCookies !== false) {
            ini_set('session.use_cookies', $this->previousUseCookies);
        }
        $_COOKIE = $this->previousCookies;

        if ($this->hadPreviousSession) {
            $_SESSION = $this->previousSession;
        } else {
            unset($_SESSION);
        }

        foreach (glob($this->nativeSessionDirectory.'/sess_*') ?: [] as $sessionFile) {
            unlink($sessionFile);
        }
        rmdir($this->nativeSessionDirectory);

        $this->app->detectEnvironment(fn (): string => $this->previousEnvironment);

        Schema::dropIfExists('produtos');
        Schema::dropIfExists('configuracoes');
        Schema::dropIfExists('tipos_bens');
        Schema::dropIfExists('dependencias');
        Schema::dropIfExists('comums');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('administracoes');

        parent::tearDown();
    }

    public function test_deactivated_administrator_cannot_reuse_native_session_to_view_other_administrations_products(): void
    {
        self::assertSame('sqlite', DB::connection()->getDriverName());
        self::assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->seedAdministrationsAndProducts();
        $admin = $this->seedAdministrator();
        $auth = new LegacyAuthSessionService;
        $permissions = new LegacyPermissionService;

        $authenticatedUser = $auth->attempt('inventory.admin@example.test', 'correct-horse');
        self::assertTrue($authenticatedUser['is_admin']);

        $loginSession = $this->app->make('session')->driver();
        $nativePayload = array_intersect_key(
            $loginSession->all(),
            array_flip([
                'usuario_id',
                'usuario_nome',
                'usuario_email',
                'comum_id',
                'administracao_id',
                'administracoes_permitidas',
                'is_admin',
                'legacy_permissions',
            ]),
        );

        $userManagement = new LegacyUserManagementService($auth, $permissions);
        $userManagement->update($admin, $this->inactiveAdministratorUpdate());
        self::assertSame(0, (int) Usuario::query()->findOrFail($admin->id)->ativo);

        $nativeSessionId = $this->persistNativeSession($nativePayload);
        $_COOKIE[session_name()] = $nativeSessionId;

        $request = Request::create(
            'http://localhost/products',
            'GET',
            [],
            [session_name() => $nativeSessionId],
        );
        $requestSession = $this->app->make('session')->driver();
        $requestSession->flush();
        $requestSession->setId('next'.bin2hex(random_bytes(12)));
        $requestSession->put([
            '_enforce_legacy_auth' => true,
            '_enforce_native_bridge' => true,
        ]);
        $request->setLaravelSession($requestSession);
        $this->app->instance('request', $request);
        $this->app['view']->share('errors', new ViewErrorBag);

        // PHPUnit does not hydrate PHP's native session id from the Request cookie bag.
        session_id($nativeSessionId);
        $this->app->detectEnvironment(static fn (): string => 'local');
        $bridge = new LegacyNativeSessionBridge;
        $bridge->import($request);
        self::assertSame(
            (int) $authenticatedUser['id'],
            (int) $request->session()->get('usuario_id', 0),
            'The subsequent request must import the existing native session before authorization.',
        );

        $controller = new LegacyProductController(
            new LegacyProductBrowserService,
            $this->createStub(LegacyProductManagementServiceInterface::class),
            $this->createStub(LegacyProductUtilityServiceInterface::class),
        );
        $controllerInvoked = false;
        $response = (new RequireLegacySession)->handle(
            $request,
            function (Request $request) use ($permissions, $controller, &$controllerInvoked): Response {
                return (new RequireLegacyPermission($permissions))->handle(
                    $request,
                    function (Request $request) use ($controller, &$controllerInvoked): Response {
                        $controllerInvoked = true;

                        return new Response($controller->index($request)->render());
                    },
                    'products.view',
                );
            },
        );

        self::assertStringNotContainsString(
            'P-20',
            (string) $response->getContent(),
            'An existing native administrator session must not render products from another administration.',
        );
        self::assertFalse((bool) $request->session()->get('is_admin'));
        self::assertFalse($permissions->can('users.permissions.manage'));
        self::assertFalse($controllerInvoked, 'An inactive account must be stopped before product browsing.');
    }

    private function seedAdministrationsAndProducts(): void
    {
        foreach ([10, 20] as $administrationId) {
            $administration = new Administracao;
            $administration->forceFill([
                'id' => $administrationId,
                'descricao' => 'Administração '.$administrationId,
            ])->save();

            $church = new Comum;
            $church->forceFill([
                'id' => $administrationId * 10,
                'codigo' => 'IG-'.$administrationId,
                'descricao' => 'Igreja '.$administrationId,
                'administracao_id' => $administrationId,
                'estado' => 'SP',
            ])->save();

            $product = new Produto;
            $product->forceFill([
                'id_produto' => $administrationId,
                'comum_id' => $administrationId * 10,
                'codigo' => 'P-'.$administrationId,
                'bem' => 'Produto '.$administrationId,
                'ativo' => 1,
            ])->save();
        }
    }

    private function seedAdministrator(): Usuario
    {
        $admin = new Usuario;
        $admin->forceFill([
            'id' => 7,
            'administracao_id' => 10,
            'administracoes_permitidas' => [10],
            'nome' => 'Administrador de teste',
            'email' => 'INVENTORY.ADMIN@EXAMPLE.TEST',
            'senha' => password_hash('correct-horse', PASSWORD_BCRYPT),
            'ativo' => 1,
            'cpf' => '12345678909',
            'rg' => '12345678',
            'tipo' => 'administrador',
            'permissions' => [],
        ])->save();

        return $admin;
    }

    private function inactiveAdministratorUpdate(): UserMutationData
    {
        return new UserMutationData(
            administrationId: 10,
            administrationIds: [10],
            name: 'Administrador de teste',
            email: 'inventory.admin@example.test',
            active: false,
            cpf: '123.456.789-09',
            rg: '12.345.678',
            rgEqualsCpf: false,
            phone: '(11) 99999-0000',
            married: false,
            spouseName: '',
            spouseCpf: '',
            spouseRg: '',
            spouseRgEqualsCpf: false,
            spousePhone: '',
            addressZip: '',
            addressStreet: '',
            addressNumber: '',
            addressComplement: '',
            addressDistrict: '',
            addressCity: '',
            addressState: '',
            permissions: [],
            permissionsProvided: false,
            password: null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistNativeSession(array $payload): string
    {
        $sessionId = 'inactiveadmin'.bin2hex(random_bytes(8));
        session_id($sessionId);
        session_start();
        $_SESSION = $payload;
        session_write_close();
        session_id('');

        return $sessionId;
    }
}

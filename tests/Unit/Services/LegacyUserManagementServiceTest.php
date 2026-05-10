<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\UserMutationData;
use App\Models\Legacy\Usuario;
use App\Services\LegacyUserManagementService;
use Tests\TestCase;
use ReflectionMethod;

final class LegacyUserManagementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testNormalizeAdministrationScopeIdsKeepsPrimaryAdministrationForRestrictedUser(): void
    {
        $service = $this->makeService(false);

        $method = new ReflectionMethod($service, 'normalizeAdministrationScopeIds');

        self::assertSame([7], $method->invoke($service, [], 7));
    }

    public function testNormalizeAdministrationScopeIdsPreservesMultipleAdministrationsForAuthorizedUser(): void
    {
        $service = $this->makeService(true);

        $method = new ReflectionMethod($service, 'normalizeAdministrationScopeIds');

        self::assertSame([7, 8], $method->invoke($service, [7, 8], 7));
    }

    public function testNormalizePayloadForAdministratorForcesAllAccessAndPreservesType(): void
    {
        $service = $this->makeService(true, [7, 8, 9]);

        $method = new ReflectionMethod($service, 'normalizePayload');

        $existingUser = new Usuario();
        $existingUser->forceFill([
            'id' => 1,
            'administracao_id' => 7,
            'nome' => 'Admin',
            'email' => 'ADMIN@LOCALHOST',
            'tipo' => 'administrador',
            'permissions' => [
                'users.view' => true,
            ],
        ]);
        $existingUser->exists = true;

        $data = new UserMutationData(
            administrationId: 8,
            administrationIds: [8],
            name: 'Admin Atualizado',
            email: 'admin.novo@example.com',
            active: true,
            cpf: '12345678909',
            rg: '12345678',
            rgEqualsCpf: false,
            phone: '(65) 99999-0000',
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
            permissions: ['users.edit'],
            permissionsProvided: true,
            password: null,
        );

        $payload = $method->invoke($service, $data, $existingUser);

        self::assertSame([7, 8, 9], $payload['administracoes_permitidas']);
        self::assertSame('administrador', $payload['tipo']);
        self::assertTrue($payload['permissions']['users.view']);
        self::assertTrue($payload['permissions']['users.edit']);
    }

    private function makeService(bool $canManageOtherAdministrations, array $allAdministrationIds = [7, 8]): LegacyUserManagementService
    {
        $auth = $this->createMock(LegacyAuthSessionServiceInterface::class);
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);

        $auth->method('currentUser')->willReturn([
            'id' => 19,
            'nome' => 'Carla',
            'email' => 'carla@example.com',
            'administracao_id' => 7,
            'comum_id' => null,
            'administracoes_permitidas' => [7],
            'is_admin' => false,
        ]);

        $permissions->method('can')->willReturnMap([
            ['users.manage_other_administrations', $canManageOtherAdministrations],
            ['users.permissions.manage', true],
        ]);

        return new class($auth, $permissions, $allAdministrationIds) extends LegacyUserManagementService
        {
            /**
             * @param array<int, int> $allAdministrationIds
             */
            public function __construct(
                LegacyAuthSessionServiceInterface $auth,
                LegacyPermissionServiceInterface $permissions,
                private array $allAdministrationIds,
            ) {
                parent::__construct($auth, $permissions);
            }

            protected function allAdministrationIds(): array
            {
                return $this->allAdministrationIds;
            }
        };
    }
}

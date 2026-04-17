<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Services\LegacyUserManagementService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class LegacyUserManagementServiceTest extends TestCase
{
    public function testNormalizeAdministrationScopeIdsKeepsPrimaryAdministrationForRestrictedUser(): void
    {
        $service = $this->makeService(false);

        $method = new ReflectionMethod($service, 'normalizeAdministrationScopeIds');
        $method->setAccessible(true);

        self::assertSame([7], $method->invoke($service, [], 7));
    }

    public function testNormalizeAdministrationScopeIdsPreservesMultipleAdministrationsForAuthorizedUser(): void
    {
        $service = $this->makeService(true);

        $method = new ReflectionMethod($service, 'normalizeAdministrationScopeIds');
        $method->setAccessible(true);

        self::assertSame([7, 8], $method->invoke($service, [7, 8], 7));
    }

    private function makeService(bool $canManageOtherAdministrations): LegacyUserManagementService
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

        return new LegacyUserManagementService($auth, $permissions);
    }
}

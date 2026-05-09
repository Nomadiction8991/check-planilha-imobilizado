<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Legacy\Usuario;
use App\Services\LegacyAuthSessionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class LegacyAuthSessionServiceTest extends TestCase
{
    public function testAdminUsersReceiveAllAdministrationIds(): void
    {
        $service = new class extends LegacyAuthSessionService
        {
            protected function allAdministrationIds(): array
            {
                return [3, 7, 8];
            }
        };

        $user = new Usuario();
        $user->forceFill([
            'tipo' => 'administrador',
            'administracao_id' => 7,
            'administracoes_permitidas' => [7],
        ]);

        self::assertSame([3, 7, 8], $this->invokeResolveAdministrationIds($service, $user));
    }

    public function testNonAdminUsersKeepPrimaryAdministrationAndSelectedScope(): void
    {
        $service = new class extends LegacyAuthSessionService
        {
            protected function allAdministrationIds(): array
            {
                return [3, 7, 8];
            }
        };

        $user = new Usuario();
        $user->forceFill([
            'tipo' => 'operador',
            'administracao_id' => 7,
            'administracoes_permitidas' => [8],
        ]);

        self::assertSame([8, 7], $this->invokeResolveAdministrationIds($service, $user));
    }

    public function testProtectedAdministratorAccountIsRecognizedAsAdmin(): void
    {
        $service = new LegacyAuthSessionService();

        $user = new Usuario();
        $user->forceFill([
            'id' => 1,
            'tipo' => 'operador',
        ]);

        self::assertTrue($this->invokeInferIsAdmin($service, $user));
    }

    public function testAdminEmailIsRecognizedAsAdmin(): void
    {
        $service = new LegacyAuthSessionService();

        $user = new Usuario();
        $user->forceFill([
            'id' => 9,
            'email' => 'ADMIN@LOCALHOST',
            'tipo' => 'operador',
        ]);

        self::assertTrue($this->invokeInferIsAdmin($service, $user));
    }

    /**
     * @return array<int, int>
     */
    private function invokeResolveAdministrationIds(LegacyAuthSessionService $service, Usuario $user): array
    {
        $method = new ReflectionMethod(LegacyAuthSessionService::class, 'resolveAdministrationIds');
        $method->setAccessible(true);

        /** @var array<int, int> $result */
        $result = $method->invoke($service, $user);

        return $result;
    }

    private function invokeInferIsAdmin(LegacyAuthSessionService $service, Usuario $user): bool
    {
        $method = new ReflectionMethod(LegacyAuthSessionService::class, 'inferIsAdmin');
        $method->setAccessible(true);

        /** @var bool $result */
        $result = $method->invoke($service, $user);

        return $result;
    }
}

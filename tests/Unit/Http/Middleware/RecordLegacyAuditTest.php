<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\DTO\LegacyAuditEntryData;
use App\Http\Middleware\RecordLegacyAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class RecordLegacyAuditTest extends TestCase
{
    public function testRecordsSuccessfulLoginEvent(): void
    {
        $auth = $this->createMock(LegacyAuthSessionServiceInterface::class);
        $audits = $this->createMock(LegacyAuditTrailServiceInterface::class);

        $auth->expects($this->exactly(2))
            ->method('currentUser')
            ->willReturnOnConsecutiveCalls(
                null,
                [
                    'id' => 19,
                    'nome' => 'Carla',
                    'email' => 'carla@example.com',
                    'administracao_id' => 5,
                    'comum_id' => null,
                    'is_admin' => false,
                ]
            );

        $audits->expects($this->once())
            ->method('record')
            ->with($this->callback(function (LegacyAuditEntryData $entry): bool {
                return $entry->module === 'Sessão'
                    && $entry->action === 'Login'
                    && $entry->description === 'Autenticação realizada com sucesso.'
                    && $entry->userId === 19
                    && $entry->administrationId === 5;
            }));

        $middleware = new RecordLegacyAudit($auth, $audits);
        $request = Request::create('/login', 'POST');
        $request->setRouteResolver(static fn (): object => new class {
            public function getName(): string
            {
                return 'migration.login.store';
            }
        });

        $response = new RedirectResponse('/painel');
        $result = $middleware->handle($request, static fn (): RedirectResponse => $response);

        self::assertSame($response, $result);
    }
}

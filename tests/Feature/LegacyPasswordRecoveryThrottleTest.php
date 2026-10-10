<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyPasswordRecoveryServiceInterface;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyPasswordRecoveryThrottleTest extends TestCase
{
    public function testPasswordRecoveryReturnsTooManyRequestsAfterFiveAttempts(): void
    {
        $this->mock(LegacyPasswordRecoveryServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('recover')->times(5);
        });

        $payload = [
            'cpf' => '123.456.789-09',
            'telefone' => '(65) 99999-0000',
            'email' => 'maria@example.com',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('migration.password.store'), $payload)->assertRedirect();
        }

        $this->post(route('migration.password.store'), $payload)->assertTooManyRequests();
    }
}

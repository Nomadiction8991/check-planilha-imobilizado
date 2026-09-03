<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\ProductVerificationItemData;
use App\Models\Legacy\Comum;
use App\Services\LegacyProductUtilityService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class LegacyProductUtilityServiceTest extends TestCase
{
    public function testPrintLabelMarksProductAsCheckedWhenSavingVerificationChecklist(): void
    {
        session(['is_admin' => true]);
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static function (callable $callback): mixed {
                return $callback();
            });

        $service = Mockery::mock(LegacyProductUtilityService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('assertChurchWithinProductScope')->once()->with(7)->andReturn(new Comum(['id' => 7, 'administracao_id' => 4]));
        $service->shouldReceive('updateLabel')
            ->once()
            ->with(19, 7, true)
            ->andReturnTrue();
        $service->shouldReceive('updateCheck')
            ->once()
            ->with(19, 7, true)
            ->andReturnTrue();
        $service->shouldReceive('updateObservation')
            ->once()
            ->with(19, 7, '')
            ->andReturnTrue();

        $processed = $service->saveVerificationChecklist(7, [
            new ProductVerificationItemData(
                productId: 19,
                printLabel: true,
                verified: false,
                observation: '',
            ),
        ]);

        $this->assertSame(1, $processed);
    }
}

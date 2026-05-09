<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Services\LegacyReportService;
use App\Services\LegacyReportTemplateService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class LegacyReportServiceTest extends TestCase
{
    public function testReport146IncludesOnlyRelevantEdits(): void
    {
        $auth = new class implements LegacyAuthSessionServiceInterface {
            public function attempt(string $email, string $password): array
            {
                return [];
            }

            public function logout(): void
            {
            }

            public function isAuthenticated(): bool
            {
                return false;
            }

            public function currentUser(): ?array
            {
                return null;
            }

            public function currentChurchId(): ?int
            {
                return null;
            }

            public function switchChurch(int $churchId): void
            {
            }

            public function currentChurch(): ?array
            {
                return null;
            }

            public function availableChurches(): Collection
            {
                return collect();
            }

            public function filterPinStates(): array
            {
                return [];
            }

            public function storeFilterPinState(string $scope, int $index, bool $pinned): void
            {
            }

            public function labelManualCodes(?int $churchId, ?int $dependencyId): array
            {
                return [];
            }

            public function saveLabelManualCodes(?int $churchId, ?int $dependencyId, array $codes): void
            {
            }
        };

        $service = new class(new LegacyReportTemplateService(), $auth) extends LegacyReportService {
            public function __construct(LegacyReportTemplateService $templates, LegacyAuthSessionServiceInterface $auth)
            {
                parent::__construct($templates, $auth);
            }

            /**
             * @param array<string, mixed> $product
             */
            public function includes(array $product): bool
            {
                return $this->hasRelevantEditForReport146($product);
            }
        };

        self::assertTrue($service->includes([
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'editado_marca' => 'TRAMONTINA',
            'editado_bem' => 'CADEIRA',
            'editado_complemento' => 'METALICA',
            'tipo_bem_id' => 4,
            'editado_tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_dependencia_id' => 2,
            'editado' => 1,
        ]));

        self::assertTrue($service->includes([
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'editado_marca' => 'TRAMONTINA',
            'editado_bem' => 'CADEIRA',
            'editado_complemento' => 'METALICA GRANDE',
            'tipo_bem_id' => 4,
            'editado_tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_dependencia_id' => 2,
            'editado' => 1,
            'observacao' => 'AJUSTE',
        ]));

        self::assertFalse($service->includes([
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'editado_bem' => 'CADEIRA',
            'editado_complemento' => 'METALICA',
            'tipo_bem_id' => 4,
            'editado_tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_dependencia_id' => 2,
            'editado' => 1,
            'checado' => 1,
            'imprimir_etiqueta' => 1,
            'observacao' => 'AJUSTE',
        ]));

        self::assertTrue($service->includes([
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'editado_marca' => 'TRAMONTINA',
            'altura_m' => '1.200',
            'largura_m' => '0.800',
            'comprimento_m' => '2.000',
            'editado_bem' => 'CADEIRA',
            'editado_complemento' => 'METALICA',
            'editado_marca' => 'TRAMONTINA',
            'editado_altura_m' => '1.300',
            'editado_largura_m' => '0.800',
            'editado_comprimento_m' => '2.000',
            'tipo_bem_id' => 4,
            'editado_tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_dependencia_id' => 2,
            'editado' => 1,
        ]));
    }
}

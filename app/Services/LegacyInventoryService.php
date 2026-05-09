<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyInventoryServiceInterface;
use App\DTO\LegacyInventorySnapshot;
use App\DTO\LegacyModuleSummary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class LegacyInventoryService implements LegacyInventoryServiceInterface
{
    public function buildSnapshot(): LegacyInventorySnapshot
    {
        $legacyRootPath = (string) config('legacy.root_path');
        $legacyPublicUrl = rtrim((string) config('legacy.public_url'), '/');

        [$databaseReachable, $databaseDriver, $databaseName, $databaseError] = $this->probeDatabase();

        return new LegacyInventorySnapshot(
            legacyRootPath: $legacyRootPath,
            legacyPublicUrl: $legacyPublicUrl,
            databaseReachable: $databaseReachable,
            databaseDriver: $databaseDriver,
            databaseName: $databaseName,
            databaseError: $databaseError,
            architectureCounts: $this->collectArchitectureCounts($legacyRootPath),
            modules: $this->collectModuleSummaries($databaseReachable),
        );
    }

    /**
     * @return array{0: bool, 1: ?string, 2: ?string, 3: ?string}
     */
    private function probeDatabase(): array
    {
        try {
            $connection = DB::connection();
            $connection->getPdo();

            return [
                true,
                $connection->getDriverName(),
                (string) $connection->getDatabaseName(),
                null,
            ];
        } catch (Throwable $throwable) {
            return [
                false,
                null,
                null,
                $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, int>
     */
    private function collectArchitectureCounts(string $legacyRootPath): array
    {
        $paths = (array) config('legacy.paths', []);
        $counts = [];

        foreach ($paths as $key => $relativePath) {
            $fullPath = rtrim($legacyRootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            $counts[$key] = is_dir($fullPath) ? count(File::allFiles($fullPath)) : 0;
        }

        return $counts;
    }

    /**
     * @return array<int, LegacyModuleSummary>
     */
    private function collectModuleSummaries(bool $databaseReachable): array
    {
        $modules = [];

        foreach ((array) config('legacy.modules', []) as $module) {
            $records = null;

            if ($databaseReachable && isset($module['model']) && is_string($module['model'])) {
                $records = $this->countModelRecords($module['model'], $module['scope'] ?? null);
            }

            $modules[] = new LegacyModuleSummary(
                key: (string) $module['key'],
                title: (string) $module['title'],
                description: (string) $module['description'],
                category: (string) ($module['category'] ?? 'Estrutura'),
                tone: (string) ($module['tone'] ?? 'structure'),
                legacyPath: (string) $module['legacy_path'],
                target: (string) $module['target'],
                records: $records,
            );
        }

        return $modules;
    }

    private function countModelRecords(string $modelClass, ?string $scope): ?int
    {
        if (!is_a($modelClass, Model::class, true)) {
            return null;
        }

        try {
            $query = $modelClass::query();

            if ($scope !== null && method_exists($query->getModel(), 'scope' . ucfirst($scope))) {
                $query->{$scope}();
            }

            return $query->count();
        } catch (Throwable) {
            return null;
        }
    }
}

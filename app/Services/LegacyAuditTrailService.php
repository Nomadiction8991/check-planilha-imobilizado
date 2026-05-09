<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\DTO\LegacyAuditEntryData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

final class LegacyAuditTrailService implements LegacyAuditTrailServiceInterface
{
    private string $storageFile;

    public function __construct(?string $storageFile = null)
    {
        $configuredFile = $storageFile ?? (string) config(
            'legacy.audit.storage_file',
            storage_path('app/private/audits/audit-log.jsonl')
        );

        $this->storageFile = $this->resolveWritableStorageFile($configuredFile);
    }

    public function record(LegacyAuditEntryData $entry): void
    {
        try {
            $directory = dirname($this->storageFile);

            if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
                return;
            }

            $payload = json_encode($entry->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                return;
            }

            file_put_contents($this->storageFile, $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @param array<string, string> $filters
     * @param array<string, mixed> $query
     */
    public function paginate(
        array $filters,
        ?int $userId,
        ?int $administrationId,
        ?int $churchId,
        bool $isAdmin,
        string $path,
        array $query = [],
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $module = mb_strtolower(trim((string) ($filters['module'] ?? '')), 'UTF-8');
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')), 'UTF-8');
        $dateFrom = $this->parseDateBoundary((string) ($filters['date_from'] ?? ''), false);
        $dateTo = $this->parseDateBoundary((string) ($filters['date_to'] ?? ''), true);

        $entries = collect($this->readEntries())
            ->filter(static function (LegacyAuditEntryData $entry) use ($userId, $administrationId, $churchId, $isAdmin): bool {
                if ($isAdmin) {
                    return true;
                }

                if ($administrationId !== null && $administrationId > 0) {
                    return $entry->administrationId === $administrationId;
                }

                if ($churchId !== null && $churchId > 0) {
                    return $entry->churchId === $churchId;
                }

                if ($userId !== null && $userId > 0) {
                    return $entry->userId === $userId;
                }

                return true;
            })
            ->filter(static function (LegacyAuditEntryData $entry) use ($module): bool {
                if ($module === '') {
                    return true;
                }

                return mb_strtolower($entry->module, 'UTF-8') === $module;
            })
            ->filter(static function (LegacyAuditEntryData $entry) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(
                    implode(' ', array_filter([
                        $entry->module,
                        $entry->action,
                        $entry->description,
                        $entry->routeName,
                        $entry->path,
                        $entry->method,
                        $entry->userName,
                        $entry->userEmail,
                    ], static fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')),
                    'UTF-8'
                );

                return str_contains($haystack, $search);
            })
            ->filter(function (LegacyAuditEntryData $entry) use ($dateFrom, $dateTo): bool {
                $entryDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $entry->occurredAt);

                if ($dateFrom !== null && $entryDate->lt($dateFrom)) {
                    return false;
                }

                if ($dateTo !== null && $entryDate->gt($dateTo)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(static fn (LegacyAuditEntryData $entry): string => $entry->occurredAt)
            ->values();

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $items = $entries->forPage($page, $perPage)->values()->all();

        return new LengthAwarePaginator(
            $items,
            $entries->count(),
            $perPage,
            $page,
            [
                'path' => $path,
                'query' => $query,
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    public function availableModules(): array
    {
        $modules = (array) config('legacy.audit.modules', []);

        $modules = array_values(array_filter(array_map(
            static fn (mixed $module): string => trim((string) $module),
            $modules
        )));

        return array_values(array_unique($modules));
    }

    /**
     * @return array<int, LegacyAuditEntryData>
     */
    private function readEntries(): array
    {
        if (!is_file($this->storageFile) || !is_readable($this->storageFile)) {
            return [];
        }

        $lines = file($this->storageFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $entries = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            $entries[] = LegacyAuditEntryData::fromArray($decoded);
        }

        return $entries;
    }

    private function parseDateBoundary(string $value, bool $endOfDay): ?\Carbon\Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function resolveWritableStorageFile(string $storageFile): string
    {
        $storageFile = trim($storageFile);

        if ($storageFile !== '' && $this->isWritableFilePath($storageFile)) {
            return $storageFile;
        }

        $fallbackFile = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'check-planilha-imobilizado'
            . DIRECTORY_SEPARATOR
            . 'audits'
            . DIRECTORY_SEPARATOR
            . 'audit-log.jsonl';

        if ($this->isWritableFilePath($fallbackFile)) {
            return $fallbackFile;
        }

        return $storageFile !== '' ? $storageFile : $fallbackFile;
    }

    private function isWritableFilePath(string $file): bool
    {
        $directory = dirname($file);

        if (is_file($file)) {
            return is_writable($file);
        }

        if (is_dir($directory)) {
            return is_writable($directory);
        }

        $parent = dirname($directory);

        return is_dir($parent) && is_writable($parent);
    }
}

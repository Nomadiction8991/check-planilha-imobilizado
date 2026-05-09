<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyAuditEntryData
{
    public function __construct(
        public string $occurredAt,
        public ?int $userId,
        public string $userName,
        public ?string $userEmail,
        public ?int $administrationId,
        public ?int $churchId,
        public bool $isAdmin,
        public string $module,
        public string $action,
        public string $description,
        public ?string $routeName,
        public string $path,
        public string $method,
        public int $statusCode,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            occurredAt: (string) ($data['occurred_at'] ?? now()->format('Y-m-d H:i:s')),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            userName: (string) ($data['user_name'] ?? 'Sistema'),
            userEmail: isset($data['user_email']) ? (string) $data['user_email'] : null,
            administrationId: isset($data['administration_id']) ? (int) $data['administration_id'] : null,
            churchId: isset($data['church_id']) ? (int) $data['church_id'] : null,
            isAdmin: (bool) ($data['is_admin'] ?? false),
            module: (string) ($data['module'] ?? 'Sistema'),
            action: (string) ($data['action'] ?? 'Ação'),
            description: (string) ($data['description'] ?? ''),
            routeName: isset($data['route_name']) ? (string) $data['route_name'] : null,
            path: (string) ($data['path'] ?? ''),
            method: (string) ($data['method'] ?? 'GET'),
            statusCode: isset($data['status_code']) ? (int) $data['status_code'] : 200,
            ipAddress: isset($data['ip_address']) ? (string) $data['ip_address'] : null,
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'occurred_at' => $this->occurredAt,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_email' => $this->userEmail,
            'administration_id' => $this->administrationId,
            'church_id' => $this->churchId,
            'is_admin' => $this->isAdmin,
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description,
            'route_name' => $this->routeName,
            'path' => $this->path,
            'method' => $this->method,
            'status_code' => $this->statusCode,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}

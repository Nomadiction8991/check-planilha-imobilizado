<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyMailConfigurationData
{
    public function __construct(
        public string $host,
        public int $port,
        public string $scheme,
        public string $username,
        public ?string $password,
        public string $fromAddress,
        public string $fromName,
    ) {
    }

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     scheme: string,
     *     username: string,
     *     password: ?string,
     *     fromAddress: string,
     *     fromName: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            host: $data['host'],
            port: $data['port'],
            scheme: $data['scheme'],
            username: $data['username'],
            password: $data['password'],
            fromAddress: $data['fromAddress'],
            fromName: $data['fromName'],
        );
    }

    /**
     * @return array{
     *     host: string,
     *     port: int,
     *     scheme: string,
     *     username: string,
     *     password: ?string,
     *     fromAddress: string,
     *     fromName: string
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'scheme' => $this->scheme,
            'username' => $this->username,
            'password' => $this->password,
            'fromAddress' => $this->fromAddress,
            'fromName' => $this->fromName,
        ];
    }
}

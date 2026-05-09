<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyMailConfigurationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class LegacyMailConfigurationService implements LegacyMailConfigurationServiceInterface
{
    public function current(): LegacyMailConfigurationData
    {
        $record = DB::table('configuracoes')->first();

        if ($record === null) {
            return LegacyMailConfigurationData::fromArray([
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'scheme' => 'tls',
                'username' => '',
                'password' => null,
                'fromAddress' => '',
                'fromName' => (string) config('app.name'),
            ]);
        }

        return LegacyMailConfigurationData::fromArray([
            'host' => trim((string) ($record->mail_host ?? 'smtp.gmail.com')),
            'port' => (int) ($record->mail_port ?? 587),
            'scheme' => trim((string) ($record->mail_scheme ?? 'tls')),
            'username' => trim((string) ($record->mail_username ?? '')),
            'password' => null,
            'fromAddress' => trim((string) ($record->mail_from_address ?? '')),
            'fromName' => trim((string) ($record->mail_from_name ?? config('app.name'))),
        ]);
    }

    public function save(LegacyMailConfigurationData $data): void
    {
        $existing = DB::table('configuracoes')->first();
        $password = $this->resolvePasswordForSave($data->password, $existing?->mail_password ?? null);

        $payload = [
            'mail_host' => trim($data->host),
            'mail_port' => $data->port,
            'mail_scheme' => trim($data->scheme),
            'mail_username' => trim($data->username),
            'mail_password' => $password,
            'mail_from_address' => trim($data->fromAddress),
            'mail_from_name' => trim($data->fromName),
        ];

        if ($existing === null) {
            DB::table('configuracoes')->insert($payload);
            return;
        }

        DB::table('configuracoes')->update($payload);
    }

    public function configureRuntimeMailer(): void
    {
        $settings = $this->current();
        $record = DB::table('configuracoes')->first();

        if ($settings->host === '' || $settings->username === '' || $settings->fromAddress === '') {
            throw new RuntimeException('Configure o e-mail do Google antes de enviar mensagens.');
        }

        $password = $this->resolvePasswordForRuntime($record?->mail_password ?? null);

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.scheme' => in_array($settings->scheme, ['tls', 'ssl'], true) ? $settings->scheme : null,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $settings->fromAddress,
            'mail.from.name' => $settings->fromName !== '' ? $settings->fromName : config('app.name'),
        ]);
    }

    private function resolvePasswordForSave(?string $password, mixed $existingPassword): string
    {
        $normalizedPassword = trim((string) ($password ?? ''));

        if ($normalizedPassword !== '') {
            return Crypt::encryptString($normalizedPassword);
        }

        if (is_string($existingPassword) && $existingPassword !== '') {
            return $existingPassword;
        }

        throw new RuntimeException('Informe a senha do aplicativo do Google.');
    }

    private function resolvePasswordForRuntime(mixed $password): string
    {
        if (!is_string($password) || $password === '') {
            throw new RuntimeException('Informe a senha do aplicativo do Google.');
        }

        try {
            return Crypt::decryptString($password);
        } catch (Throwable) {
            return $password;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyBrandingServiceInterface;
use App\DTO\LegacyBrandingData;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class LegacyBrandingService implements LegacyBrandingServiceInterface
{
    private ?object $registroCache = null;

    private bool $registroLido = false;

    private function registro(): ?object
    {
        if ($this->registroLido) {
            return $this->registroCache;
        }

        $this->registroLido = true;
        try {
            $this->registroCache = DB::table('configuracoes')->select(['app_name', 'app_logo_hash'])->first();
        } catch (Throwable) {
            $this->registroCache = null;
        }

        return $this->registroCache;
    }

    public function nome(): string
    {
        $nome = trim((string) ($this->registro()?->app_name ?? ''));

        return $nome !== '' ? $nome : (string) config('app.name');
    }

    public function logo(): array
    {
        try {
            $registro = DB::table('configuracoes')->select(['app_logo', 'app_logo_mime'])->first();
        } catch (Throwable) {
            $registro = null;
        }
        $conteudo = base64_decode((string) ($registro?->app_logo ?? ''), true);
        $mime = $registro?->app_logo_mime ?? null;
        if ($conteudo !== false && $conteudo !== '' && is_string($mime) && $mime !== '') {
            return ['conteudo' => $conteudo, 'mime' => $mime];
        }

        return ['conteudo' => (string) file_get_contents(public_path('icons/pwa-192.png')), 'mime' => 'image/png'];
    }

    public function versao(): string
    {
        return (string) (($this->registro()?->app_logo_hash ?? null) ?: 'padrao');
    }

    public function salvar(LegacyBrandingData $data): void
    {
        $payload = ['app_name' => trim($data->nome)];
        if ($data->conteudo !== null && $data->mime !== null) {
            $payload['app_logo'] = base64_encode($data->conteudo);
            $payload['app_logo_mime'] = $data->mime;
            $payload['app_logo_hash'] = md5($data->conteudo);
        }

        try {
            if (DB::table('configuracoes')->exists()) {
                DB::table('configuracoes')->update($payload);
            } else {
                DB::table('configuracoes')->insert($payload);
            }
            $this->registroCache = null;
            $this->registroLido = false;
        } catch (Throwable $exception) {
            throw new RuntimeException('Não foi possível salvar o nome e a logo.', 0, $exception);
        }
    }
}

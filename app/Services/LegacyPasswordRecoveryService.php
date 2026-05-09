<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyMailConfigurationServiceInterface;
use App\Contracts\LegacyPasswordRecoveryServiceInterface;
use App\Mail\LegacyPasswordResetMail;
use App\Models\Legacy\Usuario;
use App\Support\LegacyCpfValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class LegacyPasswordRecoveryService implements LegacyPasswordRecoveryServiceInterface
{
    public function __construct(
        private readonly LegacyMailConfigurationServiceInterface $mailConfiguration,
    ) {
    }

    public function recover(string $cpf, string $phone, string $email): void
    {
        $normalizedCpf = $this->digitsOnly($cpf);
        $normalizedPhone = $this->digitsOnly($phone);
        $normalizedEmail = mb_strtoupper(trim($email), 'UTF-8');

        if ($normalizedCpf === '' || !LegacyCpfValidator::isValid($normalizedCpf)) {
            throw new RuntimeException('Dados não conferem com o cadastro informado.');
        }

        /** @var Usuario|null $user */
        $user = Usuario::query()
            ->active()
            ->whereRaw('UPPER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user === null) {
            throw new RuntimeException('Dados não conferem com o cadastro informado.');
        }

        if ($this->digitsOnly((string) $user->cpf) !== $normalizedCpf || $this->digitsOnly((string) $user->telefone) !== $normalizedPhone) {
            throw new RuntimeException('Dados não conferem com o cadastro informado.');
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $originalPassword = (string) $user->senha;

        $user->forceFill([
            'senha' => Hash::make($temporaryPassword),
        ])->save();

        try {
            $this->mailConfiguration->configureRuntimeMailer();
            Mail::mailer('smtp')->to((string) $user->email)->send(
                new LegacyPasswordResetMail(
                    recipientName: (string) $user->nome,
                    temporaryPassword: $temporaryPassword,
                )
            );
        } catch (Throwable $exception) {
            $user->forceFill(['senha' => $originalPassword])->save();

            throw new RuntimeException(
                'Não foi possível enviar a nova senha agora. Tente novamente mais tarde.',
                previous: $exception
            );
        }
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(bin2hex(random_bytes(6)));
    }

    private function digitsOnly(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) ? $digits : '';
    }
}

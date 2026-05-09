<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLegacyConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        $menuKeys = app(LegacyNavigationServiceInterface::class)->availableKeys();

        return [
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_scheme' => ['required', 'string', Rule::in(['tls', 'ssl', 'null'])],
            'mail_username' => ['required', 'email:rfc', 'max:255'],
            'mail_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'mail_from_address' => ['required', 'email:rfc', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'menu_order' => ['required', 'array', 'min:1'],
            'menu_order.*' => ['string', Rule::in($menuKeys)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_host.required' => 'Informe o host SMTP.',
            'mail_port.required' => 'Informe a porta SMTP.',
            'mail_scheme.required' => 'Selecione o tipo de conexão.',
            'mail_username.required' => 'Informe o e-mail do Google.',
            'mail_username.email' => 'Informe um e-mail válido.',
            'mail_password.min' => 'A senha precisa ter ao menos 6 caracteres.',
            'mail_from_address.required' => 'Informe o e-mail de remetente.',
            'mail_from_address.email' => 'Informe um e-mail de remetente válido.',
            'mail_from_name.required' => 'Informe o nome do remetente.',
            'menu_order.required' => 'Organize a ordem do menu antes de salvar.',
            'menu_order.array' => 'A ordem do menu está inválida.',
        ];
    }

    public function toDto(): LegacyMailConfigurationData
    {
        return LegacyMailConfigurationData::fromArray([
            'host' => trim((string) $this->validated('mail_host')),
            'port' => (int) $this->validated('mail_port'),
            'scheme' => trim((string) $this->validated('mail_scheme')),
            'username' => trim((string) $this->validated('mail_username')),
            'password' => trim((string) ($this->validated('mail_password') ?? '')) ?: null,
            'fromAddress' => trim((string) $this->validated('mail_from_address')),
            'fromName' => trim((string) $this->validated('mail_from_name')),
        ]);
    }

    public function toMenuOrderDto(): LegacyNavigationOrderData
    {
        /** @var array<int, string> $menuOrder */
        $menuOrder = array_values(array_filter(
            (array) $this->validated('menu_order'),
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));

        return LegacyNavigationOrderData::fromArray([
            'items' => $menuOrder,
        ]);
    }
}

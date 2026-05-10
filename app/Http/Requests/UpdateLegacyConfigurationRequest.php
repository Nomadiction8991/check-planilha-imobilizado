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
        $section = $this->section();
        $mailSection = $section !== 'menu';
        $menuSection = $section !== 'mail';

        return [
            'config_section' => ['required', Rule::in(['mail', 'menu'])],
            'mail_host' => $mailSection ? ['required', 'string', 'max:255'] : ['nullable'],
            'mail_port' => $mailSection ? ['required', 'integer', 'between:1,65535'] : ['nullable', 'integer', 'between:1,65535'],
            'mail_scheme' => $mailSection ? ['required', 'string', Rule::in(['tls', 'ssl', 'null'])] : ['nullable', 'string', Rule::in(['tls', 'ssl', 'null'])],
            'mail_username' => $mailSection ? ['required', 'email:rfc', 'max:255'] : ['nullable', 'email:rfc', 'max:255'],
            'mail_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'mail_from_address' => $mailSection ? ['required', 'email:rfc', 'max:255'] : ['nullable', 'email:rfc', 'max:255'],
            'mail_from_name' => $mailSection ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'menu_order' => $menuSection ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
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
        $values = $this->mailValues();

        return LegacyMailConfigurationData::fromArray([
            'host' => trim((string) $values['mail_host']),
            'port' => (int) $values['mail_port'],
            'scheme' => trim((string) $values['mail_scheme']),
            'username' => trim((string) $values['mail_username']),
            'password' => trim((string) ($values['mail_password'] ?? '')) ?: null,
            'fromAddress' => trim((string) $values['mail_from_address']),
            'fromName' => trim((string) $values['mail_from_name']),
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

    public function section(): string
    {
        $section = trim((string) $this->input('config_section', ''));

        return in_array($section, ['mail', 'menu'], true) ? $section : '';
    }

    /**
     * @return array{mail_host: mixed, mail_port: mixed, mail_scheme: mixed, mail_username: mixed, mail_password: mixed, mail_from_address: mixed, mail_from_name: mixed}
     */
    private function mailValues(): array
    {
        return [
            'mail_host' => $this->validated('mail_host'),
            'mail_port' => $this->validated('mail_port'),
            'mail_scheme' => $this->validated('mail_scheme'),
            'mail_username' => $this->validated('mail_username'),
            'mail_password' => $this->validated('mail_password'),
            'mail_from_address' => $this->validated('mail_from_address'),
            'mail_from_name' => $this->validated('mail_from_name'),
        ];
    }
}

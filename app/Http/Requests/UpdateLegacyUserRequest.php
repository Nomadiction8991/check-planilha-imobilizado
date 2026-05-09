<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\UserMutationData;
use App\Support\LegacyCpfValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLegacyUserRequest extends FormRequest
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
        return [
            'administracao_id' => ['required', 'integer', 'min:1'],
            'administracoes_permitidas' => ['nullable', 'array'],
            'administracoes_permitidas.*' => ['distinct', 'integer', 'min:1'],
            'nome' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'cpf' => ['required', 'string', 'max:20'],
            'rg' => ['required', 'string', 'max:30'],
            'telefone' => ['required', 'string', 'max:25'],
            'senha' => ['nullable', 'string', 'min:6', 'same:confirmar_senha'],
            'confirmar_senha' => ['nullable', 'string', 'min:6'],
            'nome_conjuge' => ['nullable', 'string', 'max:255'],
            'cpf_conjuge' => ['nullable', 'string', 'max:20'],
            'rg_conjuge' => ['nullable', 'string', 'max:30'],
            'telefone_conjuge' => ['nullable', 'string', 'max:25'],
            'endereco_cep' => ['nullable', 'string', 'max:20'],
            'endereco_logradouro' => ['nullable', 'string', 'max:255'],
            'endereco_numero' => ['nullable', 'string', 'max:50'],
            'endereco_complemento' => ['nullable', 'string', 'max:255'],
            'endereco_bairro' => ['nullable', 'string', 'max:255'],
            'endereco_cidade' => ['nullable', 'string', 'max:255'],
            'endereco_estado' => ['nullable', 'string', 'size:2'],
            'ativo' => ['nullable', 'boolean'],
            'casado' => ['nullable', 'boolean'],
            'rg_igual_cpf' => ['nullable', 'boolean'],
            'rg_conjuge_igual_cpf' => ['nullable', 'boolean'],
            'permissions_present' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys((array) config('legacy.permissions.defaults', [])))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'administracao_id.required' => 'Selecione a administração do usuário.',
            'administracao_id.min' => 'Selecione uma administração válida.',
            'nome.required' => 'O nome é obrigatório.',
            'nome.regex' => 'O nome não pode conter apenas espaços.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'E-mail inválido.',
            'cpf.required' => 'O CPF é obrigatório.',
            'rg.required' => 'O RG é obrigatório.',
            'telefone.required' => 'O telefone é obrigatório.',
            'senha.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'senha.same' => 'As senhas não conferem.',
            'endereco_estado.size' => 'Informe uma UF válida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateCpfField($validator, 'cpf', 'CPF inválido.');
            $this->validateRgField($validator, 'rg', 'O RG é obrigatório e deve ter ao menos 2 dígitos.');
            $this->validatePhoneField($validator, 'telefone', 'Telefone inválido.');

            if ($this->boolean('casado')) {
                if (trim((string) $this->input('nome_conjuge', '')) === '') {
                    $validator->errors()->add('nome_conjuge', 'O nome do cônjuge é obrigatório.');
                }

                if (trim((string) $this->input('cpf_conjuge', '')) === '') {
                    $validator->errors()->add('cpf_conjuge', 'O CPF do cônjuge é obrigatório.');
                } else {
                    $this->validateCpfField($validator, 'cpf_conjuge', 'CPF do cônjuge inválido. Deve conter 11 dígitos.');
                }

                if (trim((string) $this->input('telefone_conjuge', '')) === '') {
                    $validator->errors()->add('telefone_conjuge', 'O telefone do cônjuge é obrigatório.');
                } else {
                    $this->validatePhoneField($validator, 'telefone_conjuge', 'Telefone do cônjuge inválido. Deve conter 10 ou 11 dígitos.');
                }
            }
        });
    }

    public function toDto(): UserMutationData
    {
        $password = trim((string) ($this->validated('senha') ?? ''));
        $administrationIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) $this->validated('administracoes_permitidas'),
        ), static fn (int $value): bool => $value > 0)));

        return new UserMutationData(
            administrationId: (int) $this->validated('administracao_id'),
            administrationIds: $administrationIds,
            name: trim((string) $this->validated('nome')),
            email: trim((string) $this->validated('email')),
            active: $this->boolean('ativo'),
            cpf: trim((string) $this->validated('cpf')),
            rg: trim((string) $this->validated('rg')),
            rgEqualsCpf: $this->boolean('rg_igual_cpf'),
            phone: trim((string) $this->validated('telefone')),
            married: $this->boolean('casado'),
            spouseName: trim((string) ($this->validated('nome_conjuge') ?? '')),
            spouseCpf: trim((string) ($this->validated('cpf_conjuge') ?? '')),
            spouseRg: trim((string) ($this->validated('rg_conjuge') ?? '')),
            spouseRgEqualsCpf: $this->boolean('rg_conjuge_igual_cpf'),
            spousePhone: trim((string) ($this->validated('telefone_conjuge') ?? '')),
            addressZip: trim((string) ($this->validated('endereco_cep') ?? '')),
            addressStreet: trim((string) ($this->validated('endereco_logradouro') ?? '')),
            addressNumber: trim((string) ($this->validated('endereco_numero') ?? '')),
            addressComplement: trim((string) ($this->validated('endereco_complemento') ?? '')),
            addressDistrict: trim((string) ($this->validated('endereco_bairro') ?? '')),
            addressCity: trim((string) ($this->validated('endereco_cidade') ?? '')),
            addressState: trim((string) ($this->validated('endereco_estado') ?? '')),
            permissions: array_values(array_filter((array) ($this->validated('permissions') ?? []), static fn (mixed $value): bool => is_string($value) && $value !== '')),
            permissionsProvided: $this->boolean('permissions_present'),
            password: $password !== '' ? $password : null,
        );
    }

    private function validateCpfField(Validator $validator, string $field, string $message): void
    {
        $value = trim((string) $this->input($field, ''));

        if ($value === '' || !LegacyCpfValidator::isValid($value)) {
            $validator->errors()->add($field, $message);
        }
    }

    private function validateRgField(Validator $validator, string $field, string $message): void
    {
        $digits = preg_replace('/\D/', '', (string) $this->input($field, ''));
        $digits = is_string($digits) ? $digits : '';

        if (strlen($digits) < 2) {
            $validator->errors()->add($field, $message);
        }
    }

    private function validatePhoneField(Validator $validator, string $field, string $message): void
    {
        $digits = preg_replace('/\D/', '', (string) $this->input($field, ''));
        $digits = is_string($digits) ? $digits : '';

        if (strlen($digits) < 10 || strlen($digits) > 11) {
            $validator->errors()->add($field, $message);
        }
    }
}

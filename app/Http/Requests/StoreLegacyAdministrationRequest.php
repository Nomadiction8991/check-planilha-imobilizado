<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\AdministrationMutationData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegacyAdministrationRequest extends FormRequest
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
            'descricao' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'cnpj' => ['required', 'string', 'max:30', 'regex:/\S/'],
            'estado' => ['required', 'string', Rule::in(array_keys((array) config('brazil.states', [])))],
            'cidade' => ['required', 'string', 'max:255', 'regex:/\S/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'descricao.regex' => 'A descrição não pode conter apenas espaços.',
            'cnpj.required' => 'O CNPJ da administração é obrigatório.',
            'cnpj.max' => 'O CNPJ da administração deve ter no máximo 30 caracteres.',
            'cnpj.regex' => 'O CNPJ da administração não pode conter apenas espaços.',
            'estado.required' => 'O estado da administração é obrigatório.',
            'estado.in' => 'Selecione um estado válido para a administração.',
            'cidade.required' => 'A cidade da administração é obrigatória.',
            'cidade.regex' => 'A cidade da administração não pode conter apenas espaços.',
        ];
    }

    public function toDto(): AdministrationMutationData
    {
        return new AdministrationMutationData(
            description: trim((string) $this->validated('descricao')),
            cnpj: trim((string) $this->validated('cnpj')),
            state: trim((string) $this->validated('estado')),
            city: trim((string) $this->validated('cidade')),
        );
    }
}

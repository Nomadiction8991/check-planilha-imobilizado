<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\DepartmentMutationData;
use Illuminate\Foundation\Http\FormRequest;

class StoreLegacyDepartmentRequest extends FormRequest
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
            'comum_id' => ['required', 'integer', 'min:1'],
            'descricao' => ['required', 'string', 'max:255', 'regex:/\S/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comum_id.required' => 'A igreja é obrigatória.',
            'comum_id.min' => 'Selecione uma igreja válida.',
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'descricao.regex' => 'A descrição não pode conter apenas espaços.',
        ];
    }

    public function toDto(): DepartmentMutationData
    {
        return new DepartmentMutationData(
            churchId: (int) $this->validated('comum_id'),
            description: trim((string) $this->validated('descricao')),
        );
    }
}

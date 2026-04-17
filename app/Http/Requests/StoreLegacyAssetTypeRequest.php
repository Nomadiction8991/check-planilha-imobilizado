<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\AssetTypeMutationData;
use Illuminate\Foundation\Http\FormRequest;

class StoreLegacyAssetTypeRequest extends FormRequest
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
        ];
    }

    public function toDto(): AssetTypeMutationData
    {
        return new AssetTypeMutationData(
            description: trim((string) $this->validated('descricao')),
        );
    }
}

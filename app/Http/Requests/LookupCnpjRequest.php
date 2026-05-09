<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\CnpjLookupData;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LookupCnpjRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', 'regex:/^\d{14}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.regex' => 'O CNPJ deve conter exatamente 14 dígitos.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => 'CNPJ inválido',
        ], 400));
    }

    public function toDto(): CnpjLookupData
    {
        return new CnpjLookupData(
            cnpj: (string) $this->validated('cnpj'),
        );
    }
}
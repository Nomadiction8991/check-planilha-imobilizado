<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\CreateLegacyProductData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegacyProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $requiresInvoice = in_array($this->normalizedCondition(), ['1', '3'], true);

        return [
            'comum_id' => ['required', 'integer', 'min:1'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'id_tipo_ben' => ['required', 'integer', 'min:1'],
            'tipo_ben' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'complemento' => ['required', 'string', 'regex:/\S/'],
            'id_dependencia' => ['required', 'integer', 'min:1'],
            'multiplicador' => ['required', 'integer', 'min:1'],
            'condicao_14_1' => ['nullable', 'string', Rule::in(['1', '2', '3'])],
            'nota_numero' => [$requiresInvoice ? 'required' : 'nullable', 'integer', 'min:1'],
            'nota_data' => [$requiresInvoice ? 'required' : 'nullable', 'date'],
            'nota_valor' => [$requiresInvoice ? 'required' : 'nullable', 'string', 'max:255', 'regex:/\S/'],
            'nota_fornecedor' => [$requiresInvoice ? 'required' : 'nullable', 'string', 'max:255', 'regex:/\S/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comum_id.required' => 'A igreja é obrigatória.',
            'id_tipo_ben.required' => 'Selecione um tipo de bem.',
            'tipo_ben.required' => 'Selecione um bem.',
            'tipo_ben.regex' => 'Selecione um bem válido.',
            'complemento.required' => 'Informe o complemento.',
            'complemento.regex' => 'Informe o complemento.',
            'id_dependencia.required' => 'Selecione uma dependência.',
            'multiplicador.required' => 'Informe o multiplicador.',
            'multiplicador.min' => 'O multiplicador deve ser pelo menos 1.',
            'nota_numero.required' => 'Informe o número da nota fiscal.',
            'nota_data.required' => 'Informe a data da nota fiscal.',
            'nota_valor.required' => 'Informe o valor da nota fiscal.',
            'nota_valor.regex' => 'Informe o valor da nota fiscal.',
            'nota_fornecedor.required' => 'Informe o fornecedor da nota fiscal.',
            'nota_fornecedor.regex' => 'Informe o fornecedor da nota fiscal.',
        ];
    }

    public function toDto(): CreateLegacyProductData
    {
        $code = trim((string) ($this->validated('codigo') ?? ''));
        $invoiceValue = trim((string) ($this->validated('nota_valor') ?? ''));
        $invoiceSupplier = trim((string) ($this->validated('nota_fornecedor') ?? ''));

        return new CreateLegacyProductData(
            churchId: (int) $this->validated('comum_id'),
            code: $code !== '' ? $code : null,
            assetTypeId: (int) $this->validated('id_tipo_ben'),
            itemName: trim((string) $this->validated('tipo_ben')),
            complement: trim((string) $this->validated('complemento')),
            dependencyId: (int) $this->validated('id_dependencia'),
            multiplier: (int) $this->validated('multiplicador'),
            printReport141: $this->boolean('imprimir_14_1'),
            condition141: $this->normalizedCondition(),
            invoiceNumber: $this->filled('nota_numero') ? (int) $this->validated('nota_numero') : null,
            invoiceDate: $this->filled('nota_data') ? (string) $this->validated('nota_data') : null,
            invoiceValue: $invoiceValue !== '' ? $invoiceValue : null,
            invoiceSupplier: $invoiceSupplier !== '' ? $invoiceSupplier : null,
        );
    }

    private function normalizedCondition(): string
    {
        $condition = trim((string) $this->input('condicao_14_1', '2'));

        return in_array($condition, ['1', '2', '3'], true) ? $condition : '2';
    }
}

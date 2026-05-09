<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\UpdateLegacyProductData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegacyProductRequest extends FormRequest
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
            'novo_tipo_bem_id' => ['required', 'integer', 'min:1'],
            'novo_bem' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'novo_complemento' => ['required', 'string', 'regex:/\S/'],
            'novo_marca' => ['nullable', 'string', 'max:255'],
            'altura_m' => ['nullable', 'numeric', 'min:0'],
            'largura_m' => ['nullable', 'numeric', 'min:0'],
            'comprimento_m' => ['nullable', 'numeric', 'min:0'],
            'nova_dependencia_id' => ['required', 'integer', 'min:1'],
            'verificado' => ['nullable', 'boolean'],
            'imprimir_etiqueta' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:255'],
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
            'novo_tipo_bem_id.required' => 'Selecione um tipo de bem.',
            'novo_bem.required' => 'Selecione um bem.',
            'novo_bem.regex' => 'Selecione um bem válido.',
            'novo_complemento.required' => 'Informe o complemento.',
            'novo_complemento.regex' => 'Informe o complemento.',
            'novo_marca.max' => 'A marca deve ter no máximo 255 caracteres.',
            'nova_dependencia_id.required' => 'Selecione uma dependência.',
            'verificado.boolean' => 'A seleção de verificação é inválida.',
            'imprimir_etiqueta.boolean' => 'A seleção para impressão é inválida.',
            'observacao.max' => 'A observação deve ter no máximo 255 caracteres.',
            'nota_numero.required' => 'Informe o número da nota fiscal.',
            'nota_data.required' => 'Informe a data da nota fiscal.',
            'nota_valor.required' => 'Informe o valor da nota fiscal.',
            'nota_valor.regex' => 'Informe o valor da nota fiscal.',
            'nota_fornecedor.required' => 'Informe o fornecedor da nota fiscal.',
            'nota_fornecedor.regex' => 'Informe o fornecedor da nota fiscal.',
        ];
    }

    public function toDto(): UpdateLegacyProductData
    {
        $invoiceValue = trim((string) ($this->validated('nota_valor') ?? ''));
        $invoiceSupplier = trim((string) ($this->validated('nota_fornecedor') ?? ''));

        return new UpdateLegacyProductData(
            editedAssetTypeId: (int) $this->validated('novo_tipo_bem_id'),
            editedItemName: trim((string) $this->validated('novo_bem')),
            editedComplement: trim((string) $this->validated('novo_complemento')),
            editedBrand: $this->filled('novo_marca') ? trim((string) $this->validated('novo_marca')) : null,
            editedHeightMeters: $this->filled('altura_m') ? (string) $this->validated('altura_m') : null,
            editedWidthMeters: $this->filled('largura_m') ? (string) $this->validated('largura_m') : null,
            editedLengthMeters: $this->filled('comprimento_m') ? (string) $this->validated('comprimento_m') : null,
            editedDependencyId: (int) $this->validated('nova_dependencia_id'),
            verified: $this->boolean('verificado'),
            printLabel: $this->boolean('imprimir_etiqueta'),
            observation: trim((string) ($this->validated('observacao') ?? '')),
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

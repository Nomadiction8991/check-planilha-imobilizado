<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ProductVerificationItemData;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVerificationRequest extends FormRequest
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
        return [
            'comum_id' => ['required', 'integer', 'min:1'],
            'pagina' => ['nullable', 'integer', 'min:1'],
            'busca' => ['nullable', 'string', 'max:255'],
            'nome' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:255'],
            'dependencia_id' => ['nullable', 'integer', 'min:1'],
            'tipo_bem_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'somente_novos' => ['nullable', 'boolean'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer', 'min:1'],
            'itens.*.imprimir_etiqueta' => ['nullable', 'boolean'],
            'itens.*.verificado' => ['nullable', 'boolean'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comum_id.required' => 'Selecione uma igreja para salvar o checklist.',
            'itens.required' => 'Inclua ao menos um produto no checklist.',
            'itens.array' => 'O checklist de produtos é inválido.',
            'itens.min' => 'Inclua ao menos um produto no checklist.',
            'itens.*.produto_id.required' => 'Um item do checklist está sem produto vinculado.',
            'itens.*.produto_id.integer' => 'Um item do checklist está com produto inválido.',
            'itens.*.imprimir_etiqueta.boolean' => 'A seleção para impressão é inválida.',
            'itens.*.verificado.boolean' => 'A seleção de verificação é inválida.',
            'itens.*.observacao.max' => 'A observação de cada produto deve ter no máximo 255 caracteres.',
        ];
    }

    /**
     * @return list<ProductVerificationItemData>
     */
    public function toItems(): array
    {
        $items = [];
        $validatedItems = (array) $this->validated('itens', []);

        foreach ($validatedItems as $item) {
            $items[] = new ProductVerificationItemData(
                productId: (int) ($item['produto_id'] ?? 0),
                printLabel: $this->toBoolean($item['imprimir_etiqueta'] ?? false),
                verified: $this->toBoolean($item['verificado'] ?? false),
                observation: trim((string) ($item['observacao'] ?? '')),
            );
        }

        return $items;
    }

    /**
     * @return array<string, scalar>
     */
    public function toReturnQuery(): array
    {
        $search = trim((string) $this->input('busca', $this->input('nome', $this->input('codigo', ''))));

        return array_filter([
            'comum_id' => (int) $this->validated('comum_id'),
            'pagina' => $this->filled('pagina') ? (int) $this->validated('pagina') : null,
            'busca' => $search,
            'dependencia_id' => $this->filled('dependencia_id') ? (int) $this->validated('dependencia_id') : null,
            'tipo_bem_id' => $this->filled('tipo_bem_id') ? (int) $this->validated('tipo_bem_id') : null,
            'status' => trim((string) $this->input('status', '')),
            'somente_novos' => $this->boolean('somente_novos') ? 1 : null,
        ], static fn (mixed $value): bool => is_scalar($value) && $value !== '');
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

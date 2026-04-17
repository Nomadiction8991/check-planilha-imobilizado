<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ProductVerificationItemData;
use Illuminate\Foundation\Http\FormRequest;

class SyncProductVerificationRowRequest extends FormRequest
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
            'produto_id' => ['required', 'integer', 'min:1'],
            'verificado' => ['nullable', 'boolean'],
            'imprimir_etiqueta' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comum_id.required' => 'Selecione uma igreja válida.',
            'produto_id.required' => 'O produto informado é obrigatório.',
            'produto_id.integer' => 'O produto informado é inválido.',
            'verificado.boolean' => 'A seleção de verificação é inválida.',
            'imprimir_etiqueta.boolean' => 'A seleção de impressão é inválida.',
            'observacao.max' => 'A observação deve ter no máximo 255 caracteres.',
        ];
    }

    public function churchId(): int
    {
        return (int) $this->validated('comum_id');
    }

    public function productId(): int
    {
        return (int) $this->validated('produto_id');
    }

    public function toItem(): ProductVerificationItemData
    {
        return new ProductVerificationItemData(
            productId: $this->productId(),
            printLabel: $this->boolean('imprimir_etiqueta'),
            verified: $this->boolean('verificado'),
            observation: trim((string) ($this->validated('observacao') ?? '')),
        );
    }
}

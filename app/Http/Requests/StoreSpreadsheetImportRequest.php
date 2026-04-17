<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\SpreadsheetImportUploadData;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpreadsheetImportRequest extends FormRequest
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
            'usuario_id' => ['required', 'integer', 'min:1'],
            'administracao_id' => ['required', 'integer', 'min:1'],
            'arquivo_csv' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario_id.required' => 'Selecione o responsável pela importação.',
            'usuario_id.min' => 'Selecione um responsável válido.',
            'administracao_id.required' => 'Selecione a administração da importação.',
            'administracao_id.min' => 'Selecione uma administração válida.',
            'arquivo_csv.required' => 'Selecione um arquivo CSV.',
            'arquivo_csv.mimes' => 'Envie um arquivo CSV ou TXT válido.',
            'arquivo_csv.max' => 'O arquivo deve ter no máximo 50MB.',
        ];
    }

    public function toDto(): SpreadsheetImportUploadData
    {
        return new SpreadsheetImportUploadData(
            responsibleUserId: (int) $this->validated('usuario_id'),
            churchId: null,
            administrationId: (int) $this->validated('administracao_id'),
        );
    }
}

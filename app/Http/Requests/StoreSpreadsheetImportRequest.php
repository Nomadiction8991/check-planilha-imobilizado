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
            'administracao_id' => ['required', 'integer', 'min:1'],
            'arquivo_csv' => ['required', 'file', 'extensions:csv,txt,xls,xlsx', 'max:51200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'administracao_id.required' => 'O campo administração é obrigatório.',
            'administracao_id.min' => 'Selecione uma administração válida.',
            'arquivo_csv.required' => 'Selecione um arquivo de planilha (CSV, XLS ou XLSX).',
            'arquivo_csv.extensions' => 'Envie um arquivo CSV, TXT, XLS ou XLSX válido.',
            'arquivo_csv.max' => 'O arquivo não pode ser maior que 50MB.',
        ];
    }

    public function toDto(): SpreadsheetImportUploadData
    {
        return new SpreadsheetImportUploadData(
            (int) $this->input('administracao_id'),
            $this->input('church_id') ? (int) $this->input('church_id') : null,
        );
    }
}

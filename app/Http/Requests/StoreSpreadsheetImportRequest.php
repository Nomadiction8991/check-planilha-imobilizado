<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\SpreadsheetImportUploadData;
use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

class StoreSpreadsheetImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'administracao_id' => ['required', 'integer', 'min:1'],
            'arquivo_csv' => ['required', 'file', 'extensions:csv,txt,xls,xlsx', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'administracao_id.required' => 'Selecione a administração da importação.',
            'administracao_id.min' => 'Selecione uma administração válida.',
            'arquivo_csv.required' => 'Selecione um arquivo de planilha (CSV, TXT, XLS ou XLSX).',
            'arquivo_csv.extensions' => 'Envie um arquivo CSV, TXT, XLS ou XLSX válido.',
            'arquivo_csv.max' => 'O arquivo deve ter no máximo 50MB.',
        ];
    }

    public function toDto(): SpreadsheetImportUploadData
    {
        $responsibleUserId = (int) $this->session()->get('usuario_id', 0);

        if ($responsibleUserId <= 0) {
            throw new RuntimeException('Sessão inválida para identificar o responsável.');
        }

        return new SpreadsheetImportUploadData(
            responsibleUserId: $responsibleUserId,
            churchId: null,
            administrationId: (int) $this->validated('administracao_id'),
        );
    }
}

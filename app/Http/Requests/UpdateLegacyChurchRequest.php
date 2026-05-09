<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ChurchMutationData;
use App\Models\Legacy\Administracao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegacyChurchRequest extends FormRequest
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
            'administracao_id' => ['required', 'integer', Rule::exists((new Administracao())->getTable(), 'id')],
            'descricao' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'cnpj' => ['required', 'string', 'max:30', 'regex:/\S/'],
            'estado' => ['required', 'string', Rule::in(array_keys((array) config('brazil.states', [])))],
            'cidade' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'setor' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'administracao_id.required' => 'Selecione a administração vinculada à igreja.',
            'administracao_id.integer' => 'Selecione uma administração válida.',
            'administracao_id.exists' => 'A administração selecionada não existe.',
            'descricao.required' => 'A descrição / nome fantasia é obrigatória.',
            'descricao.regex' => 'A descrição / nome fantasia não pode conter apenas espaços.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.regex' => 'O CNPJ não pode conter apenas espaços.',
            'estado.required' => 'O estado da igreja é obrigatório.',
            'estado.in' => 'Selecione um estado válido para a igreja.',
            'cidade.required' => 'A cidade da igreja é obrigatória.',
            'cidade.regex' => 'A cidade da igreja não pode conter apenas espaços.',
        ];
    }

    public function toDto(): ChurchMutationData
    {
        $sector = trim((string) ($this->validated('setor') ?? ''));

        return new ChurchMutationData(
            administrationId: (int) $this->validated('administracao_id'),
            description: trim((string) $this->validated('descricao')),
            cnpj: trim((string) $this->validated('cnpj')),
            state: trim((string) $this->validated('estado')),
            city: trim((string) $this->validated('cidade')),
            administrationState: '',
            administrationCity: '',
            sector: $sector !== '' ? $sector : null,
        );
    }
}

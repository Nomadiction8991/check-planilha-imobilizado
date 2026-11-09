<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\LegacyBrandingData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateLegacyBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:40'],
            'app_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:512'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'app_name.required' => 'Informe o nome do sistema.',
            'app_name.string' => 'Informe um nome válido.',
            'app_name.max' => 'O nome deve ter no máximo 40 caracteres.',
            'app_logo.file' => 'Envie um arquivo válido.',
            'app_logo.mimes' => 'Envie uma imagem PNG, JPG ou WEBP.',
            'app_logo.max' => 'A imagem deve ter no máximo 512 KB.',
        ];
    }

    public function toDto(): LegacyBrandingData
    {
        $file = $this->file('app_logo');

        return new LegacyBrandingData(
            (string) $this->input('app_name'),
            $file !== null ? (string) file_get_contents($file->getRealPath()) : null,
            $file !== null ? $file->getMimeType() : null,
        );
    }
}

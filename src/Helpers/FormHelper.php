<?php

namespace App\Helpers;

/**
 * Helper para geração de elementos de formulário
 * 
 * Centraliza a criação de campos HTML padronizados.
 * Todos os campos aplicam uppercase automaticamente.
 */
class FormHelper
{
    /**
     * Gera um campo de texto (input type="text")
     * 
     * @param string $name Nome do campo
     * @param string $label Label do campo
     * @param string $value Valor atual
     * @param array $options Opções adicionais (required, placeholder, maxlength, etc.)
     * @return string HTML do campo
     */
    public static function text(string $name, string $label, string $value = '', array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $maxlength = $options['maxlength'] ?? '';
        $readonly = $options['readonly'] ?? false;
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $readonlyAttr = $readonly ? 'readonly' : '';
        $maxlengthAttr = $maxlength ? "maxlength=\"{$maxlength}\"" : '';
        $placeholderAttr = $placeholder ? "placeholder=\"{$placeholder}\"" : '';

        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$id}\" class=\"form-label\">{$label} {$requiredLabel}</label>";
        $html .= "<input type=\"text\" class=\"form-control\" id=\"{$id}\" name=\"{$name}\" value=\"{$escapedValue}\" {$requiredAttr} {$readonlyAttr} {$maxlengthAttr} {$placeholderAttr}>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera um campo de senha (input type="password")
     */
    public static function password(string $name, string $label, array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $autocomplete = $options['autocomplete'] ?? 'current-password';
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $placeholderAttr = $placeholder ? "placeholder=\"{$placeholder}\"" : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$id}\" class=\"form-label\">{$label} {$requiredLabel}</label>";
        $html .= "<input type=\"password\" class=\"form-control\" id=\"{$id}\" name=\"{$name}\" {$requiredAttr} {$placeholderAttr} autocomplete=\"{$autocomplete}\">";

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera um campo de email (input type="email")
     */
    public static function email(string $name, string $label, string $value = '', array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $readonly = $options['readonly'] ?? false;
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $readonlyAttr = $readonly ? 'readonly' : '';
        $placeholderAttr = $placeholder ? "placeholder=\"{$placeholder}\"" : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$id}\" class=\"form-label\">{$label} {$requiredLabel}</label>";
        $html .= "<input type=\"email\" class=\"form-control\" id=\"{$id}\" name=\"{$name}\" value=\"{$escapedValue}\" {$requiredAttr} {$readonlyAttr} {$placeholderAttr}>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera um campo select (dropdown)
     */
    public static function select(string $name, string $label, array $options, string $selected = '', array $attributes = []): string
    {
        $id = $attributes['id'] ?? $name;
        $required = $attributes['required'] ?? false;
        $helpText = $attributes['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$id}\" class=\"form-label\">{$label} {$requiredLabel}</label>";
        $html .= "<select class=\"form-select\" id=\"{$id}\" name=\"{$name}\" {$requiredAttr}>";

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? 'selected' : '';
            $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $html .= "<option value=\"{$escapedValue}\" {$selectedAttr}>{$escapedText}</option>";
        }

        $html .= '</select>';

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera um campo textarea
     */
    public static function textarea(string $name, string $label, string $value = '', array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $rows = $options['rows'] ?? 3;
        $placeholder = $options['placeholder'] ?? '';
        $maxlength = $options['maxlength'] ?? '';
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $maxlengthAttr = $maxlength ? "maxlength=\"{$maxlength}\"" : '';
        $placeholderAttr = $placeholder ? "placeholder=\"{$placeholder}\"" : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$id}\" class=\"form-label\">{$label} {$requiredLabel}</label>";
        $html .= "<textarea class=\"form-control\" id=\"{$id}\" name=\"{$name}\" rows=\"{$rows}\" {$requiredAttr} {$maxlengthAttr} {$placeholderAttr}>{$escapedValue}</textarea>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera um checkbox
     */
    public static function checkbox(string $name, string $label, bool $checked = false, array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $value = $options['value'] ?? '1';
        $helpText = $options['help'] ?? '';

        $checkedAttr = $checked ? 'checked' : '';
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        $html = '<div class="mb-3 form-check">';
        $html .= "<input type=\"checkbox\" class=\"form-check-input\" id=\"{$id}\" name=\"{$name}\" value=\"{$escapedValue}\" {$checkedAttr}>";
        $html .= "<label class=\"form-check-label\" for=\"{$id}\">{$label}</label>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">{$helpText}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gera botões de submit e cancelar
     */
    public static function buttons(string $submitText = 'SALVAR', string $cancelUrl = null): string
    {
        $html = '<div class="d-grid gap-2">';
        $html .= "<button type=\"submit\" class=\"btn btn-primary\">{$submitText}</button>";

        if ($cancelUrl) {
            $html .= "<a href=\"{$cancelUrl}\" class=\"btn btn-outline-secondary\">CANCELAR</a>";
        }

        $html .= '</div>';

        return $html;
    }
}

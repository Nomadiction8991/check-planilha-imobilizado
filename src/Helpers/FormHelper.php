<?php

declare(strict_types=1);

namespace App\Helpers;


class FormHelper
{
    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

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
        $maxlengthAttr = $maxlength ? 'maxlength="' . self::esc((string) $maxlength) . '"' : '';
        $placeholderAttr = $placeholder ? 'placeholder="' . self::esc($placeholder) . '"' : '';

        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = self::esc($value);
        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$escapedId}\" class=\"form-label\">{$escapedLabel} {$requiredLabel}</label>";
        $html .= "<input type=\"text\" class=\"form-control\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" value=\"{$escapedValue}\" {$requiredAttr} {$readonlyAttr} {$maxlengthAttr} {$placeholderAttr}>";

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function password(string $name, string $label, array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $autocomplete = $options['autocomplete'] ?? 'current-password';
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $placeholderAttr = $placeholder ? 'placeholder="' . self::esc($placeholder) . '"' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';

        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$escapedId}\" class=\"form-label\">{$escapedLabel} {$requiredLabel}</label>";
        $html .= "<input type=\"password\" class=\"form-control\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" {$requiredAttr} {$placeholderAttr} autocomplete=\"" . self::esc($autocomplete) . "\">";

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function email(string $name, string $label, string $value = '', array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $readonly = $options['readonly'] ?? false;
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $readonlyAttr = $readonly ? 'readonly' : '';
        $placeholderAttr = $placeholder ? 'placeholder="' . self::esc($placeholder) . '"' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = self::esc($value);

        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$escapedId}\" class=\"form-label\">{$escapedLabel} {$requiredLabel}</label>";
        $html .= "<input type=\"email\" class=\"form-control\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" value=\"{$escapedValue}\" {$requiredAttr} {$readonlyAttr} {$placeholderAttr}>";

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function select(string $name, string $label, array $options, string $selected = '', array $attributes = []): string
    {
        $id = $attributes['id'] ?? $name;
        $required = $attributes['required'] ?? false;
        $helpText = $attributes['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';

        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$escapedId}\" class=\"form-label\">{$escapedLabel} {$requiredLabel}</label>";
        $html .= "<select class=\"form-select\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" {$requiredAttr}>";

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? 'selected' : '';
            $escapedValue = self::esc($value);
            $escapedText = self::esc($text);
            $html .= "<option value=\"{$escapedValue}\" {$selectedAttr}>{$escapedText}</option>";
        }

        $html .= '</select>';

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function textarea(string $name, string $label, string $value = '', array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $required = $options['required'] ?? false;
        $rows = $options['rows'] ?? 3;
        $placeholder = $options['placeholder'] ?? '';
        $maxlength = $options['maxlength'] ?? '';
        $helpText = $options['help'] ?? '';

        $requiredAttr = $required ? 'required' : '';
        $maxlengthAttr = $maxlength ? 'maxlength="' . self::esc((string) $maxlength) . '"' : '';
        $placeholderAttr = $placeholder ? 'placeholder="' . self::esc($placeholder) . '"' : '';
        $requiredLabel = $required ? '<span class="text-danger">*</span>' : '';
        $escapedValue = self::esc($value);

        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$escapedId}\" class=\"form-label\">{$escapedLabel} {$requiredLabel}</label>";
        $html .= "<textarea class=\"form-control\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" rows=\"{$rows}\" {$requiredAttr} {$maxlengthAttr} {$placeholderAttr}>{$escapedValue}</textarea>";

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function checkbox(string $name, string $label, bool $checked = false, array $options = []): string
    {
        $id = $options['id'] ?? $name;
        $value = $options['value'] ?? '1';
        $helpText = $options['help'] ?? '';

        $checkedAttr = $checked ? 'checked' : '';
        $escapedValue = self::esc($value);

        $escapedLabel = self::esc($label);
        $escapedId = self::esc($id);

        $html = '<div class="mb-3 form-check">';
        $html .= "<input type=\"checkbox\" class=\"form-check-input\" id=\"{$escapedId}\" name=\"" . self::esc($name) . "\" value=\"{$escapedValue}\" {$checkedAttr}>";
        $html .= "<label class=\"form-check-label\" for=\"{$escapedId}\">{$escapedLabel}</label>";

        if ($helpText) {
            $html .= '<div class="form-text">' . self::esc($helpText) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }


    public static function buttons(string $submitText = 'SALVAR', string $cancelUrl = null): string
    {
        $html = '<div class="d-grid gap-2">';
        $html .= '<button type="submit" class="btn btn-primary">' . self::esc($submitText) . '</button>';

        if ($cancelUrl) {
            $html .= '<a href="' . self::esc($cancelUrl) . '" class="btn btn-outline-secondary">CANCELAR</a>';
        }

        $html .= '</div>';

        return $html;
    }
}

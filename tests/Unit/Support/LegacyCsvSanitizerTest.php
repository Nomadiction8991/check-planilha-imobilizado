<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\LegacyCsvSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyCsvSanitizerTest extends TestCase
{
    public function testSanitizeTextNullReturnsEmptyString(): void
    {
        $this->assertSame('', LegacyCsvSanitizer::sanitizeText(null));
    }

    public function testSanitizeTextSafeStringRemainsUnchanged(): void
    {
        $this->assertSame('Cadeira de Escritório', LegacyCsvSanitizer::sanitizeText('Cadeira de Escritório'));
        $this->assertSame('12345', LegacyCsvSanitizer::sanitizeText('12345'));
    }

    #[DataProvider('formulaCharactersProvider')]
    public function testSanitizeTextPrefixesSingleQuoteOnFormulaTriggers(string $prefix, string $input, string $expected): void
    {
        $this->assertSame($expected, LegacyCsvSanitizer::sanitizeText($input));
    }

    public static function formulaCharactersProvider(): array
    {
        return [
            'equals' => ['=', '=SUM(A1:A10)', "'=SUM(A1:A10)"],
            'plus' => ['+', '+5511999999999', "'+5511999999999"],
            'minus' => ['-', '-1234', "'-1234"],
            'at' => ['@', '@SUM(1+1)', "'@SUM(1+1)"],
            'tab' => ["\t", "\tcmd.exe", "'\tcmd.exe"],
            'carriage return' => ["\r", "\rpayload", "'\rpayload"],
        ];
    }

    public function testSanitizeRowNeutralizesAllStringElements(): void
    {
        $row = [
            'id' => 10,
            'code' => 'CCB-001',
            'name' => '=HYPERLINK("http://evil.com")',
            'qty' => 5,
            'notes' => '+Observacao critica',
            'empty' => null,
        ];

        $sanitized = LegacyCsvSanitizer::sanitizeRow($row);

        $this->assertSame([
            'id' => 10,
            'code' => 'CCB-001',
            'name' => '\'=HYPERLINK("http://evil.com")',
            'qty' => 5,
            'notes' => '\'+Observacao critica',
            'empty' => '',
        ], $sanitized);
    }
}

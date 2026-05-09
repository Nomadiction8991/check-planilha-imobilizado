<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CsvParserService;
use PDO;
use PDOStatement;
use ReflectionMethod;
use Tests\TestCase;

final class CsvParserServiceTest extends TestCase
{
    public function testParsearNomePreservesOriginalComplementoWhileIdentifyingAssetType(): void
    {
        $pdo = $this->createMock(PDO::class);
        $statement = $this->createMock(PDOStatement::class);

        $pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT descricao FROM tipos_bens WHERE codigo = :codigo LIMIT 1')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('execute')
            ->with([':codigo' => '4'])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['descricao' => 'CADEIRA']);

        $service = new CsvParserService($pdo);
        $method = new ReflectionMethod($service, 'parsearNome');
        $method->setAccessible(true);

        $result = $method->invoke($service, '4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO');

        self::assertSame('4', $result['tipo_bem_codigo']);
        self::assertSame('CADEIRA', $result['bem']);
        self::assertSame('CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO', $result['complemento']);
        self::assertSame('CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO', $result['descricao_apos_tipo']);
    }
}

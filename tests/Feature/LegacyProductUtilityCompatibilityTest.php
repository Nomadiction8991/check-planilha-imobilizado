<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use App\Models\Legacy\Produto;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyProductUtilityCompatibilityTest extends TestCase
{
    public function testCopyLabelsPageRendersCompatibilityView(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('labelCopyData')->once()->with(7, 3)->andReturn([
                'church' => [
                    'id' => 7,
                    'descricao' => 'Central Cuiabá',
                ],
                'dependencies' => [
                    ['id' => 3, 'descricao' => 'SECRETARIA'],
                    ['id' => 4, 'descricao' => 'TESOURARIA'],
                ],
                'products' => [
                    ['codigo' => 'A-101', 'dependencia' => 'SECRETARIA'],
                    ['codigo' => 'A-102', 'dependencia' => 'SECRETARIA'],
                ],
                'selected_dependency_id' => 3,
                'total_products' => 2,
                'unique_codes' => 2,
                'codes' => 'A-101,A-102',
            ]);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/products/label?comum_id=7&dependencia=3');

        $response->assertOk();
        $response->assertSee('Copiar códigos para etiquetas.');
        $response->assertSee('A-101,A-102');
        $response->assertSee('Igreja');
        $response->assertSee('Selecione uma igreja');
        $response->assertSee('Etiquetas manuais');
        $response->assertSee('Copiar etiquetas manuais');
        $response->assertSee('Copiar tudo');
    }

    public function testCopyLabelsPageOpensWithoutChurchSelected(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/products/label');

        $response->assertOk();
        $response->assertSee('Selecione uma igreja acima para carregar as etiquetas.');
        $response->assertSee('Selecione uma igreja');
    }

    public function testObservationPageRendersCompatibilityForm(): void
    {
        $product = new Produto([
            'id_produto' => 19,
            'comum_id' => 7,
            'codigo' => 'A-101',
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'observacao' => 'RISCO NO ENCOSTO',
        ]);
        $product->exists = true;

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock) use ($product): void {
            $mock->shouldReceive('findForChurch')->once()->with(19, 7)->andReturn($product);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/products/observation?id_produto=19&comum_id=7');

        $response->assertOk();
        $response->assertSee('Observação do produto.');
        $response->assertSee('RISCO NO ENCOSTO');
    }

    public function testObservationEndpointReturnsLegacyJsonPayload(): void
    {
        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateObservation')->once()->with(19, 7, 'risco no encosto')->andReturn(true);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson('/products/observation', [
            'produto_id' => 19,
            'comum_id' => 7,
            'observacoes' => 'risco no encosto',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Observação salva com sucesso',
            'sucesso' => true,
            'mensagem' => 'Observação salva com sucesso',
        ]);
    }

    public function testCheckEndpointReturnsLegacyJsonPayload(): void
    {
        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateCheck')->once()->with(19, 7, true)->andReturn(true);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson('/products/check', [
            'produto_id' => 19,
            'comum_id' => 7,
            'checado' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Status atualizado com sucesso',
        ]);
    }

    public function testLabelEndpointReturnsLegacyJsonPayload(): void
    {
        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateLabel')->once()->with(19, 7, true)->andReturn(true);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson('/products/label', [
            'produto_id' => 19,
            'comum_id' => 7,
            'imprimir' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Etiqueta atualizada com sucesso',
        ]);
    }

    public function testSignEndpointReturnsLegacyJsonPayload(): void
    {
        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('signProducts')->once()->with([19, 20], 7, 9, 'assinar')->andReturn(2);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson('/products/sign', [
            'acao' => 'assinar',
            'PRODUTOS' => [19, 20],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Assinatura aplicada com sucesso.',
            'updated' => 2,
        ]);
    }

    public function testClearEditsRedirectsBackToProductsIndex(): void
    {
        $this->mock(LegacyProductUtilityServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearEdits')->once()->with(19, 7);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->post('/products/clear-edits', [
            'id_PRODUTO' => 19,
            'comum_id' => 7,
            'pagina' => 2,
            'busca' => 'CADEIRA',
        ]);

        $response->assertRedirect('/products?comum_id=7&pagina=2&busca=CADEIRA');
        $response->assertSessionHas('status', 'Edições limpas com sucesso!');
    }
}

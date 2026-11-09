<?php

declare(strict_types=1);

namespace Tests\Feature\Marca;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

final class MarcaDesempenhoTest extends TestCase
{
    public function test_pagina_nao_carrega_logo_e_consulta_configuracoes_uma_vez(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->string('app_name')->nullable();
            $table->longText('app_logo')->nullable();
            $table->string('app_logo_mime')->nullable();
            $table->string('app_logo_hash')->nullable();
        });
        DB::table('configuracoes')->insert([
            'app_name' => 'Marca', 'app_logo' => base64_encode('imagem'),
            'app_logo_mime' => 'image/png', 'app_logo_hash' => md5('imagem'),
        ]);
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::view('/teste-marca-desempenho', 'configurations.index', [
            'mailConfiguration' => new LegacyMailConfigurationData('smtp.example.com', 587, 'tls', 'teste@example.com', null, 'teste@example.com', 'Sistema'),
            'menuOrder' => new LegacyNavigationOrderData([]), 'menuItems' => [],
        ])->middleware('web');

        $consultas = [];
        DB::listen(static function (QueryExecuted $query) use (&$consultas): void {
            if (preg_match('/^\s*select\b.*\bfrom\s+["`]?configuracoes["`]?/i', $query->sql)) {
                $consultas[] = $query->sql;
            }
        });

        $this->withSession(['is_admin' => true])->get('/teste-marca-desempenho')->assertOk();
        foreach ($consultas as $consulta) {
            self::assertDoesNotMatchRegularExpression('/\bselect\s+\*|\bapp_logo\b/i', $consulta);
        }
        self::assertCount(1, $consultas);
    }
}

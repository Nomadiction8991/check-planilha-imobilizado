<?php

declare(strict_types=1);

namespace Tests\Unit\Marca;

use App\DTO\LegacyBrandingData;
use App\Services\LegacyBrandingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class LegacyBrandingServiceTest extends TestCase
{
    public function test_padrao_quando_tabela_nao_existe(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.name' => 'Nome padrão']);
        DB::purge();
        $servico = new LegacyBrandingService();
        self::assertSame('Nome padrão', $servico->nome());
        self::assertSame('padrao', $servico->versao());
        self::assertSame(['conteudo' => file_get_contents(public_path('icons/pwa-192.png')), 'mime' => 'image/png'], $servico->logo());
    }

    public function test_salva_logo_e_versao(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->string('app_name')->nullable();
            $table->text('app_logo')->nullable();
            $table->string('app_logo_mime')->nullable();
            $table->string('app_logo_hash')->nullable();
        });
        $servico = new LegacyBrandingService();
        $servico->salvar(new LegacyBrandingData('Marca', 'abc', 'image/png'));
        self::assertSame('Marca', $servico->nome());
        self::assertSame('900150983cd24fb0d6963f7d28e17f72', $servico->versao());
        self::assertSame(['conteudo' => 'abc', 'mime' => 'image/png'], $servico->logo());
        $servico->salvar(new LegacyBrandingData('Nova marca', 'def', 'image/png'));
        self::assertSame('Nova marca', $servico->nome());
        self::assertSame('4ed9407630eb1000c0f6b63842defa7d', $servico->versao());
    }

    public function test_usa_padrao_quando_colunas_nao_existem(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.name' => 'Nome padrão']);
        DB::purge();
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->string('outra_coluna')->nullable();
        });
        DB::table('configuracoes')->insert(['outra_coluna' => null]);
        $servico = new LegacyBrandingService();
        self::assertSame('Nome padrão', $servico->nome());
        self::assertSame('padrao', $servico->versao());
        self::assertSame('image/png', $servico->logo()['mime']);
    }
}

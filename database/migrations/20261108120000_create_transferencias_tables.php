<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabeçalho da transferência: uma linha para cada vez que um grupo de bens muda de igreja.
        Schema::create('transferencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('comum_origem_id');
            $table->unsignedBigInteger('comum_destino_id');
            $table->unsignedBigInteger('dependencia_destino_id');
            $table->unsignedInteger('numero_saida');
            $table->unsignedInteger('numero_entrada');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            // Os números são sequências por igreja: não podem repetir.
            $table->unique(['comum_origem_id', 'numero_saida'], 'uk_transferencia_saida');
            $table->unique(['comum_destino_id', 'numero_entrada'], 'uk_transferencia_entrada');
        });

        // Cópia dos dados de cada bem no momento da transferência, inclusive o código antigo.
        Schema::create('transferencia_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transferencia_id')->constrained('transferencias')->cascadeOnDelete();
            $table->unsignedBigInteger('produto_id');
            $table->string('codigo_anterior', 255)->nullable();
            $table->string('codigo_novo', 255)->nullable();
            $table->string('tipo_bem', 255)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('dependencia_origem', 255)->nullable();
            $table->timestamps();

            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_itens');
        Schema::dropIfExists('transferencias');
    }
};

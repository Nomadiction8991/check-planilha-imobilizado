<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas que existem só no servidor: aparelhos cadastrados, envios já recebidos
 * (para não aplicar duas vezes) e conflitos pendentes de decisão.
 * Estas tabelas NÃO sincronizam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacao_aparelhos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120);
            // Guarda só o hash do código de acesso. O código em si nunca fica salvo.
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('ultimo_contato_em')->nullable();
            $table->timestamp('revogado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('sincronizacao_envios', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->unsignedBigInteger('aparelho_id');
            $table->timestamp('recebido_em')->useCurrent();
        });

        Schema::create('sincronizacao_conflitos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('aparelho_id')->nullable();
            $table->string('tabela', 64);
            $table->string('chave', 255);
            $table->string('operacao', 10);
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->string('motivo', 120);
            $table->string('status', 20)->default('pendente');
            $table->timestamps();
            $table->index(['status', 'tabela']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacao_conflitos');
        Schema::dropIfExists('sincronizacao_envios');
        Schema::dropIfExists('sincronizacao_aparelhos');
    }
};

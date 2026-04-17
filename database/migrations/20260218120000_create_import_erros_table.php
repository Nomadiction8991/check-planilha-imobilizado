<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_erros')) {
            return;
        }

        Schema::create('import_erros', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('importacao_id')->index('idx_import_erros_importacao');
            $table->string('linha_csv', 50)->nullable();
            $table->string('codigo', 50)->nullable();
            $table->string('localidade')->nullable();
            $table->string('codigo_comum', 50)->nullable();
            $table->text('descricao_csv')->nullable();
            $table->string('bem')->nullable();
            $table->string('complemento')->nullable();
            $table->string('dependencia')->nullable();
            $table->text('mensagem_erro');
            $table->boolean('resolvido')->default(false)->index('idx_import_erros_resolvido');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('importacao_id', 'fk_import_erros_importacao')
                ->references('id')
                ->on('importacoes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_erros');
    }
};

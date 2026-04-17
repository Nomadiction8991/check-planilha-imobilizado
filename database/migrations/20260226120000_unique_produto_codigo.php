<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            <<<'SQL'
UPDATE produtos p
INNER JOIN (
    SELECT codigo, MAX(id_produto) AS id_manter
    FROM produtos
    WHERE codigo IS NOT NULL
      AND codigo != ''
    GROUP BY codigo
    HAVING COUNT(*) > 1
) dup ON p.codigo = dup.codigo AND p.id_produto != dup.id_manter
SET p.ativo = 0
SQL
        );

        DB::statement('ALTER TABLE produtos ADD CONSTRAINT uk_produto_codigo UNIQUE KEY (codigo)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE produtos DROP INDEX uk_produto_codigo');
    }
};

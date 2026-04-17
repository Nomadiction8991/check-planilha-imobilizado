<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `comums`
MODIFY COLUMN `setor` VARCHAR(255) DEFAULT NULL COMMENT 'Setor da igreja';
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `comums`
MODIFY COLUMN `setor` INT DEFAULT NULL;
SQL);
    }
};

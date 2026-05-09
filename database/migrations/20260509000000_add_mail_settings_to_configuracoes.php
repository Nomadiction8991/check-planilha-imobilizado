<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', static function (Blueprint $table): void {
            $table->string('mail_host', 255)->nullable()->after('data_importacao');
            $table->unsignedInteger('mail_port')->nullable()->after('mail_host');
            $table->string('mail_scheme', 20)->nullable()->after('mail_port');
            $table->string('mail_username', 255)->nullable()->after('mail_scheme');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_from_address', 255)->nullable()->after('mail_password');
            $table->string('mail_from_name', 255)->nullable()->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', static function (Blueprint $table): void {
            $table->dropColumn([
                'mail_host',
                'mail_port',
                'mail_scheme',
                'mail_username',
                'mail_password',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};

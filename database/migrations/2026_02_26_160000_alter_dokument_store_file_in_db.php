<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('DOKUMENT', function (Blueprint $table) {
            if (!Schema::hasColumn('DOKUMENT', 'DodaoEmail')) {
                $table->string('DodaoEmail', 120)->nullable();
            }
            if (!Schema::hasColumn('DOKUMENT', 'OriginalnoIme')) {
                $table->string('OriginalnoIme', 160)->nullable();
            }
            if (!Schema::hasColumn('DOKUMENT', 'MimeType')) {
                $table->string('MimeType', 80)->nullable();
            }
            if (!Schema::hasColumn('DOKUMENT', 'Velicina')) {
                $table->unsignedInteger('Velicina')->nullable();
            }
            if (!Schema::hasColumn('DOKUMENT', 'Sadrzaj')) {
                $table->binary('Sadrzaj')->nullable();
            }
        });

        try {
            DB::statement("ALTER TABLE `DOKUMENT` MODIFY `Sadrzaj` LONGBLOB NULL");
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('DOKUMENT', function (Blueprint $table) {
            try {
                $table->foreign('DodaoEmail')
                    ->references('Email')->on('KORISNIK')
                    ->onDelete('set null');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        Schema::table('DOKUMENT', function (Blueprint $table) {
            try {
                $table->dropForeign(['DodaoEmail']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('DOKUMENT', 'Sadrzaj')) $table->dropColumn('Sadrzaj');
            if (Schema::hasColumn('DOKUMENT', 'Velicina')) $table->dropColumn('Velicina');
            if (Schema::hasColumn('DOKUMENT', 'MimeType')) $table->dropColumn('MimeType');
            if (Schema::hasColumn('DOKUMENT', 'OriginalnoIme')) $table->dropColumn('OriginalnoIme');
            if (Schema::hasColumn('DOKUMENT', 'DodaoEmail')) $table->dropColumn('DodaoEmail');
        });
    }
};

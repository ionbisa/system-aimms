<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        $hasSpecification = Schema::hasColumn('assets', 'specification');
        $hasCategory = Schema::hasColumn('assets', 'category');
        $hasNopol = Schema::hasColumn('assets', 'nopol');
        $hasPic = Schema::hasColumn('assets', 'pic');

        if (! $hasNopol || ! $hasPic) {
            Schema::table('assets', function (Blueprint $table) use ($hasNopol, $hasPic) {
                if (! $hasNopol) {
                    $table->string('nopol')->nullable();
                }

                if (! $hasPic) {
                    $table->string('pic')->nullable();
                }
            });
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if ($hasSpecification) {
                DB::statement('ALTER TABLE assets MODIFY specification TEXT NULL');
            }

            if ($hasCategory) {
                DB::statement('ALTER TABLE assets MODIFY category VARCHAR(255) NULL');
            }

            return;
        }

        if (! $hasSpecification && ! $hasCategory) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) use ($hasSpecification, $hasCategory) {
            if ($hasSpecification) {
                $table->text('specification')->nullable()->change();
            }

            if ($hasCategory) {
                $table->string('category')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // This migration repairs legacy schema drift and is intentionally non-destructive.
    }
};

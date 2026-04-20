<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'specification')) {
                $table->text('specification')->nullable();
            }

            if (! Schema::hasColumn('assets', 'photo')) {
                $table->string('photo')->nullable();
            }

            if (! Schema::hasColumn('assets', 'last_service')) {
                $table->date('last_service')->nullable();
            }

            if (! Schema::hasColumn('assets', 'next_service')) {
                $table->date('next_service')->nullable();
            }

            if (! Schema::hasColumn('assets', 'location')) {
                $table->string('location')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $columns = [];

            foreach (['specification', 'photo', 'last_service', 'next_service', 'location'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

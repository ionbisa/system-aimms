<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE assets 
            MODIFY status ENUM('active','maintenance','disposed') 
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE assets 
            MODIFY status ENUM('active','maintenance') 
            NOT NULL DEFAULT 'active'
        ");
    }
};

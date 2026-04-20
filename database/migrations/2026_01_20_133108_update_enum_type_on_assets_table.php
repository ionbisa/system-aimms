<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Perluas ENUM (sementara) agar update tidak error
        DB::statement("
            ALTER TABLE assets
            MODIFY type ENUM(
                'Car',
                'Motor',
                'Office',
                'Delivery Cars',
                'Personal Cars',
                'Motorcycles',
                'Office Assets'
            ) NOT NULL
        ");

        // 2. Konversi data lama ke format baru
        DB::statement("
            UPDATE assets
            SET type = CASE
                WHEN type = 'Car' THEN 'Delivery Cars'
                WHEN type = 'Motor' THEN 'Motorcycles'
                WHEN type = 'Office' THEN 'Office Assets'
                ELSE type
            END
        ");

        // 3. Kunci ENUM ke final value (rapih & standar)
        DB::statement("
            ALTER TABLE assets
            MODIFY type ENUM(
                'Delivery Cars',
                'Personal Cars',
                'Motorcycles',
                'Office Assets'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // rollback aman
        DB::statement("
            ALTER TABLE assets
            MODIFY type ENUM(
                'Car',
                'Motor',
                'Office'
            ) NOT NULL
        ");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category');

            $table->enum('type', [
                'Delivery Cars',
                'Personal Cars',
                'Motorcycles',
                'Office Assets'
            ]);

            $table->date('purchase_date')->nullable();
            $table->decimal('value', 15, 2)->default(0);

            $table->enum('status', [
                'active',
                'maintenance',
                'disposed'
            ])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

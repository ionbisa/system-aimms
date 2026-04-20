<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_uniforms', function (Blueprint $table) {
            $table->id();
            $table->date('pickup_date');
            $table->date('expiry_date');
            $table->string('employee_name');
            $table->string('employee_code');
            $table->string('department');
            $table->string('shirt_size', 50);
            $table->unsignedInteger('quantity_given')->default(1);
            $table->enum('condition', ['Baru', 'Bekas Layak']);
            $table->enum('notes', ['Baru', 'Distribusi Rutin', 'Pergantian Rusak']);
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_uniforms');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_note_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_note_id')->constrained('purchase_order_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['purchase_order_note_id', 'user_id'], 'purchase_order_note_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_note_reads');
    }
};

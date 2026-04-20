<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_request_note_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_request_note_id')->constrained('item_request_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['item_request_note_id', 'user_id'], 'item_request_note_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_request_note_reads');
    }
};

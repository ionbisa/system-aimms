<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->timestamp('requested_at');
            $table->string('division');
            $table->string('requested_role')->nullable();
            $table->string('overall_status')->default('pending');
            $table->string('current_step')->default('waiting_production_head');
            $table->text('initial_note')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('final_approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('ga_seen_at')->nullable();
            $table->string('realization_status')->nullable();
            $table->text('realization_note')->nullable();
            $table->foreignId('realized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('realized_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_requests');
    }
};

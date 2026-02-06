<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_hook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hook_id')->constrained('payment_hooks')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['hook_id']);
            $table->index(['event']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_hook_deliveries');
    }
};

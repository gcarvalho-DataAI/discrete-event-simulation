<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['PENDING', 'PAID', 'CANCELED', 'REFUNDED'])->default('PENDING');
            $table->integer('total_cents');
            $table->string('currency', 8)->default('BRL');
            $table->string('customer_email')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_preference_id')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('provider_status_detail')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['created_at']);
            $table->index(['customer_id']);
            $table->index(['provider_preference_id']);
            $table->index(['provider_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

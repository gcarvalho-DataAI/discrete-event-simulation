<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_hooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('provider')->default('mercadopago');
            $table->string('event');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['provider']);
            $table->index(['event']);
            $table->index(['active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_hooks');
    }
};

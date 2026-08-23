<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['checkout_initiated', 'webhook', 'status_check']);
            $table->string('provider_reference')->nullable();
            $table->string('status')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('signature_valid')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['payment_id']);
            $table->index(['provider_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

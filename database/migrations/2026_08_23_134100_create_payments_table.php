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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Élément payé : Boost ou Subscription (relation polymorphique).
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->enum('provider', ['wave', 'orange_money', 'other']);
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('reference')->unique();
            $table->string('payer_phone', 20)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['user_id', 'status']);
            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

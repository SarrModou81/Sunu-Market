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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('brand')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('condition', ['neuf', 'tres_bon', 'bon', 'moyen'])->default('bon');
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained('neighborhoods')->nullOnDelete();
            $table->boolean('delivery_available')->default(false);
            $table->enum('status', [
                'BROUILLON', 'EN_ATTENTE', 'ACTIVE', 'VENDUE',
                'ARCHIVEE', 'SIGNALEE', 'BLOQUEE', 'REFUSEE',
            ])->default('BROUILLON');
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('contacts_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('boosted_until')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'boosted_until']);
            $table->index(['category_id', 'status']);
            $table->index(['city_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

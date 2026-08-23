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
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('shop_name')->nullable();
            $table->text('description')->nullable();
            $table->enum('seller_type', ['standard', 'pro'])->default('standard');
            $table->timestamp('pro_started_at')->nullable();
            $table->timestamp('pro_expires_at')->nullable();
            $table->boolean('badge_verified')->default(false);
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_message')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('contacts_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();

            $table->index(['seller_type', 'pro_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};

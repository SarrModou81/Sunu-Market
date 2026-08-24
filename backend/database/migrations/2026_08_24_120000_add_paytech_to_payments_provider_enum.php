<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Laravel's enum()->change() emits invalid Postgres DDL (combines the type
            // change and CHECK clause in one ALTER COLUMN, which Postgres rejects) —
            // replace the CHECK constraint directly instead.
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_provider_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_provider_check CHECK (provider IN ('wave', 'orange_money', 'paytech', 'other'))");

            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('provider', ['wave', 'orange_money', 'paytech', 'other'])->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_provider_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_provider_check CHECK (provider IN ('wave', 'orange_money', 'other'))");

            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('provider', ['wave', 'orange_money', 'other'])->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transactions ALTER COLUMN total_amount SET DEFAULT 0');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->decimal('total_amount', 10, 2)->default(0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transactions ALTER COLUMN total_amount DROP DEFAULT');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->decimal('total_amount', 10, 2)->change();
            });
        }
    }
};

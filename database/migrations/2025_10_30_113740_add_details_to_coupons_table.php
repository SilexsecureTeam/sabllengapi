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
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('promotion_name')->before('code');
            $table->date('start_date')->nullable()->after('expires_at');
            $table->boolean('is_active')->default(true)->after('times_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('promotion_name');
            $table->dropColumn('state_date');
            $table->dropColumn('is_active');
        });
    }
};

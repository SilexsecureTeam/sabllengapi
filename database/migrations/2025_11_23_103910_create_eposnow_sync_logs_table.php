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
        Schema::create('eposnow_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sync_type')->default('stock_update');
            $table->string('status'); // success | failed
            $table->integer('quantity')->default(0);
            $table->integer('old_stock')->nullable();
            $table->integer('new_stock')->nullable();
            $table->longText('response')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eposnow_sync_logs', function (Blueprint $table) {
            $table->dropForeign(['order_id', 'product_id']);
        });
        Schema::dropIfExists('eposnow_sync_logs');
    }
};

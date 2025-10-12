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
        Schema::create('stock_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Product name
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete(); // ✅ Linked to brands table
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('order_code')->nullable(); // Purchase order or stock order code
          
            $table->integer('current_stock')->default(0); // Quantity currently in stock
            $table->integer('total_stock')->default(0); // Lifetime total stock count
            $table->integer('on_order')->default(0); // Quantity on order

            $table->decimal('cost_price', 10, 2)->nullable(); // Cost per unit
            $table->decimal('sale_price', 10, 2)->nullable(); // Sale price per unit

            $table->decimal('total_cost', 15, 2)->nullable(); // cost_price * total_stock
            $table->decimal('total_value', 15, 2)->nullable(); // sale_price * total_stock

            $table->decimal('margin', 10, 2)->nullable(); // sale_price - cost_price
            $table->decimal('margin_perc', 5, 2)->nullable(); // (margin / sale_price) * 100

            $table->string('measure')->nullable(); // e.g. "ml", "kg"
            $table->string('unit_of_sale')->nullable(); // e.g. "bottle", "pack", "carton"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reports');
    }
};

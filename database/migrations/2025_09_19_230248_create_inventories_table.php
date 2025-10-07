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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('barcode')->nullable()->unique(); // Added unique constraint
            $table->string('brand')->nullable();
            $table->string('supplier')->nullable();
            $table->string('order_code')->nullable();
            $table->string('category_name')->nullable();
            $table->integer('current_stock')->nullable()->default(0);
            $table->integer('total_stock')->nullable()->default(0);
            $table->integer('on_order')->nullable()->default(0); // Changed from tinyInteger
            $table->decimal('cost_price', 15, 2)->nullable()->default(0.00);
            $table->decimal('sales_price', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_cost', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_value', 15, 2)->nullable()->default(0.00);
            $table->decimal('margin', 15, 2)->nullable()->default(0.00);
            $table->decimal('margin_percentage', 8, 2)->nullable()->default(0.00); // Changed to decimal
            $table->string('measure')->nullable()->default(''); // Added default empty string
            $table->string('unit_of_sale')->nullable()->default(''); // Added default empty string
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['category_name']);
            $table->index(['supplier']);
            $table->index(['brand']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
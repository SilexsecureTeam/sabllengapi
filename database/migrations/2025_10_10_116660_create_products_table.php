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
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // Link to categories table
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name'); // Product name
            $table->text('description')->nullable(); // Product description
            $table->json('images')->nullable();
            $table->decimal('cost_price', 10, 2)->nullable(); // Cost before tax
            $table->string('tax_rate')->nullable(); // Tax rate in percentage
            $table->decimal('tax_rate_value', 5, 2)->default(0.00); // Tax rate in percentage
            $table->decimal('cost_inc_tax', 10, 2)->nullable(); // Cost including tax
            $table->decimal('sale_price_inc_tax', 10, 2)->nullable(); // Sale price including tax

            $table->boolean('is_variable_price')->default(false); // Can the price vary?
            $table->decimal('margin_perc', 5, 2)->nullable(); // Profit margin percentage
            $table->boolean('tax_exempt_eligible')->default(false); // Tax exemption eligibility
            $table->decimal('rr_price', 10, 2)->nullable(); // Recommended retail price

            $table->string('bottle_deposit_item_name')->nullable(); // Deposit info
            $table->string('barcode')->nullable()->unique(); // Unique product barcode

            $table->json('size')->nullable(); // Product size (e.g., "500ml")
            $table->json('colours')->nullable(); // Product color
            $table->string('product_code')->nullable()->unique(); // Custom product identifier
            $table->integer('age_restriction')->nullable(); // Age limit (e.g., 18)
            $table->timestamps();
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

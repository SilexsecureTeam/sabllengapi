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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Brand name, e.g. "Coca-Cola"
            $table->string('slug')->unique(); // For URLs, e.g. "coca-cola"
            $table->text('description')->nullable(); // Optional brand details
            $table->string('logo')->nullable(); // Path to logo image (e.g. storage/brands/logo.png)
            $table->string('website')->nullable(); // Optional website link
            $table->boolean('is_active')->default(true); // Enable/disable brand
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};

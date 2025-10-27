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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->uuid('session_id')->nullable()->index();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('product_id');
        });
        Schema::dropIfExists('wishlists');
    }
};

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
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('image_path')->nullable(); // uploaded logo/image
            $table->string('text')->nullable(); // custom text
            $table->text('instruction')->nullable(); // message/instruction
            $table->enum('position', ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'])->default('center'); // placement
            $table->json('coordinates')->nullable(); // x,y values for fine-grained movement
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customizations', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('product_id');
            $table->dropColumn('user_id');
        });
    }
};

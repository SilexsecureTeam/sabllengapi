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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // ✅ Foreign keys
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');

            // ✅ Paystack + order references
            $table->string('reference')->unique(); // Paystack transaction reference
            $table->string('currency', 10)->default('NGN');

            // ✅ Payment details
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // e.g. success, failed, pending
            $table->string('payment_channel')->nullable(); // e.g. card, bank, ussd
            $table->string('gateway_response')->nullable();

            // ✅ Extra Paystack data
            $table->timestamp('paid_at')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('customer_email')->nullable();

            // ✅ Store raw Paystack response as JSON (for audit/debug)
            $table->json('transaction_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
        });
        Schema::dropIfExists('transactions');
    }
};

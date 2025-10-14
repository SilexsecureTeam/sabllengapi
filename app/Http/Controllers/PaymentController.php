<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function PaystackCallback($reference, $order_reference)
    {
        // ✅ Verify transaction with Paystack
        $verifyUrl = "https://api.paystack.co/transaction/verify/{$reference}";
        $response = Http::withToken(config('services.paystack.secret_key'))->get($verifyUrl)->json();

        if (!$response['status']) {
            return response()->json(['error' => $response['message']], 400);
        }

        $data = $response['data'];
        $status = $data['status'];

        // ✅ Proceed only if Paystack confirms success
        if ($status !== 'success') {
            return response()->json(['error' => 'Payment not successful'], 400);
        }

        // ✅ Check that the order exists and matches both reference + order_reference
        $order = Order::where('reference', $reference)
            ->where('order_reference', $order_reference)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or reference mismatch'], 404);
        }

        // ✅ Update order details
        $order->update([
            'status' => 'paid',
            'payment_method' => $data['channel'] ?? 'paystack',
            'tax_amount' => $order->tax_amount ?? 0,
            'total' => $data['amount'] / 100, // Convert from kobo
            'reference' => $reference,
        ]);

        // ✅ Log or update transaction record
        Transaction::updateOrCreate(
            ['reference' => $reference],
            [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'amount' => $data['amount'] / 100,
                'currency' => $data['currency'],
                'status' => $status,
                'payment_channel' => $data['channel'],
                'gateway_response' => $data['gateway_response'],
                'paid_at' => $data['paid_at'],
                'authorization_code' => $data['authorization']['authorization_code'] ?? null,
                'customer_email' => $data['customer']['email'] ?? null,
                'transaction_data' => $data,
            ]
        );

        return response()->json([
            'message' => 'Payment verified successfully',
            'status' => $status,
            'order' => $order->load('items.product'),
        ]);
    }
}

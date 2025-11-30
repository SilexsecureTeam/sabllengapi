<?php

namespace App\Http\Controllers;

use App\Jobs\SyncEposStockJob;
use App\Models\EposnowSyncLog;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function PaystackCallback($reference, $order_reference)
    {

        $verifyUrl = "https://api.paystack.co/transaction/verify/{$reference}";
        $response = Http::withToken(config('services.paystack.secret_key'))->get($verifyUrl)->json();

        if (!$response['status']) {
            return response()->json(['error' => $response['message']], 400);
        }

        $data = $response['data'];
        $status = $data['status'];

        if ($status !== 'success') {
            return response()->json(['error' => 'Payment not successful'], 400);
        }

        $order = Order::where('order_reference', $order_reference)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or reference mismatch'], 404);
        }

        $order->update([
            'status' => 'paid',
            'payment_method' => $data['channel'] ?? 'paystack',
            'tax_amount' => $order->tax_amount ?? 0,
            'total' => $data['amount'] / 100,
            'reference' => $reference,
        ]);

        if ($order->status === 'paid') {
            $transact_mode = 'Online Transaction';
        }

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
                'transaction_type' => $transact_mode,
            ]
        );

        // 5. Dispatch stock sync jobs for each item
        foreach ($order->items as $item) {
            // ensure product has eposnow_product_id
            if (!empty($item->product->eposnow_product_id)) {
                SyncEposStockJob::dispatch(
                    orderId: $order->id,
                    eposProductId: $item->product->eposnow_product_id,
                    productId: $item->product->id,
                    quantity: $item->quantity,
                    orderReference: $order->order_reference
                );
            } else {
                // create a failed log to inspect later (optional)
                Log::warning('Product missing eposnow_product_id', ['product_id' => $item->product->id]);
            }
        }

        return response()->json([
            'message' => 'Payment successful and stock sync queued',
            'status' => $status,
            'order' => $order->load('items.product'),
        ]);
    }

    public function userTransactions(Request $request)
    {
        // Ensure user is authenticated
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Fetch user's transactions
        $transactions = Transaction::with(['order'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Transactions retrieved successfully',
            'data' => $transactions
        ], 200);
    }
}

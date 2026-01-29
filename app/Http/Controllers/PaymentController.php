<?php

namespace App\Http\Controllers;

use App\Jobs\SyncEposStockJob;
use App\Models\EposnowSyncLog;
use App\Models\Inventory;
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

        DB::transaction(function () use ($order, $reference) {
            // 🔥 Stock deduction for each order item using BARCODE
            foreach ($order->items as $item) {
                $product = $item->product;

                if (!$product) {
                    Log::warning('Product not found for order item', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                    ]);
                    continue;
                }

                // Check if product has a barcode
                if (empty($product->barcode)) {
                    Log::warning('Product has no barcode', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                    ]);
                    continue;
                }

                // 🎯 Find inventory by barcode (unique identifier)
                $inventory = Inventory::where('product_id', $product->id)
                    ->where('barcode', $product->barcode)
                    ->first();

                if (!$inventory) {
                    Log::warning('No inventory found with matching barcode', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'barcode' => $product->barcode,
                    ]);
                    continue;
                }

                // Quantity to deduct
                $deductQty = $item->quantity;
                $oldStock = $inventory->current_stock;

                // 🚨 Check for insufficient stock
                if ($oldStock < $deductQty) {
                    Log::error('Insufficient stock', [
                        'order_id' => $order->id,
                        'order_reference' => $order->order_reference,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'barcode' => $product->barcode,
                        'inventory_id' => $inventory->id,
                        'inventory_name' => $inventory->name,
                        'required_qty' => $deductQty,
                        'available_stock' => $oldStock,
                    ]);

                    throw new \Exception(
                        "Insufficient stock for '{$product->name}' (Barcode: {$product->barcode}). Required: {$deductQty}, Available: {$oldStock}"
                    );
                }


                // 🔻 Deduct stock
                $inventory->decrement('current_stock', $deductQty);

                // 🔄 Recalculate inventory totals
                $inventory->refresh(); // Reload fresh data
                $newStock = $inventory->current_stock;

                // Recalculate totals
                $totalCost = ($inventory->cost_price ?? 0) * $newStock;
                $totalValue = ($inventory->sales_price ?? 0) * $newStock;

                $margin = 0;
                $marginPercentage = 0;

                if ($totalCost > 0) {
                    $margin = $totalValue - $totalCost;
                    $marginPercentage = ($margin / $totalCost) * 100;
                }

                $inventory->update([
                    'total_cost' => $totalCost,
                    'total_value' => $totalValue,
                    'margin' => $margin,
                    'margin_percentage' => $marginPercentage,
                ]);

                Log::info('Stock deducted and recalculated using barcode', [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_barcode' => $product->barcode,
                    'inventory_id' => $inventory->id,
                    'inventory_name' => $inventory->name,
                    'inventory_barcode' => $inventory->barcode,
                    'deducted_qty' => $deductQty,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'total_cost' => $totalCost,
                    'total_value' => $totalValue,
                    'margin' => $margin,
                ]);

                // 🧾 Create EPOS sync log
                EposnowSyncLog::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sync_type' => 'sale',
                    'status' => 'pending',
                    'quantity' => $deductQty,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'payment_method' => 'online',
                    'synced_at' => now(),
                ]);

                // 🚀 Dispatch EPOS sync job
                if ($product->eposnow_product_id) {
                    SyncEposStockJob::dispatch(
                        orderId: $order->id,
                        eposProductId: $product->eposnow_product_id,
                        productId: $product->id,
                        quantity: $deductQty,
                        orderReference: $order->order_reference,
                        paymentMethod: 'online'
                    );
                }
            }
        });
        return response()->json([
            'message' => 'Payment successful and stock synced',
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

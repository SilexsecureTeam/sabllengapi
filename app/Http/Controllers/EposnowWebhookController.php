<?php

namespace App\Http\Controllers;

use App\Models\EposnowSyncLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EposnowWebhookController extends Controller
{
    public function handleSale(Request $request)
    {
        Log::info('EPOSNOW Webhook Received', $request->all());

        $products = $request->input('Products', []);
        $reference = $request->input('Reference', 'EPOS-SALE');

        foreach ($products as $item) {
            $eposProductId = $item['ProductId'] ?? null;

            if (!$eposProductId) {
                Log::warning('EPOS product ID missing in payload', ['item' => $item]);
                continue;
            }

            // Try to find the product, skip if not mapped
            $product = Product::where('eposnow_product_id', $eposProductId)->first();

            if (!$product) {
                Log::warning('EPOS product not mapped in local DB', ['epos_product_id' => $eposProductId]);
                continue;
            }

            if (!$product->inventory) {
                Log::warning('Inventory missing for product', ['product_id' => $product->id]);
                continue;
            }

            // Update stock safely
            $oldStock = $product->inventory->current_stock ?? 0;
            $quantity = abs($item['Quantity'] ?? 0);
            $newStock = max(0, $oldStock - $quantity);

            $product->inventory->update(['current_stock' => $newStock]);

            // Log the sync
            EposnowSyncLog::create([
                'order_id'       => null,
                'product_id'     => $product->id,
                'sync_type'      => 'pos_sale',
                'status'         => 'success',
                'quantity'       => $quantity,
                'old_stock'      => $oldStock,
                'new_stock'      => $newStock,
                'response'       => $item,
                'payment_method' => 'pos',
                'synced_at'      => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockReportController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== "admin") {
                return response()->json([
                    'message' => 'You are unauthorized',
                ], 403);
            }

            $data = Inventory::latest()->get();

            return response()->json([
                'message' => 'List of Reports',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching reports.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $user = Auth::user();

    //         if (!$user || $user->role !== 'admin') {
    //             return response()->json(['message' => 'You are unauthorized'], 403);
    //         }

    //         $validated = $request->validate([
    //             'product_id' => 'nullable|exists:products,id', // Add this
    //             'name' => 'nullable|string|max:255',
    //             'barcode' => 'nullable|string|max:13|unique:inventories,barcode|regex:/^\d{13}$/',
    //             'brand' => 'nullable|string|max:255',
    //             'supplier' => 'nullable|string|max:255',
    //             'order_code' => 'nullable|string|max:255',
    //             'category_name' => 'nullable|string|max:255',
    //             'current_stock' => 'nullable|numeric',
    //             'total_stock' => 'nullable|numeric',
    //             'on_order' => 'nullable|numeric',
    //             'cost_price' => 'nullable|numeric',
    //             'sales_price' => 'nullable|numeric',
    //             'measure' => 'nullable|string|max:255',
    //             'unit_of_sale' => 'nullable|string|max:255',
    //         ]);

    //         // 🔹 Auto-populate barcode from selected product
    //         if (!empty($validated['product_id'])) {
    //             $product = Product::find($validated['product_id']);

    //             if ($product && $product->barcode) {
    //                 $validated['barcode'] = $product->barcode;
    //             }

    //             // Optional: Auto-populate other fields from product if not provided
    //             $validated['name'] = $validated['name'] ?? $product->name;
    //             $validated['cost_price'] = $validated['cost_price'] ?? $product->cost_price;
    //             $validated['sales_price'] = $validated['sales_price'] ?? $product->sale_price;
    //         }

    //         // 🔹 Auto-calculate totals
    //         $validated['total_cost'] = ($validated['cost_price'] ?? 0) * ($validated['total_stock'] ?? 0);
    //         $validated['total_value'] = ($validated['sales_price'] ?? 0) * ($validated['total_stock'] ?? 0);

    //         if (($validated['total_cost'] ?? 0) > 0) {
    //             $validated['margin'] = $validated['total_value'] - $validated['total_cost'];
    //             $validated['margin_percentage'] = ($validated['margin'] / $validated['total_cost']) * 100;
    //         } else {
    //             $validated['margin'] = 0;
    //             $validated['margin_percentage'] = 0;
    //         }

    //         $inventory = Inventory::create($validated);

    //         return response()->json([
    //             'message' => 'Inventory item created successfully',
    //             'data' => $inventory->load('product')
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error creating inventory item',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'You are unauthorized'], 403);
            }

            $validated = $request->validate([
                'product_id' => 'nullable|exists:products,id|unique:inventories,product_id',
                'name' => 'nullable|string|max:255', // Changed to nullable
                'barcode' => 'nullable|string|max:13|regex:/^\d{13}$/',
                'brand' => 'nullable|string|max:255',
                'supplier' => 'nullable|string|max:255',
                'order_code' => 'nullable|string|max:255',
                'category_name' => 'nullable|string|max:255',
                'current_stock' => 'nullable|numeric',
                'total_stock' => 'nullable|numeric',
                'on_order' => 'nullable|numeric',
                'cost_price' => 'nullable|numeric',
                'sales_price' => 'nullable|numeric',
                'measure' => 'nullable|string|max:255',
                'unit_of_sale' => 'nullable|string|max:255',
            ]);

            // 🔥 Auto-populate fields from selected product
            if (!empty($validated['product_id'])) {
                $product = Product::with(['brand', 'supplier', 'category'])->find($validated['product_id']);

                if ($product) {
                    // Auto-fill only if not manually provided
                    $validated['name'] = $validated['name'] ?? $product->name;
                    $validated['barcode'] = $validated['barcode'] ?? $product->barcode;
                    $validated['cost_price'] = $validated['cost_price'] ?? $product->cost_price;
                    $validated['sales_price'] = $validated['sales_price'] ?? $product->sales_price;

                    // Auto-fill brand name
                    if (!isset($validated['brand']) && $product->brand) {
                        $validated['brand'] = $product->brand->name;
                    }

                    // Auto-fill supplier name
                    if (!isset($validated['supplier']) && $product->supplier) {
                        $validated['supplier'] = $product->supplier->name;
                    }

                    // Auto-fill category name
                    if (!isset($validated['category_name']) && $product->category) {
                        $validated['category_name'] = $product->category->name;
                    }

                    // Auto-fill product code as order code if not provided
                    $validated['order_code'] = $validated['order_code'] ?? $product->product_code;
                }
            }

            // 🔹 Auto-calculate totals
            $validated['total_cost'] = ($validated['cost_price'] ?? 0) * ($validated['total_stock'] ?? 0);
            $validated['total_value'] = ($validated['sales_price'] ?? 0) * ($validated['total_stock'] ?? 0);

            if (($validated['total_cost'] ?? 0) > 0) {
                $validated['margin'] = $validated['total_value'] - $validated['total_cost'];
                $validated['margin_percentage'] = ($validated['margin'] / $validated['total_cost']) * 100;
            } else {
                $validated['margin'] = 0;
                $validated['margin_percentage'] = 0;
            }

            $inventory = Inventory::create($validated);

            return response()->json([
                'message' => 'Inventory item created successfully',
                'data' => $inventory->load('product')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating inventory item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a specific inventory item.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'You are unauthorized'], 403);
            }

            $inventory = Inventory::findOrFail($id);

            return response()->json([
                'message' => 'Inventory item details',
                'data' => $inventory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching inventory item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing inventory item.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'You are unauthorized'], 403);
            }

            $inventory = Inventory::findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'barcode' => 'nullable|string|max:13|unique:inventories,barcode,' . $id . '|regex:/^\d{13}$/',
                'brand' => 'nullable|string|max:255',
                'supplier' => 'nullable|string|max:255',
                'order_code' => 'nullable|string|max:255',
                'category_name' => 'nullable|string|max:255',
                'current_stock' => 'nullable|numeric',
                'total_stock' => 'nullable|numeric',
                'on_order' => 'nullable|numeric',
                'cost_price' => 'nullable|numeric',
                'sales_price' => 'nullable|numeric',
                'measure' => 'nullable|string|max:255',
                'unit_of_sale' => 'nullable|string|max:255',
            ]);

            // 🔹 Recalculate totals
            $validated['total_cost'] = ($validated['cost_price'] ?? $inventory->cost_price) * ($validated['total_stock'] ?? $inventory->total_stock);
            $validated['total_value'] = ($validated['sales_price'] ?? $inventory->sales_price) * ($validated['total_stock'] ?? $inventory->total_stock);

            if (($validated['total_cost'] ?? 0) > 0) {
                $validated['margin'] = $validated['total_value'] - $validated['total_cost'];
                $validated['margin_percentage'] = ($validated['margin'] / $validated['total_cost']) * 100;
            } else {
                $validated['margin'] = 0;
                $validated['margin_percentage'] = 0;
            }

            $inventory->update($validated);

            return response()->json([
                'message' => 'Inventory item updated successfully',
                'data' => $inventory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating inventory item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an inventory item.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'You are unauthorized'], 403);
            }

            $inventory = Inventory::findOrFail($id);
            $inventory->delete();

            return response()->json([
                'message' => 'Inventory item deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting inventory item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

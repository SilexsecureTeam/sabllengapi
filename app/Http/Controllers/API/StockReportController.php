<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
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

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'You are unauthorized'], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'barcode' => 'nullable|string|max:13|unique:inventories,barcode|regex:/^\d{13}$/',
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
                'data' => $inventory
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

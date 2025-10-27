<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    // List all taxes
    public function index()
    {
        $taxes = Tax::all();
        return response()->json([
            'message' => 'fixed tax rate',
            'tax' => $taxes,
        ]);
    }

    // Create a new tax
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $tax = Tax::create($validated);

        return response()->json([
            'message' => 'Tax created successfully',
            'tax' => $tax,
        ], 201);
    }

    // Update a tax
    public function update(Request $request, $id)
    {
        $tax = Tax::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'percentage' => 'sometimes|required|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ]);
// dd($validated);
        $tax->update($validated);

        return response()->json([
            'message' => 'Tax updated successfully',
            'tax' => $tax,
        ]);
    }

    // Delete a tax
    public function destroy($id)
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();

        return response()->json([
            'message' => 'Tax deleted successfully'
        ]);
    }
}

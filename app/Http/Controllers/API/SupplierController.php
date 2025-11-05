<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
   public function index()
    {
        return response()->json(Supplier::all());
    }

    // ✅ Create a new supplier
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'contact_number2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'contact_person' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'supplier' => $supplier
        ], 201);
    }

    // ✅ View a single supplier
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    // ✅ Update supplier
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'contact_number2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'contact_person' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => $supplier
        ]);
    }

    // ✅ Delete supplier
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.'
        ]);
    }
}

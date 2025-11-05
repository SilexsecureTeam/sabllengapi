<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::all()->map(function ($brand) {
            $brand->logo_url = $brand->logo ? asset('storage/' . $brand->logo) : null;
            return $brand;
        });

        return response()->json([
            'message' => 'List of brands',
            'brands' => $brands
        ], 200);
    }

    // ✅ Create a brand
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'slug' => 'nullable|string|max:255|unique:brands,slug',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'website' => 'nullable|url|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        // Generate slug if not provided
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        // Default active value
        $validated['is_active'] = $validated['is_active'] ?? true;

        $brand = Brand::create($validated);

        $brand->logo_url = $brand->logo ? asset('storage/' . $brand->logo) : null;

        return response()->json([
            'message' => 'Brand created successfully',
            'brand' => $brand
        ], 201);
    }

    // ✅ View single brand
    public function show($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->logo_url = $brand->logo ? asset('storage/' . $brand->logo) : null;

        return response()->json([
            'brand' => $brand
        ], 200);
    }

    // ✅ Update brand
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $brand->id,
            'slug' => 'nullable|string|max:255|unique:brands,slug,' . $brand->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'website' => 'nullable|url|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        // Generate slug if missing
        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle logo replacement
        if ($request->hasFile('logo')) {
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($validated);

        $brand->logo_url = $brand->logo ? asset('storage/' . $brand->logo) : null;

        return response()->json([
            'message' => 'Brand updated successfully',
            'brand' => $brand
        ], 200);
    }

    // ✅ Delete brand
    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully'
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:sub_categories,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('sub_categories', 'public');
        }

        // dd($validated['image']);
        $subcategory = SubCategory::create($validated);

        return response()->json([
            'message' => 'Subcategory created successfully',
            'data' => [
                'id' => $subcategory->id,
                'category_id' => $subcategory->category_id,
                'name' => $subcategory->name,
                'description' => $subcategory->description,
                'image_url' => $subcategory->image ? asset('storage/' . $subcategory->image) : null,
                'created_at' => $subcategory->created_at,
                'updated_at' => $subcategory->updated_at,
            ]
        ]);
    }

    public function index()
    {
        return response()->json(
            SubCategory::with('category:id,name')->get()
        );
    }

    public function show($id)
    {
        $subcategory = SubCategory::with('category')->findOrFail($id);
        return response()->json($subcategory);
    }

    public function update(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:subcategories,name,' . $subcategory->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($subcategory->image) {
                Storage::disk('public')->delete($subcategory->image);
            }
            $validated['image'] = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory->update($validated);

        return response()->json([
            'message' => 'Subcategory updated successfully',
            'data' => [
                'id' => $subcategory->id,
                'category_id' => $subcategory->category_id,
                'name' => $subcategory->name,
                'description' => $subcategory->description,
                'image_url' => $subcategory->image ? asset('storage/' . $subcategory->image) : null,
                'created_at' => $subcategory->created_at,
                'updated_at' => $subcategory->updated_at,
            ]
        ]);
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::findOrFail($id);

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted successfully']);
    }
}

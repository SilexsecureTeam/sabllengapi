<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // public function index()
    // {
    //     return response()->json(Category::all(), 200);
    // }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'tag_id'    => 'nullable|exists:tags,id',
    //         'name'        => 'required|string|max:255|unique:categories,name',
    //         'description' => 'nullable|string',
    //         'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     $path = null;
    //     if ($request->hasFile('image')) {
    //         $path = $request->file('image')->store('categories', 'public');
    //     }

    //     $category = Category::create([
    //         'tag_id' => $validated['tag_id'] ?? null,
    //         'name'        => $validated['name'],
    //         'slug'        => Str::slug($validated['name']),
    //         'description' => $validated['description'] ?? null,
    //         'image'       => $path,
    //     ]);

    //     // ✅ Convert to full URL (same pattern as your sliders example)
    //     if (!empty($category->image)) {
    //         $category->image_url = asset('storage/' . $category->image);
    //     }

    //     return response()->json([
    //         'message'  => 'Category created successfully',
    //         'category' => $category,
    //     ], 201);
    // }

    // public function show(Category $category)
    // {
    //     return response()->json($category->load('products'), 200);
    // }

    // public function update(Request $request, Category $category)
    // {
    //     $validated = $request->validate([
    //         'tag_id'      => 'nullable|exists:tags,id',
    //         'name'        => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
    //         'description' => 'nullable|string',
    //         'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     // Handle image upload
    //     if ($request->hasFile('image')) {
    //         // Delete old if exists
    //         if (!empty($category->image) && Storage::disk('public')->exists($category->image)) {
    //             Storage::disk('public')->delete($category->image);
    //         }

    //         $validated['image'] = $request->file('image')->store('categories', 'public');
    //     }

    //     // Update the slug if name changes
    //     if (isset($validated['name'])) {
    //         $validated['slug'] = Str::slug($validated['name']);
    //     }

    //     // Update the category with validated fields
    //     $category->update($validated);

    //     // ✅ Convert image path to full URL for response
    //     if (!empty($category->image)) {
    //         $category->image_url = asset('storage/' . $category->image);
    //     }

    //     return response()->json([
    //         'message'  => 'Category updated successfully',
    //         'category' => $category,
    //     ], 200);
    // }


    // public function destroy(Category $category)
    // {
    //     $category->delete();

    //     return response()->json([
    //         'message' => 'Category deleted successfully',
    //     ], 200);
    // }

    public function index()
    {
        // Load categories with their tags
        $categories = Category::with('tags')->get();

        return response()->json($categories, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'image' => $path,
        ]);

        // Attach tags if provided
        if (!empty($validated['tag_ids'])) {
            $category->tags()->sync($validated['tag_ids']);
        }

        // Load tags relationship for response
        $category->load('tags');

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json($category->load(['products', 'tags']), 200);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if (!empty($category->image) && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        // Update the slug if name changes
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Remove tag_ids from validated data before updating category
        $tagIds = $validated['tag_ids'] ?? null;
        unset($validated['tag_ids']);

        // Update the category
        $category->update($validated);

        // Sync tags (this will replace all existing tags with new ones)
        if ($tagIds !== null) {
            $category->tags()->sync($tagIds);
        }

        // Load tags relationship for response
        $category->load('tags');

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    public function destroy(Category $category)
    {
        // Delete image if exists
        if (!empty($category->image) && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        // Tags will be automatically detached due to cascade delete on pivot table
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}

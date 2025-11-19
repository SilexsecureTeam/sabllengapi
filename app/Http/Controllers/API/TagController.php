<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user && $user->role === 'admin') {
            $tags = Tag::with('categories')->get();
        } else {
            $tags = Tag::with([
                'categories' => function ($query) {
                    $query->where('is_active', true);
                }
            ])->where('is_active', true)->get();
        }

        return response()->json($tags);
    }


    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|unique:tags,name',
        'is_active' => 'boolean',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $imagePath = null;

    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('tags', 'public');
    }

    $tag = Tag::create([
        'name' => $validated['name'],
        'slug' => Str::slug($validated['name']),
        'is_active' => $validated['is_active'] ?? true,
        'image' => $imagePath,
    ]);

    return response()->json($tag, 201);
}


    public function show($id)
    {
        $tag = Tag::with('categories')->findOrFail($id);
        return response()->json([
            $tag
        ]);
    }

   public function update(Request $request, $id)
{
    $tag = Tag::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|unique:tags,name,' . $tag->id,
        'is_active' => 'boolean',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // Upload new image if provided
    if ($request->hasFile('image')) {

        // delete old image if exists
        if ($tag->image && Storage::disk('public')->exists($tag->image)) {
            Storage::disk('public')->delete($tag->image);
        }

        $imagePath = $request->file('image')->store('tags', 'public');
        $tag->image = $imagePath;
    }

    $tag->name = $validated['name'];
    $tag->slug = Str::slug($validated['name']);
    $tag->is_active = $validated['is_active'] ?? $tag->is_active;

    $tag->save();

    return response()->json([
        'message' => 'Tag updated successfully',
        'tag' => $tag,
    ], 200);
}


    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}

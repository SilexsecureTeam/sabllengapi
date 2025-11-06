<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'admin') {
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
            'is_active' => 'boolean'
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            $tag
        ], 201);
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
        ]);

        $tag->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'is_active' => $validated['is_active'] ?? $tag->is_active,
        ]);

        return response()->json([
            'Message' => 'Tag updated successfully',
            'Tag' => $tag
        ], 200);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}

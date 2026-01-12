<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class HeroController extends Controller
{
    public function index()
    {
        $slides = HeroSlide::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $slides
        ], 200);
    }

    /**
     * Store a newly created hero slide.
     * POST /api/hero-slides
     */
    public function store(Request $request)
    {
        // 🚫 Limit to only 6 slides
        if (HeroSlide::count() >= 6) {
            return response()->json([
                'success' => false,
                'message' => 'You can only upload a maximum of 6 hero slides.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'    => 'required|string|min:3|max:255',
            'image'    => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'required|url|max:255',
            'order'    => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Upload image
            $data['image_path'] = $request->file('image')
                ->store('hero-slides', 'public');

            $data['order'] = $request->order ?? 0;

            $slide = HeroSlide::create([
                'title'      => $data['title'],
                'image_path' => $data['image_path'],
                'link_url'   => $data['link_url'],
                'order'      => $data['order'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hero slide created successfully',
                'data'    => $slide
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create hero slide',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified hero slide.
     * GET /api/hero-slides/{id}
     */
    public function show(HeroSlide $heroSlide)
    {
        return response()->json([
            'success' => true,
            'data' => $heroSlide
        ], 200);
    }

    /**
     * Update the specified hero slide.
     * PUT/PATCH /api/hero-slides/{id}
     */
    public function update(Request $request, HeroSlide $heroSlide)
    {
        // Validation
        $slide = HeroSlide::findOrFail($heroSlide);

        $validator = Validator::make($request->all(), [
            'title'    => 'required|string|min:3|max:255',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'required|url|max:255',
            'order'    => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // ✅ Replace image ONLY if a new one is uploaded
            if ($request->hasFile('image')) {
                // Delete old image
                if ($slide->image_path && Storage::disk('public')->exists($slide->image_path)) {
                    Storage::disk('public')->delete($slide->image_path);
                }

                // Store new image
                $data['image_path'] = $request->file('image')
                    ->store('hero-slides', 'public');
            }

            $slide->update([
                'title'      => $data['title'],
                'image_path' => $data['image_path'] ?? $slide->image_path,
                'link_url'   => $data['link_url'],
                'order'      => $data['order'] ?? $slide->order,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hero slide updated successfully',
                'data'    => $slide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update hero slide',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified hero slide.
     * DELETE /api/hero-slides/{id}
     */
    public function destroy(HeroSlide $heroSlide)
    {
        try {
            // Delete image from storage
            if ($heroSlide->image_path && Storage::disk('public')->exists($heroSlide->image_path)) {
                Storage::disk('public')->delete($heroSlide->image_path);
            }

            $heroSlide->delete();

            return response()->json([
                'success' => true,
                'message' => 'Hero slide deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete hero slide',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder hero slides.
     * POST /api/hero-slides/reorder
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:hero_slides,id',
            'slides.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->slides as $slideData) {
                HeroSlide::where('id', $slideData['id'])
                    ->update(['order' => $slideData['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Slides reordered successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder slides',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

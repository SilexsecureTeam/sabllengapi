<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * GET /api/teams
     */
    public function index()
    {
        return response()->json(
            Team::where('is_active', true)->latest()->get()
        );
    }

    // GET team for Admin
    public function adminIndex()
    {
        return Team::latest()->get();
    }

    /**
     * POST /api/teams
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'nullable|string|max:255',
            'position'  => 'nullable|string|max:255',
            'photo'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bio'       => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name',
            'position',
            // 'photo',
            'bio',
            'is_active'
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('teams', 'public');

            // ✅ Store FULL URL
            $data['photo'] = Storage::disk('public')->url($path);
        }
// dd($photo);
        $team = Team::create($data);

        return response()->json([
            'message' => 'Team member created successfully',
            'data' => $team,
            // 'image' => $data['photo']
        ], 201);
    }

    /**
     * GET /api/teams/{id}
     */
    public function show($id)
    {
        $team = Team::findOrFail($id);

        return response()->json($team);
    }

    /**
     * PUT /api/teams/{id}
     */
    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'position'  => 'sometimes|string|max:255',
            'photo'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bio'       => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name',
            'position',
            'bio',
            'is_active'
        ]);

        if ($request->hasFile('photo')) {

            /** 🔥 Delete old photo safely */
            if ($team->photo) {
                $path = str_replace(
                    Storage::disk('public')->url(''),
                    '',
                    $team->photo
                );

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            /** ✅ Store new photo as URL */
            $path = $request->file('photo')->store('teams', 'public');
            $data['photo'] = Storage::disk('public')->url($path);
        }

        $team->update($data);

        return response()->json([
            'message' => 'Team member updated successfully',
            'data' => $team
        ], 200);
    }

    /**
     * DELETE /api/teams/{id}
     */
    public function destroy($id)
    {
        $team = Team::findOrFail($id);

        if ($team->photo) {
            Storage::disk('public')->delete($team->photo);
        }

        $team->delete();

        return response()->json([
            'message' => 'Team member deleted successfully'
        ]);
    }
}

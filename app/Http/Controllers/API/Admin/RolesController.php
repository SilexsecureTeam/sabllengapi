<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{

    public function store(Request $request)
    {

        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized action.',
            ], 401);
        }

        // Validate the role
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        // Create the role
        $role = Role::create($validated);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin'])) {
            return response()->json([
                'message' => 'Unauthorized action.',
            ], 401);
        }

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Delete a role
     */
    public function destroy($id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin'])) {
            return response()->json([
                'message' => 'Unauthorized action.',
            ], 401);
        }

        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Fetch all roles (for dropdown)
     */
    public function index()
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin'])) {
            return response()->json([
                'message' => 'Unauthorized action.',
            ], 401);
        }

        return response()->json([
            'data' => Role::all()
        ]);
    }
}

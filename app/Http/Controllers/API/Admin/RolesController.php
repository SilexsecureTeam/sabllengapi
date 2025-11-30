<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    private function ensureSuperadmin()
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized: Only Superadmin can perform this action.');
        }
    }

    public function store(Request $request)
    {
        // Restrict to superadmin
        $this->ensureSuperadmin();

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
        $this->ensureSuperadmin();

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
        $this->ensureSuperadmin();

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
        $this->ensureSuperadmin();

        return response()->json([
            'data' => Role::all()
        ]);
    }
}

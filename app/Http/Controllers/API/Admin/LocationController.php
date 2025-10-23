<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LocationController extends Controller
{
    public function nigeriaLocation()
    {
        $path = resource_path('data/nigerian-locations.json');

        if (!File::exists($path)) {
            return response()->json([
                'message' => 'Data file not found'
            ], 404);
        }

        $data = json_decode(File::get($path), true);
        return response()->json($data);
    }
}

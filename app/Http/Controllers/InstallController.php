<?php

namespace App\Http\Controllers;

use App\Models\Install;
use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function extract($id)
    {
        $install = Install::findOrFail($id);

        if (!$install->downloaded_at) {
            if (!$install->download()) {
                return response()->json(['error' => 'Download failed.'], 500);
            }
        }

        if ($install->extract()) {
            return response()->json(['message' => 'Extraction successful.']);
        }

        return response()->json(['error' => 'Extraction failed.'], 500);
    }

    public function show($id)
    {
        $args = [];
        $args['install'] = Install::find($id);

        return view('main.install.show', $args);
    }
}

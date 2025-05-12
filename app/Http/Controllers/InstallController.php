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
                session()->flash('error', 'Download failed.');
            }
        }

        if ($install->extract()) {
            session()->flash('success', 'Extraction successful.');
        }

        return redirect()->route('install.show', $id);
    }

    public function show($id)
    {
        $args = [];
        $args['install'] = Install::find($id);

        return view('main.install.show', $args);
    }
}

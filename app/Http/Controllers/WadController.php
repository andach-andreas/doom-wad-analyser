<?php

namespace App\Http\Controllers;

use App\Models\Wad;
use Illuminate\Http\Request;

class WadController extends Controller
{
    public function analyze()
    {
        $wad = new Wad();
        $wad->insertIntoDatabase(storage_path('wads/doom2.wad'));
    }

    public function downloadAndExtract($id)
    {
        $wad = Wad::find($id);
        $wad->downloadAndExtract();

        return redirect()->route('wad.show', $id);
    }

    public function index()
    {
        $args = [];
        $args['wads'] = Wad::all();

        return view('main.wad.index', $args);
    }

    public function show($id)
    {
        $args = [];
        $args['wad'] = Wad::find($id);

        return view('main.wad.show', $args);
    }
}

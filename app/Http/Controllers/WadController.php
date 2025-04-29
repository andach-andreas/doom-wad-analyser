<?php

namespace App\Http\Controllers;

use App\Models\Wad;
use Illuminate\Http\Request;

class WadController extends Controller
{
    public function analyze()
    {
        $wad = new Wad(storage_path('wads/doom2.wad'));
        dd($wad->lumps, $wad->type, $wad->numLumps, $wad->dirOffset, $wad->skippedLumps);
    }
}

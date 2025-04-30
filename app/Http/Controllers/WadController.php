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
}

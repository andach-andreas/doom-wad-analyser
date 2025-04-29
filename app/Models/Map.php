<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public string $name;
    public int $lumpIndex;
    public array $lumps = [];
    public array $sectors = [];
    public array $things = [];

    public function __construct(int $index, array $allLumps)
    {
        $this->name = trim($allLumps[$index]->name);
        $this->lumpIndex = $index;

        $this->parseAssociatedLumps($allLumps);
        $this->parseThings();
    }

    public function parseAssociatedLumps(array $allLumps): void
    {
        // Lumps that define map data, in typical order
        $mapLumpNames = [
            'THINGS',
            'LINEDEFS',
            'SIDEDEFS',
            'VERTEXES',
            'SEGS',
            'SSECTORS',
            'NODES',
            'SECTORS',
            'REJECT',
            'BLOCKMAP',
            'BEHAVIOR', // for Hexen format maps
        ];

        $this->lumps = [];

        for ($i = $this->lumpIndex + 1; $i < count($allLumps); $i++) {
            $lump = $allLumps[$i];
            $name = strtoupper(trim($lump->name));

            if (in_array($name, $mapLumpNames, true)) {
                $this->lumps[$name] = $lump;
            } else {
                break;
            }
        }
    }

    public function parseThings()
    {
        $thingLump = $this->lumps['THINGS'] ?? null;

        if (!$thingLump)
        {
            return;
        }

        for ($i = 0; $i < strlen($thingLump->data); $i += 10) {
            $entry = substr($thingLump->data, $i, 10);
            if (strlen($entry) < 10) continue;
            $this->things[] = Thing::fromBinary($entry);
        }
    }
}

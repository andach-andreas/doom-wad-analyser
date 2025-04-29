<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public string $name;
    public int $lumpIndex;
    public array $lumps = [];

    public function __construct(int $index, array $allLumps)
    {
        $this->name = trim($allLumps[$index]->name);
        $this->lumpIndex = $index;

        $this->parseAssociatedLumps($allLumps);
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

    public function hasLump(string $lumpName): bool
    {
        return isset($this->lumps[strtoupper($lumpName)]);
    }

    public function getLump(string $lumpName): ?Lump
    {
        return $this->lumps[strtoupper($lumpName)] ?? null;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public array $enemies = [];
    public int $lumpIndex;
    public string $name;
    public array $lumps = [];
    public array $sectors = [];
    public array $stats = [];
    public array $things = [];
    public array $thingsLookup = [];

    public function __construct(int $lumpIndex, array $allLumps, array $thingsLookup)
    {
        $this->lumpIndex = $lumpIndex;
        $this->name = trim($allLumps[$lumpIndex]->name);
        $this->thingsLookup = $thingsLookup;

        $this->parseAssociatedLumps($allLumps);
        $this->parseThings();

        $this->cacheStats();
        $this->extractEnemies();
    }

    public function cacheStats(): void
    {
        $this->stats = [
            'linedefs'   => $this->countLumpEntries('LINEDEFS', 14),
            'sidedefs'   => $this->countLumpEntries('SIDEDEFS', 30),
            'vertexes'   => $this->countLumpEntries('VERTEXES', 4),
            'segs'       => $this->countLumpEntries('SEGS', 12),
            'ssectors'   => $this->countLumpEntries('SSECTORS', 4),
            'nodes'      => $this->countLumpEntries('NODES', 28),
            'sectors'    => $this->countLumpEntries('SECTORS', 26),
            'secretSectors' => $this->countSecretSectors(),
        ];
    }

    protected function countLumpEntries(string $lumpName, int $entrySize): int
    {
        if (!isset($this->lumps[$lumpName])) return 0;
        return intdiv(strlen($this->lumps[$lumpName]->data), $entrySize);
    }

    protected function countSecretSectors(): int
    {
        if (!isset($this->lumps['SECTORS'])) return 0;

        $data = $this->lumps['SECTORS']->data;
        $count = 0;
        $entrySize = 26;
        $numSectors = intdiv(strlen($data), $entrySize);

        for ($i = 0; $i < $numSectors; $i++) {
            $offset = $i * $entrySize;
            $flags = unpack('v', substr($data, $offset + 20, 2))[1]; // Flags are at offset 20 in the sector struct
            if ($flags & 0x8000) $count++;
        }

        return $count;
    }

    private function extractEnemies(): void
    {
        foreach ($this->things as $thing) {
            $thingType = $this->thingsLookup[$thing->type]['type'] ?? '';

            if ($thingType === 'monster') {
                $this->enemies[] = $thing;
            }
        }
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

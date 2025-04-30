<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public array $enemies = [];
    public int $enemiesCount = 0;
    public int $lumpIndex;
    public array $lumps = [];
    public array $sectors = [];
    public array $stats = [];
    public array $things = [];
    public array $weapons = [];
    public array $weaponsAvailable = [];
    public int $weaponsCount = 0;

    protected $fillable = ['music_lump_id', 'next_level_map_id', 'next_secret_level_map_id', 'wad_id', 'name'];

    public function lumps()
    {
        return $this->belongsToMany(Lump::class, 'link_lumps_maps')
            ->withPivot('is_header') // Include the is_header flag in the pivot table
            ->withTimestamps();
    }

    public function music()
    {
        return $this->belongsTo(Lump::class, 'music_lump_id');
    }

    public function nextLevel()
    {
        return $this->belongsTo(Map::class, 'next_level_map_id');
    }

    public function nextSecretLevel()
    {
        return $this->belongsTo(Map::class, 'next_secret_level_map_id');
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
        die('rewrite extractEnemies');
        foreach ($this->things as $thing) {
            $thingType = $this->thingsLookup[$thing->type]['type'] ?? '';

            if ($thingType === 'monster') {
                $this->enemies[] = $thing;
            }
        }

        $this->enemiesCount = count($this->enemies);
    }

    private function extractWeapons(): void
    {
        die('rewrite extractWeapons');
        foreach ($this->things as $thing) {
            $thingType = $this->thingsLookup[$thing->type]['type'] ?? '';

            if ($thingType === 'weapon') {
                $this->weapons[] = $thing;
            }
        }

        $this->weaponsCount = count($this->weapons);
    }

    private function extractWeaponsAvailable(): void
    {
        die('rewrite extractWeaponsAvailable');
        $weaponCount = [];

        foreach ($this->weapons as $weapon)
        {
            $weaponCount[$weapon->type] = $weaponCount[$weapon->type] ?? 0 + 1;
        }

        dd($this->name, $this->weapons);

        foreach ($this->things as $thingID => $thing) {
            $weaponName = $this->thingsLookup[$thing->type]['name'] ?? '';
            $thingType = $this->thingsLookup[$thing->type]['type'] ?? '';

            if ($thingType === 'weapon') {
                $this->weaponsAvailable[$weaponName] = $weaponCount[$thing->type] ?? 0;
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

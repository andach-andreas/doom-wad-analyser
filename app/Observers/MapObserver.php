<?php

namespace App\Observers;

use App\Models\Lump;
use App\Models\Map;

class MapObserver
{
    /**
     * Handle the "creating" event.
     *
     * @param  \App\Models\Map  $map
     * @return void
     */
    public function creating(Map $map)
    {
        // Ensure the map name is set to the lump's name (from the lump relationship)
        if ($map->lump) {
            $map->name = $map->lump->name;
        }

        // Cache stats and update the necessary columns
        $map->linedefs = $this->countLumpEntries($map->lump, 'LINEDEFS', 14);
        $map->sidedefs = $this->countLumpEntries($map->lump, 'SIDEDEFS', 30);
        $map->vertexes = $this->countLumpEntries($map->lump, 'VERTEXES', 4);
        $map->segs = $this->countLumpEntries($map->lump, 'SEGS', 12);
        $map->ssectors = $this->countLumpEntries($map->lump, 'SSECTORS', 4);
        $map->nodes = $this->countLumpEntries($map->lump, 'NODES', 28);
        $map->sectors = $this->countLumpEntries($map->lump, 'SECTORS', 26);
        $map->secretSectors = $this->countSecretSectors($map->lump);
    }

    protected function countLumpEntries(Lump $lump, string $lumpName, int $entrySize): int
    {
        return intdiv(strlen($lump->data), $entrySize);
    }

    protected function countSecretSectors(Lump $lump): int
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
}

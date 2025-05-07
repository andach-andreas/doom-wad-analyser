<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Wad extends Model
{
    public string $type;
    public int $numLumps;
    public int $dirOffset;

    public array $parsedLumps = [];
    public string $path;
    public array $skippedLumps = [];

    public function compLevel()
    {
        return $this->belongsTo(CompLevel::class);
    }

    public function installs(): BelongsToMany
    {
        return $this->belongsToMany(Install::class, 'link_installs_wads')
            ->withPivot(['is_compatible', 'notes'])
            ->withTimestamps();
    }

    public function iwad()
    {
        return $this->belongsTo(Wad::class, 'iwad_id');
    }

    public function lumps()
    {
        return $this->hasMany(Lump::class);
    }

    public function maps()
    {
        return $this->hasMany(Map::class, 'wad_id');
    }

    public function ports(): BelongsToMany
    {
        return $this->belongsToMany(Port::class, 'link_ports_wads');
    }

    public function types()
    {
        return $this->hasMany(Type::class);
    }

    public function allTypes()
    {
        return Type::whereNull('wad_id')
            ->orWhere('wad_id', $this->id)
            ->orderByRaw('wad_id IS NULL') // So overrides appear after for possible resolution
            ->get()
            ->unique('type'); // Keep overridden ones
    }

    public function insertIntoDatabase(string $filePath)
    {
        $this->path = $filePath;
        $this->filename = basename($filePath);
        $this->name = basename($filePath);
        $this->save();

        $this->insertIntoDatabaseLumps();
        $this->insertIntoDatabaseMaps();

        $this->load('maps');
        foreach ($this->maps as $map)
        {
            $map->insertIntoDatabase();
        }
    }

    private function insertIntoDatabaseLumps(): void
    {
        $handle = fopen($this->path, 'rb');
        if (!$handle) {
            throw new \Exception("Unable to open WAD file: {$this->path}");
        }

        // Read the header to get the number of lumps and the directory offset
        $header = fread($handle, 12);
        [$this->type, $this->numLumps, $this->dirOffset] = array_values(unpack('a4type/VnumLumps/VdirOffset', $header));

        // Move to the directory offset to read the lump entries
        fseek($handle, $this->dirOffset);

        // Loop through each lump entry
        for ($i = 0; $i < $this->numLumps; $i++) {
            $entry = fread($handle, 16);
            if (strlen($entry) < 16) {
                $this->skippedLumps[] = $i;
                continue;
            }
            [$offset, $size, $name] = array_values(unpack('Voffset/Vsize/a8name', $entry));

            $data = null;
            if ($size > 0) {
                $curPos = ftell($handle);
                fseek($handle, $offset);
                $data = fread($handle, $size);
                fseek($handle, $curPos); // return to directory read position
            }

            $this->lumps()->create([
                'name' => $name,
                'offset' => $offset,
                'size' => $size,
                'data' => $data,
            ]);
        }

        fclose($handle);
    }

    private function insertIntoDatabaseMaps()
    {
        $validLumps = [
            'THINGS',
            'LINEDEFS',
            'SIDEDEFS',
            'VERTEXES',
            'SEGS',
            'SSECTORS',
            'NODES',
            'SECTORS',
        ];

        $isProcessingMap = false;
        $map = null;

        foreach ($this->lumps as $lump) {
            if ($lump->isMap()) {
                $map = Map::create([
                    'wad_id' => $this->id,
                    'name' => $lump->name,
                ]);
                $map->lumps()->attach($lump->id, ['is_header' => true]);

                $isProcessingMap = true;

                continue;
            }

            if ($isProcessingMap) {
                if (in_array($lump->name, $validLumps)) {
                    $map->lumps()->attach($lump->id, ['is_header' => false]);
                } else {
                    $isProcessingMap = false;
                }
            }
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wad extends Model
{
    public string $type;
    public int $numLumps;
    public int $dirOffset;
    public array $maps = [];

    public array $parsedLumps = [];
    public string $path;
    public array $skippedLumps = [];

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("WAD file not found: $filePath");
        }

        $this->path = $filePath;
    }

    public function compLevel()
    {
        return $this->belongsTo(CompLevel::class);
    }

    public function lumps()
    {
        return $this->hasMany(Lump::class);
    }

    public function maps()
    {
        return $this->hasManyThrough(Map::class, Lump::class);
    }

    private function createMaps()
    {
        $mapRegex = '/^(E[1-4]M[1-9]|MAP[0-9]{2})$/';

        foreach ($this->parsedLumps as $index => $lump) {
            if (preg_match($mapRegex, $lump->name)) {
                $map = new Map($index, $this->parsedLumps);
                $this->maps[] = $map;
            }
        }
    }

    public function insertIntoDatabase()
    {
        $this->filename = basename($this->path);
        $this->name = basename($this->path);
        $this->save();

        $this->parse();
        $this->createMaps();
    }

    private function parse(): void
    {
        $handle = fopen($this->path, 'rb');
        if (!$handle) {
            throw new \Exception("Unable to open WAD file: {$this->path}");
        }

        $this->parseHeader($handle);

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

    private function parseHeader($handle): void
    {
        // Read the header to get the number of lumps and the directory offset
        $header = fread($handle, 12);
        [$this->type, $this->numLumps, $this->dirOffset] = array_values(unpack('a4type/VnumLumps/VdirOffset', $header));
    }
}

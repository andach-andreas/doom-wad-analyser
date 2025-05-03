<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public array $enemies = [];
    public int $enemiesCount = 0;
    public int $lumpIndex;
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

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }

    public function things()
    {
        return $this->hasMany(Thing::class);
    }

    public function insertIntoDatabase()
    {
        $this->insertIntoDatabaseStats();
    }

    public function insertIntoDatabaseSectors(Lump $lump)
    {
        $data = $lump->data;
        $entrySize = 26;
        $num = intdiv(strlen($data), $entrySize);

        for ($i = 0; $i < $num; $i++) {
            $offset = $i * $entrySize;
            $entry = substr($data, $offset, $entrySize);

            $fields = unpack('vfloor_height/vceiling_height/a8floor_texture/a8ceiling_texture/vlight_level/vtype/vtag', $entry);

            $this->sectors()->create([
                'floor_height'    => $fields['floor_height'],
                'ceiling_height'  => $fields['ceiling_height'],
                'floor_texture'   => trim($fields['floor_texture']),
                'ceiling_texture' => trim($fields['ceiling_texture']),
                'light_level'     => $fields['light_level'],
                'type'            => $fields['type'],
                'tag'             => $fields['tag'],
            ]);
        }
    }

    public function insertIntoDatabaseStats()
    {
        foreach ($this->lumps as $lump) {
            switch ($lump->name) {
                case 'LINEDEFS':
                    $this->linedefs = $this->countLumpEntries($lump, 14);
                    break;
                case 'SIDEDEFS':
                    $this->sidedefs = $this->countLumpEntries($lump, 30);
                    break;
                case 'VERTEXES':
                    $this->vertexes = $this->countLumpEntries($lump, 4);
                    break;
                case 'SEGS':
                    $this->segs = $this->countLumpEntries($lump, 12);
                    break;
                case 'SSECTORS':
                    $this->ssectors = $this->countLumpEntries($lump, 4);
                    break;
                case 'NODES':
                    $this->nodes = $this->countLumpEntries($lump, 28);
                    break;
                case 'SECTORS':
                    $this->sectors = $this->countLumpEntries($lump, 26);
                    $this->insertIntoDatabaseSectors($lump);
                    break;
                case 'THINGS' :
                    $this->insertIntoDatabaseThings($lump);
                    break;
            }
        }

        $this->load('sectors');
        $this->secretSectors = $this->sectors()->secret()->count();

        $this->save();
    }

    protected function insertIntoDatabaseThings(Lump $lump): void
    {
        if (!$lump || !$lump->data || $lump->name !== 'THINGS') return;

        $data = $lump->data;
        $entrySize = 10;
        $numThings = intdiv(strlen($data), $entrySize);

        for ($i = 0; $i < $numThings; $i++) {
            $offset = $i * $entrySize;
            $thingData = substr($data, $offset, $entrySize);
            $unpacked = unpack('sx/sy/vangle/vtype/vflags', $thingData);

            Thing::create([
                'map_id'           => $this->id,
                'wad_id'           => $this->wad_id,
                'thing_type_id'    => 0,
                'x'                => $unpacked['x'],
                'y'                => $unpacked['y'],
                'angle'            => $unpacked['angle'],
                'type'             => $unpacked['type'],
                'flags'            => $unpacked['flags'],
                'flag_ambush'      => (bool)($unpacked['flags'] & 0x0004),
                'flag_single'      => (bool)($unpacked['flags'] & 0x0001),
                'flag_coop'        => (bool)($unpacked['flags'] & 0x0002),
                'flag_deathmatch'  => (bool)($unpacked['flags'] & 0x0008),
                'flag_multiplayer' => (bool)($unpacked['flags'] & 0x0010),
                'flag_friendly'    => (bool)($unpacked['flags'] & 0x0100),
                'flag_skill1'      => (bool)($unpacked['flags'] & 0x0001),
                'flag_skill2'      => (bool)($unpacked['flags'] & 0x0002),
                'flag_skill3'      => (bool)($unpacked['flags'] & 0x0004),
            ]);
        }
    }

    public function renderTopDownMapImage(string $outputPath): void
    {
        $vertexLump = $this->lumps()->where('name', 'VERTEXES')->first();
        $linedefLump = $this->lumps()->where('name', 'LINEDEFS')->first();

        if (!$vertexLump || !$linedefLump) return;

        $vertices = [];
        $entrySize = 4;
        $data = $vertexLump->data;
        $numEntries = intdiv(strlen($data), $entrySize);

        $minX = $minY = PHP_INT_MAX;
        $maxX = $maxY = PHP_INT_MIN;

        // Parse vertices
        for ($i = 0; $i < $numEntries; $i++) {
            $offset = $i * $entrySize;
            $x = unpack('s', substr($data, $offset, 2))[1];
            $y = unpack('s', substr($data, $offset + 2, 2))[1];
            $vertices[$i] = ['x' => $x, 'y' => $y];
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }

        $width = 800;
        $height = 800;

        $scale = min($width / ($maxX - $minX + 1), $height / ($maxY - $minY + 1)) * 0.9;
        $offsetX = $width / 2 - ($minX + $maxX) * $scale / 2;
        $offsetY = $height / 2 + ($minY + $maxY) * $scale / 2;

        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 0, 0, 0);
        $fg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);

        // Parse linedefs
        $entrySize = 14;
        $data = $linedefLump->data;
        $numEntries = intdiv(strlen($data), $entrySize);

        for ($i = 0; $i < $numEntries; $i++) {
            $offset = $i * $entrySize;
            $v1 = unpack('v', substr($data, $offset, 2))[1];
            $v2 = unpack('v', substr($data, $offset + 2, 2))[1];

            if (!isset($vertices[$v1]) || !isset($vertices[$v2])) continue;

            [$x1, $y1] = [$vertices[$v1]['x'], $vertices[$v1]['y']];
            [$x2, $y2] = [$vertices[$v2]['x'], $vertices[$v2]['y']];

            // Transform and draw
            $sx1 = intval($x1 * $scale + $offsetX);
            $sy1 = intval(-$y1 * $scale + $offsetY);
            $sx2 = intval($x2 * $scale + $offsetX);
            $sy2 = intval(-$y2 * $scale + $offsetY);

            imageline($img, $sx1, $sy1, $sx2, $sy2, $fg);
        }

        imagepng($img, $outputPath);
        imagedestroy($img);
    }


    protected function countLumpEntries(Lump $lump, int $entrySize): int
    {
        return intdiv(strlen($lump->data), $entrySize);
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

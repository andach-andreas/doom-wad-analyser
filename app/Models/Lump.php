<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lump extends Model
{
    protected $fillable = ['wad_id', 'name', 'offset', 'size', 'data'];

    public function map()
    {
        return $this->hasOne(Map::class);
    }

    public function wad()
    {
        return $this->belongsTo(Wad::class);
    }

    public function returnDehackedArray(): array
    {
        // Only process if the lump is DEHACKED
        if (strtoupper($this->name) !== 'DEHACKED') {
            return [];
        }

        // Split data into lines
        $lines = explode("\n", $this->data);
        $things = [];

        foreach ($lines as $line) {
            if (strpos($line, "THING") !== false) {
                // Example format: THING <id> <name> <properties>
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 3) {
                    $id = (int)$parts[1];
                    $name = $parts[2];
                    $properties = array_slice($parts, 3); // All other properties for the thing
                    $things[$id] = [
                        'name' => $name,
                        'properties' => $properties,
                    ];
                }
            }
        }

        return $things;
    }
}

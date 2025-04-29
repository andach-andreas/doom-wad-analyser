<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lump extends Model
{
    public $name;
    public $offset;
    public $size;
    public $data;

    public function __construct(string $name, int $offset, int $size, $data = '')
    {
        $this->name = trim(strtoupper($name));
        $this->offset = $offset;
        $this->size = $size;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getSize(): int
    {
        return $this->size;
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

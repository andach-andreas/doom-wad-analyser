<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thing extends Model
{
    public function __construct(
        public int $x,
        public int $y,
        public int $angle,
        public int $type,
        public int $flags
    ) {}

    public static function fromBinary(string $data): self
    {
        [$x, $y, $angle, $type, $flags] = array_values(unpack('sx/sy/vangle/vtype/vflags', $data));
        return new self($x, $y, $angle, $type, $flags);
    }
}

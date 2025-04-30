<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thing extends Model
{
    public int $angle;
    public array $flagInfo;
    public int $flags;
    public int $type;
    public int $x;
    public int $y;

    public function __construct(int $x, int $y, int $angle, int $type, int $flags)
    {
        $this->x = $x;
        $this->y = $y;
        $this->angle = $angle;
        $this->type = $type;
        $this->flags = $flags;

        $this->parseFlags();
    }

    public static function fromBinary(string $data): self
    {
        [$x, $y, $angle, $type, $flags] = array_values(unpack('sx/sy/vangle/vtype/vflags', $data));
        return new self($x, $y, $angle, $type, $flags);
    }

    private function parseFlags(): void
    {
        $this->flagInfo = [
            'easy'          => (bool)($this->flags & 0x0001),
            'medium'        => (bool)($this->flags & 0x0002),
            'hard'          => (bool)($this->flags & 0x0004),
            'ambush'        => (bool)($this->flags & 0x0008),
            'multiplayer'   => (bool)($this->flags & 0x0010),
            'notDeathmatch' => (bool)($this->flags & 0x0020),
            'notCoop'       => (bool)($this->flags & 0x0040),
            'friendly'      => (bool)($this->flags & 0x0080),
        ];
    }
}

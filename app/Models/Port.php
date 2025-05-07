<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Port extends Model
{
    protected $fillable = ['name'];

    public function installs(): HasMany
    {
        return $this->hasMany(Install::class);
    }

    public function wads(): BelongsToMany
    {
        return $this->belongsToMany(Wad::class, 'link_ports_wads');
    }
}

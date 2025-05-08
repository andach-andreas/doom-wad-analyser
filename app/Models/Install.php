<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Install extends Model
{
    protected $fillable = ['port_id', 'version', 'path'];

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function wads(): BelongsToMany
    {
        return $this->belongsToMany(Wad::class, 'link_installs_wads')
            ->withPivot(['is_compatible', 'notes'])
            ->withTimestamps();
    }

    public function downloadAndExtract()
    {
        if (!$this->download_url) return;

        $zipPath = storage_path("app/temp_{$this->slug}.zip");

        Http::withHeaders(['User-Agent' => 'LaravelApp'])->get($this->download_url)->sink($zipPath);

        $extractPath = Storage::disk('dsda')->path("{$this->port->slug}/{$this->version}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();

            $this->update([
                'downloaded_at' => now(),
                'extracted_at' => now(),
            ]);
        }

        unlink($zipPath);
    }
}

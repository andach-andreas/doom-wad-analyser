<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Install extends Model
{
    protected $fillable = ['port_id', 'version', 'path', 'download_url',
        'downloaded_at', 'extracted_at'];

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
        $downloadUrl = $this->download_url;
        $portSlug = $this->port->slug;
        $version = $this->version;

        $disk = Storage::disk('ports');
        $tmpDir = "tmp";
        $destDir = "{$portSlug}/{$version}";

        // Ensure directories exist
        $disk->makeDirectory($tmpDir);
        $disk->makeDirectory($destDir);

        // Build temporary ZIP path
        $tmpFilename = "{$tmpDir}/" . Str::slug("{$portSlug}-{$version}") . ".zip";
        $tmpPath = $disk->path($tmpFilename);

        // Stream and download ZIP directly
        $response = Http::withOptions(['sink' => $tmpPath])->get($downloadUrl);

        if (!$response->successful()) {
            throw new \Exception("Failed to download ZIP from: {$downloadUrl}");
        }

        // Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) === true) {
            $zip->extractTo($disk->path($destDir));
            $zip->close();
        } else {
            throw new \Exception("Unable to extract ZIP: {$tmpPath}");
        }

        // Update install timestamps
        $this->downloaded_at = date('Y-m-d H:i:s');
        $this->extracted_at = date('Y-m-d H:i:s');
        $this->save();

        // Clean up temp file
        $disk->delete($tmpFilename);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Port extends Model
{
    protected $fillable = ['name', 'slug', 'github_url'];

    public function installs()
    {
        return $this->hasMany(Install::class);
    }

    public function syncReleases()
    {
        $releases = $this->fetchGitHubReleases();

        foreach ($releases as $release) {
            $version = $release['tag_name'];
            $slug = "{$this->slug}-{$version}";

            if ($this->installs()->where('version', $version)->exists()) {
                continue;
            }

            $zipAsset = collect($release['assets'])->first(fn($a) =>
            str_ends_with($a['name'], '.zip')
            );

            $downloadUrl = $zipAsset['browser_download_url'] ?? null;

            $this->installs()->create([
                'version'       => $version,
                'slug'          => $slug,
                'download_url'  => $downloadUrl,
                'available'     => !is_null($downloadUrl),
            ]);
        }
    }

    protected function fetchGitHubReleases(): array
    {
        if (!$this->github_url) return [];

        $matches = [];
        preg_match('#github\.com/([^/]+/[^/]+)#', $this->github_url, $matches);
        if (!isset($matches[1])) return [];

        $apiUrl = "https://api.github.com/repos/{$matches[1]}/releases";

        return Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'LaravelApp'
        ])->get($apiUrl)->json() ?? [];
    }

    public function downloadAndExtractLatest()
    {
        $install = $this->installs()
            ->whereNotNull('download_url')
            ->orderByDesc('version')
            ->first();

        if ($install) {
            $install->downloadAndExtract();
        }
    }
}

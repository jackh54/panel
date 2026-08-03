<?php

namespace Pterodactyl\Services\Helpers;

use GuzzleHttp\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Exceptions\Service\Helper\CdnVersionFetchingException;

class SoftwareVersionService
{
    public const VERSION_CACHE_KEY = 'pterodactyl:versioning_data';

    private static array $result;

    /**
     * SoftwareVersionService constructor.
     */
    public function __construct(
        protected CacheRepository $cache,
        protected Client $client,
    ) {
        self::$result = $this->cacheVersionData();
    }

    /**
     * Get the latest version of the panel from GitHub releases.
     */
    public function getPanel(): string
    {
        return Arr::get(self::$result, 'panel') ?? 'error';
    }

    /**
     * Get the URL to the latest panel release page.
     */
    public function getPanelUrl(): string
    {
        $fallback = sprintf(
            'https://github.com/%s/releases/latest',
            config('pterodactyl.cdn.panel_repo', 'jackh54/panel')
        );

        return Arr::get(self::$result, 'panel_url') ?? $fallback;
    }

    /**
     * Get the URL to the panel GitHub repository.
     */
    public function getGitHub(): string
    {
        return sprintf(
            'https://github.com/%s',
            config('pterodactyl.cdn.panel_repo', 'jackh54/panel')
        );
    }

    /**
     * Get the latest version of the daemon from the CDN servers.
     */
    public function getDaemon(): string
    {
        return Arr::get(self::$result, 'wings') ?? 'error';
    }

    /**
     * Get the URL to the discord server.
     */
    public function getDiscord(): string
    {
        return Arr::get(self::$result, 'discord') ?? 'https://pterodactyl.io/discord';
    }

    /**
     * Get the URL for donations.
     */
    public function getDonations(): string
    {
        return Arr::get(self::$result, 'donations') ?? 'https://github.com/sponsors/matthewpi';
    }

    /**
     * Determine if the current version of the panel is the latest.
     */
    public function isLatestPanel(): bool
    {
        if (config('app.version') === 'canary') {
            return true;
        }

        $latest = $this->getPanel();
        if ($latest === 'error') {
            return true;
        }

        return version_compare(config('app.version'), $latest) >= 0;
    }

    /**
     * Determine if a passed daemon version string is the latest.
     */
    public function isLatestDaemon(string $version): bool
    {
        if ($version === 'develop') {
            return true;
        }

        return version_compare($version, $this->getDaemon()) >= 0;
    }

    /**
     * Keeps the versioning cache up-to-date with the latest results from GitHub / CDN.
     */
    protected function cacheVersionData(): array
    {
        return $this->cache->remember(self::VERSION_CACHE_KEY, CarbonImmutable::now()->addMinutes(config('pterodactyl.cdn.cache_time', 60)), function () {
            $data = $this->fetchCdnData();
            $panel = $this->fetchPanelReleaseData();

            return array_merge($data, $panel);
        });
    }

    /**
     * Fetch Wings / community metadata from the official CDN.
     */
    protected function fetchCdnData(): array
    {
        try {
            $response = $this->client->request('GET', config('pterodactyl.cdn.url'));

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true) ?? [];
                // Panel version comes from this fork's GitHub releases, not upstream CDN.
                unset($data['panel']);

                return $data;
            }

            throw new CdnVersionFetchingException();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Fetch the latest panel version from this fork's GitHub releases.
     */
    protected function fetchPanelReleaseData(): array
    {
        $repo = config('pterodactyl.cdn.panel_repo', 'jackh54/panel');

        try {
            $response = $this->client->request('GET', "https://api.github.com/repos/{$repo}/releases/latest", [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => config('app.name', 'PandaScript') . '-Panel',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new CdnVersionFetchingException();
            }

            $json = json_decode($response->getBody(), true) ?? [];
            $tag = Arr::get($json, 'tag_name');

            if (!$tag) {
                return [];
            }

            return [
                'panel' => ltrim($tag, 'vV'),
                'panel_url' => Arr::get($json, 'html_url') ?? "https://github.com/{$repo}/releases/latest",
            ];
        } catch (\Exception) {
            return [];
        }
    }
}

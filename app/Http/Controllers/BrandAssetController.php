<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class BrandAssetController extends Controller
{
    private const MAX_ASSET_BYTES = 10_485_760;

    public function show(string $type, SettingsService $settings): Response
    {
        abort_unless(in_array($type, ['logo', 'favicon'], true), 404);

        $key = $type === 'logo' ? 'branding.logo_path' : 'branding.favicon_path';
        $path = $settings->get($key);
        $asset = $this->readBrandingAsset($path);

        // Keep a trusted, bundled image available to callers even when a saved
        // branding path is stale or its contents are not a supported image.
        if ($asset === null) {
            $fallback = $type === 'logo' ? 'pwa-512.png' : 'pwa-192.png';
            $asset = $this->readBundledAsset($fallback);
        }

        abort_unless($asset !== null, 404);
        [$bytes, $mime] = $asset;

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Dynamic PWA manifest so the install prompt uses the uploaded logo and
     * the configured branding (name and theme colour) instead of the defaults.
     */
    public function manifest(SettingsService $settings): Response
    {
        $name = (string) $settings->get('branding.system_name', 'ElevateHer360');
        $shortName = (string) $settings->get('branding.short_name', 'ElevateHer360');
        $primary = (string) $settings->get('branding.primary_color', '#800000');

        $icons = [];

        $logo = $settings->get('branding.logo_path');
        $asset = $this->readBrandingAsset($logo);

        if (Route::has('branding.asset')) {
            // The endpoint itself selects the bundled fallback when the configured
            // upload is missing or invalid, so manifest and direct callers agree.
            $asset ??= $this->readBundledAsset('pwa-512.png');
            if ($asset !== null) {
                [$bytes, $mime] = $asset;
                $logoUrl = route('branding.asset', ['type' => 'logo', 'v' => hash('sha256', $bytes)]);
                $icons[] = ['src' => $logoUrl, 'sizes' => 'any', 'type' => $mime, 'purpose' => 'any'];
                $icons[] = ['src' => $logoUrl, 'sizes' => 'any', 'type' => $mime, 'purpose' => 'maskable'];
            }
        }

        // Keep the bundled icons so the app always meets the 192/512 px
        // installability requirement even when no logo has been uploaded.
        foreach ([['pwa-192.png', '192x192', 'any'], ['pwa-512.png', '512x512', 'any'], ['pwa-maskable-192.png', '192x192', 'maskable'], ['pwa-maskable-512.png', '512x512', 'maskable']] as [$file, $size, $purpose]) {
            $icons[] = ['src' => '/icons/'.$file, 'sizes' => $size, 'type' => 'image/png', 'purpose' => $purpose];
        }

        $manifest = [
            'id' => '/',
            'name' => $name,
            'short_name' => $shortName,
            'description' => 'Learning, mentorship, career development, jobs and digital resources.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => $primary,
            'lang' => 'en',
            'categories' => ['education', 'productivity'],
            'icons' => $icons,
            'shortcuts' => [
                ['name' => 'My Courses', 'short_name' => 'Courses', 'url' => '/learning/my-courses'],
                ['name' => 'Jobs', 'short_name' => 'Jobs', 'url' => '/jobs'],
                ['name' => 'Library', 'short_name' => 'Library', 'url' => '/library'],
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /** Determine the image format from its contents, never from its filename or storage metadata. */
    private function detectedImageMime(string $bytes): ?string
    {
        $image = @getimagesizefromstring($bytes);
        if ($image === false) {
            return null;
        }

        return match ($image[2] ?? null) {
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_WEBP => 'image/webp',
            IMAGETYPE_GIF => 'image/gif',
            default => null,
        };
    }

    /** Read and validate a configured branding asset without allowing storage errors to escape. */
    private function readBrandingAsset(mixed $path): ?array
    {
        if (! $this->isBrandingPath($path)) {
            return null;
        }

        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }

            if ($disk->size($path) > self::MAX_ASSET_BYTES) {
                return null;
            }

            $bytes = $disk->get($path);
            if (! is_string($bytes) || strlen($bytes) > self::MAX_ASSET_BYTES) {
                return null;
            }
            $mime = is_string($bytes) ? $this->detectedImageMime($bytes) : null;

            return $mime === null ? null : [$bytes, $mime];
        } catch (\Throwable) {
            return null;
        }
    }

    /** Read one of the fixed, bundled fallback icons and verify its actual format. */
    private function readBundledAsset(string $file): ?array
    {
        if (! in_array($file, ['pwa-192.png', 'pwa-512.png'], true)) {
            return null;
        }

        try {
            $path = public_path('icons/'.$file);
            if (! is_file($path) || filesize($path) > self::MAX_ASSET_BYTES) {
                return null;
            }
            $bytes = @file_get_contents($path);
            if (! is_string($bytes) || strlen($bytes) > self::MAX_ASSET_BYTES) {
                return null;
            }
            $mime = is_string($bytes) ? $this->detectedImageMime($bytes) : null;

            return $mime === null ? null : [$bytes, $mime];
        } catch (\Throwable) {
            return null;
        }
    }

    /** Accept only canonical relative paths beneath the branding directory. */
    private function isBrandingPath(mixed $path): bool
    {
        if (! is_string($path) || $path === '' || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        if (preg_match('/^[A-Za-z]:/', $path) === 1) {
            return false;
        }

        $segments = explode('/', $path);

        return count($segments) >= 2
            && $segments[0] === 'branding'
            && ! in_array('', $segments, true)
            && ! in_array('.', $segments, true)
            && ! in_array('..', $segments, true)
            && implode('/', $segments) === $path;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class BrandAssetController extends Controller
{
    public function show(string $type, SettingsService $settings): Response
    {
        abort_unless(in_array($type, ['logo', 'favicon'], true), 404);

        $key = $type === 'logo' ? 'branding.logo_path' : 'branding.favicon_path';
        $path = $settings->get($key);

        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
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
        if (is_string($logo) && $logo !== '' && Route::has('branding.asset')) {
            $logoUrl = route('branding.asset', ['type' => 'logo']);
            $icons[] = ['src' => $logoUrl, 'sizes' => 'any', 'type' => 'image/png', 'purpose' => 'any'];
            $icons[] = ['src' => $logoUrl, 'sizes' => 'any', 'type' => 'image/png', 'purpose' => 'maskable'];
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
}


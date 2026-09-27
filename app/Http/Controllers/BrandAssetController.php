<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Response;
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
}

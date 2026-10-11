<?php

namespace Tests\Feature;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_upload_uses_detected_image_extension_not_client_extension(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        $this->actingAs($admin)->put(route('admin.platform-settings.branding'), [
            'system_name' => 'ElevateHer360',
            'short_name' => 'EH360',
            'primary_color' => '#800000',
            'secondary_color' => '#ffffff',
            'accent_color' => '#d4af37',
            'font_family' => 'Rubik',
            'font_size' => 16,
            'logo' => UploadedFile::fake()->createWithContent('payload.jpg', file_get_contents(public_path('icons/pwa-192.png'))),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $path = app(SettingsService::class)->get('branding.logo_path');
        $this->assertMatchesRegularExpression('/^branding\/logo-[0-9]{14}-[a-f0-9]+\.png$/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_manifest_uses_uploaded_public_logo_url_and_detected_mime_type(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/logo.webp', file_get_contents(public_path('icons/pwa-192.png')));
        $settings = app(SettingsService::class);
        $settings->set('branding.logo_path', 'branding/logo.webp');
        $settings->set('branding.system_name', 'Custom Name');
        $settings->set('branding.short_name', 'Custom');
        $settings->set('branding.primary_color', '#123456');

        $response = $this->get(route('manifest'));
        $response->assertOk()->assertHeader('Content-Type', 'application/manifest+json');
        $manifest = $response->json();

        $this->assertSame('Custom Name', $manifest['name']);
        $this->assertSame('Custom', $manifest['short_name']);
        $this->assertSame('#123456', $manifest['theme_color']);
        $logoIcons = array_values(array_filter($manifest['icons'], fn (array $icon) => str_starts_with($icon['src'], route('branding.asset', ['type' => 'logo']))));
        $this->assertCount(2, $logoIcons);
        $this->assertSame(['image/png'], array_values(array_unique(array_column($logoIcons, 'type'))));
        $this->assertStringNotContainsString('storage/', $logoIcons[0]['src']);
        $this->assertStringContainsString('v=', $logoIcons[0]['src']);
    }

    public function test_public_logo_returns_detected_content_mime_for_supported_image_formats(): void
    {
        Storage::fake('public');
        $samples = [
            'png' => [file_get_contents(public_path('icons/pwa-192.png')), 'image/png'],
            'gif' => [base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='), 'image/gif'],
            'jpeg' => [base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGAgJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAwE/EB//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAwE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAQ/EH//Z'), 'image/jpeg'],
            'webp' => [base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA=='), 'image/webp'],
        ];

        foreach ($samples as $extension => [$bytes, $mime]) {
            Storage::disk('public')->put("branding/logo.{$extension}", $bytes);
            app(SettingsService::class)->set('branding.logo_path', "branding/logo.{$extension}");

            $this->get(route('branding.asset', ['type' => 'logo']))
                ->assertOk()
                ->assertHeader('Content-Type', $mime);
        }
    }

    public function test_public_logo_uses_bundled_fallback_for_corrupt_unsupported_and_missing_assets(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('branding/spoof.jpg', file_get_contents(public_path('icons/pwa-192.png')));
        $disk->put('branding/corrupt.png', 'not an image');
        $disk->put('branding/unsupported.bmp', base64_decode('Qk06AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAQAAAATCwAAEwsAAAAAAAAAAAAA'));

        app(SettingsService::class)->set('branding.logo_path', 'branding/spoof.jpg');
        $this->get(route('branding.asset', ['type' => 'logo']))->assertOk()->assertHeader('Content-Type', 'image/png');

        foreach (['branding/corrupt.png', 'branding/unsupported.bmp', 'branding/missing.png'] as $path) {
            app(SettingsService::class)->set('branding.logo_path', $path);
            $response = $this->get(route('branding.asset', ['type' => 'logo']))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
            $this->assertSame(file_get_contents(public_path('icons/pwa-512.png')), $response->getContent());
        }
    }

    public function test_manifest_uses_versioned_branding_endpoint_for_invalid_logo_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/invalid.png', 'broken');
        app(SettingsService::class)->set('branding.logo_path', 'branding/invalid.png');

        $manifest = $this->get(route('manifest'))->assertOk()->json();

        $this->assertCount(6, $manifest['icons']);
        $logoIcons = array_values(array_filter($manifest['icons'], fn (array $icon) => str_starts_with($icon['src'], route('branding.asset', ['type' => 'logo']))));
        $this->assertCount(2, $logoIcons);
        foreach ($logoIcons as $icon) {
            $this->assertStringContainsString('v=', $icon['src']);
            $this->assertStringNotContainsString('storage/', $icon['src']);
        }
        $this->assertSame(file_get_contents(public_path('icons/pwa-512.png')), $this->get(route('branding.asset', ['type' => 'logo']))->assertOk()->getContent());
    }

    public function test_invalid_configured_paths_are_not_read_by_asset_endpoint_or_manifest(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $bytes = file_get_contents(public_path('icons/pwa-192.png'));
        // Place bytes at normalized storage keys too: rejection must be based on
        // the configured path, not merely on whether a file happens to exist.
        $disk->put('branding/logo.png', $bytes);

        foreach ([
            '/branding/logo.png',
            'C:/branding/logo.png',
            'branding/../branding/logo.png',
            'branding//logo.png',
            'branding\\logo.png',
        ] as $path) {
            app(SettingsService::class)->set('branding.logo_path', $path);

            $response = $this->get(route('branding.asset', ['type' => 'logo']))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
            $this->assertSame(file_get_contents(public_path('icons/pwa-512.png')), $response->getContent());
            $manifest = $this->get(route('manifest'))->assertOk()->json();
            $this->assertManifestHasVersionedLogoFallback($manifest);
        }
    }

    public function test_public_favicon_uses_bundled_fallback_for_missing_invalid_and_corrupt_assets(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/corrupt-favicon.png', 'not an image');

        foreach ([null, 'branding/missing-favicon.png', 'branding/corrupt-favicon.png', '../outside.png'] as $path) {
            app(SettingsService::class)->set('branding.favicon_path', $path);

            $response = $this->get(route('branding.asset', ['type' => 'favicon']))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
            $this->assertSame(file_get_contents(public_path('icons/pwa-192.png')), $response->getContent());
        }
    }

    public function test_manifest_logo_version_is_content_only_when_configured_path_changes(): void
    {
        Storage::fake('public');
        $bytes = file_get_contents(public_path('icons/pwa-192.png'));
        Storage::disk('public')->put('branding/first.png', $bytes);
        Storage::disk('public')->put('branding/second.png', $bytes);
        $settings = app(SettingsService::class);
        $settings->set('branding.logo_path', 'branding/first.png');
        $first = $this->get(route('manifest'))->assertOk()->json()['icons'][0]['src'];

        $settings->set('branding.logo_path', 'branding/second.png');
        $second = $this->get(route('manifest'))->assertOk()->json()['icons'][0]['src'];

        $this->assertSame($first, $second);
    }

    public function test_manifest_logo_version_changes_when_content_changes_at_same_path(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $path = 'branding/logo.png';
        $disk->put($path, file_get_contents(public_path('icons/pwa-192.png')));
        $settings = app(SettingsService::class);
        $settings->set('branding.logo_path', $path);
        $first = $this->get(route('manifest'))->assertOk()->json()['icons'][0]['src'];

        $disk->put($path, base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='));
        $second = $this->get(route('manifest'))->assertOk()->json()['icons'][0]['src'];

        $this->assertNotSame($first, $second);
    }

    public function test_manifest_uses_bundled_icons_when_logo_is_absent(): void
    {
        Storage::fake('public');

        $manifest = $this->get(route('manifest'))->assertOk()->json();

        $this->assertManifestHasVersionedLogoFallback($manifest);
    }

    public function test_manifest_uses_versioned_logo_fallback_for_missing_corrupt_and_unsupported_configured_assets(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/corrupt.png', 'not an image');
        Storage::disk('public')->put('branding/unsupported.bmp', base64_decode('Qk06AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAQAAAATCwAAEwsAAAAAAAAAAAAA'));

        foreach (['branding/missing.png', 'branding/corrupt.png', 'branding/unsupported.bmp'] as $path) {
            app(SettingsService::class)->set('branding.logo_path', $path);
            $manifest = $this->get(route('manifest'))->assertOk()->json();
            $this->assertManifestHasVersionedLogoFallback($manifest);
            $response = $this->get(route('branding.asset', ['type' => 'logo']))->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->assertSame(file_get_contents(public_path('icons/pwa-512.png')), $response->getContent());
        }
    }

    public function test_branding_routes_have_sixty_per_minute_throttle_middleware(): void
    {
        foreach (['branding.asset', 'manifest'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertContains('throttle:60,1', $route->middleware());
        }
    }

    private function assertManifestHasVersionedLogoFallback(array $manifest): void
    {
        $logoIcons = array_values(array_filter($manifest['icons'], fn (array $icon) => str_starts_with($icon['src'], route('branding.asset', ['type' => 'logo']))));
        $this->assertCount(2, $logoIcons);
        foreach ($logoIcons as $icon) {
            $this->assertStringContainsString('v=', $icon['src']);
            $this->assertStringNotContainsString('storage/', $icon['src']);
        }
    }

    public function test_dynamic_manifest_route_uses_non_colliding_path_and_keeps_static_manifest(): void
    {
        $this->assertSame('/app-manifest.webmanifest', parse_url(route('manifest'), PHP_URL_PATH));
        $this->assertFileExists(public_path('manifest.webmanifest'));

        $this->get('/app-manifest.webmanifest')->assertOk();
    }

    public function test_pwa_layout_and_service_worker_use_dynamic_branding_references(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $worker = file_get_contents(public_path('sw.js'));
        $branding = file_get_contents(resource_path('views/partials/dynamic-branding.blade.php'));
        preg_match('/const SHELL = \[(.*?)\];/s', $worker, $shellMatch);

        $this->assertStringNotContainsString('apple-touch-icon.png', $layout);
        $this->assertStringContainsString("asset('icons/apple-touch-icon.png')", $branding);
        $this->assertNotEmpty($shellMatch);
        $this->assertStringNotContainsString("'/app-manifest.webmanifest'", $shellMatch[1]);
        $this->assertStringContainsString("if (url.pathname === '/app-manifest.webmanifest') {\n        event.respondWith(networkFirst(request, RUNTIME_CACHE));", $worker);
        $this->assertStringContainsString("'/brand-assets/logo'", $worker);
        $this->assertStringContainsString("'/icons/pwa-192.png'", $worker);
        $this->assertStringContainsString("const VERSION = 'eh360-v1-20261011-2'", $worker);
        $this->assertStringContainsString('options.icon = logoUrl.href;', $worker);
        $this->assertStringContainsString('options.badge = logoUrl.href;', $worker);
        $this->assertStringContainsString('logoUrl.origin !== self.location.origin', $worker);
        $this->assertStringContainsString("logoUrl.pathname !== '/brand-assets/logo'", $worker);
        $this->assertStringNotContainsString("'/manifest.webmanifest'", $worker);
    }

    public function test_branding_partial_renders_safe_nonrecursive_bundled_icon_fallbacks(): void
    {
        Storage::fake('public');
        app(SettingsService::class)->set('branding.logo_path', ['invalid'], 'json');
        app(SettingsService::class)->set('branding.favicon_path', 'branding/missing.png');

        $html = view('partials.dynamic-branding')->render();
        $this->assertStringContainsString('href="'.e(route('branding.asset', ['type' => 'logo'])).'"', $html);
        $this->assertSame(2, substr_count($html, 'data-fallback="'.e(asset('icons/pwa-192.png')).'"'));
        $this->assertStringContainsString('data-fallback="'.e(asset('icons/apple-touch-icon.png')).'"', $html);
        $this->assertSame(3, substr_count($html, 'onerror="this.onerror=null;this.href=this.dataset.fallback;"'));
        $this->assertStringNotContainsString('storage/', $html);
    }
}

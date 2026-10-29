<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBackup;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class PlatformSettingsController extends Controller
{
    public function index(Request $request, SettingsService $settings)
    {
        $aiIntegrations = \App\Models\AiIntegration::query()->systemProviders()->orderByDesc('enabled')->orderBy('provider')->get();
        $editProvider = $request->query('ai_provider');
        $aiConfig = is_string($editProvider) && \App\Services\AiProviderRegistry::get($editProvider)
            ? \App\Models\AiIntegration::forProvider($editProvider)
            : (\App\Models\AiIntegration::activeSystem() ?? $aiIntegrations->first() ?? new \App\Models\AiIntegration(['provider' => 'openai']));

        return view('admin.settings.platform', [
            'backups' => PlatformBackup::latest()->limit(20)->get(),
            'settings' => $settings,
            'aiConfig' => $aiConfig,
            'aiIntegrations' => $aiIntegrations,
            'aiRecentIssues' => \App\Models\AiUsageLog::whereIn('status', ['failed', 'limited'])->latest()->take(5)->get(),
            'assessments' => \App\Models\Assessment::orderBy('title')->get(['id', 'title']),
            'entryAssessmentId' => (int) $settings->get('admissions.entry_assessment_id'),
        ]);
    }

    public function updateAdmissions(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'entry_assessment_id' => ['nullable', 'integer', 'exists:assessments,id'],
            'mentorship_requires_assessment' => ['nullable', 'boolean'],
            'jobs_require_assessment' => ['nullable', 'boolean'],
        ]);

        $settings->set('admissions.entry_assessment_id', $data['entry_assessment_id'] ?? null, 'integer', 'admissions', true);
        $settings->set('admissions.mentorship_requires_assessment', $request->boolean('mentorship_requires_assessment'), 'boolean', 'admissions', false);
        $settings->set('admissions.jobs_require_assessment', $request->boolean('jobs_require_assessment'), 'boolean', 'admissions', false);

        return back()
            ->with('success', 'Admissions entry assessment saved.')
            ->with('platform_settings_tab', 'admissions');
    }

    public function updateAi(Request $request, \App\Services\SystemAiSettingsService $ai)
    {
        $ai->save($request);
        return back()->with('success', 'System AI configuration saved. All AI features use this provider when active.')->with('platform_settings_tab', 'ai');
    }

    public function toggleAi(\App\Models\AiIntegration $integration, \App\Services\SystemAiSettingsService $ai)
    {
        $integration = $ai->toggle($integration);
        $label = \App\Services\AiProviderRegistry::resolve((string) $integration->provider)['label'];

        return back()
            ->with('success', $integration->enabled ? "{$label} is now the active AI provider." : "{$label} disabled. AI features are off until a provider is enabled.")
            ->with('platform_settings_tab', 'ai');
    }

    public function testAi(Request $request, \App\Services\SystemAiSettingsService $ai)
    {
        return response()->json($ai->test($request));
    }

    public function updateBranding(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'system_name' => 'required|string|max:120',
            'short_name' => 'nullable|string|max:30',
            'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_family' => 'required|string|max:100',
            'font_size' => 'required|integer|min:12|max:22',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,gif|max:4096',
            // ICO is intentionally excluded until the public asset endpoint can
            // safely identify and serve it. Keep accepted and delivered types aligned.
            'favicon' => 'nullable|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        foreach ([
            'system_name',
            'short_name',
            'primary_color',
            'secondary_color',
            'accent_color',
            'font_family',
            'font_size',
        ] as $key) {
            $settings->set(
                'branding.'.$key,
                $data[$key] ?? null,
                $key === 'font_size' ? 'integer' : 'string',
                'branding',
                true
            );
        }

        if ($request->hasFile('logo')) {
            $this->replaceBrandAsset($request->file('logo'), 'branding.logo_path', $settings, 'logo');
        }

        if ($request->hasFile('favicon')) {
            $this->replaceBrandAsset($request->file('favicon'), 'branding.favicon_path', $settings, 'favicon');
        }

        return back()
            ->with('success', 'Branding settings updated.')
            ->with('platform_settings_tab', 'branding');
    }

    public function updateStorage(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'backup_destination' => 'required|in:local,google',
            'google_project_id' => 'nullable|string|max:255',
            'google_bucket' => 'nullable|string|max:255',
            'google_credentials_json' => 'nullable|string',
            'retention_days' => 'required|integer|min:1|max:3650',
        ]);

        $settings->set('backup.destination', $data['backup_destination'], 'string', 'backup', false);
        $settings->set('backup.retention_days', $data['retention_days'], 'integer', 'backup', false);
        $settings->set('backup.google_project_id', $data['google_project_id'] ?? '', 'string', 'backup', false);
        $settings->set('backup.google_bucket', $data['google_bucket'] ?? '', 'string', 'backup', false);

        if (! empty($data['google_credentials_json'])) {
            json_decode($data['google_credentials_json'], true, 512, JSON_THROW_ON_ERROR);

            $settings->set(
                'backup.google_credentials_json',
                $data['google_credentials_json'],
                'string',
                'backup',
                false,
                true
            );
        }

        return back()
            ->with('success', 'Backup/storage settings saved. Google credentials are stored encrypted.')
            ->with('platform_settings_tab', 'backup');
    }

    public function updateMaintenance(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'title' => 'nullable|string|max:160',
            'message' => 'nullable|string|max:3000',
            'return_at' => 'nullable|date',
        ]);

        $settings->set('maintenance.enabled', $request->boolean('enabled'), 'boolean', 'maintenance', false);
        $settings->set('maintenance.title', $data['title'] ?? 'Scheduled Maintenance', 'string', 'maintenance', false);
        $settings->set('maintenance.message', $data['message'] ?? 'We will be back shortly.', 'string', 'maintenance', false);
        $settings->set('maintenance.return_at', $data['return_at'] ?? '', 'string', 'maintenance', false);

        return back()
            ->with('success', 'Maintenance settings updated.')
            ->with('platform_settings_tab', 'maintenance');
    }

    public function backupNow(Request $request, SettingsService $settings)
    {
        abort_unless(
            auth()->user()->hasPermission('settings.backups')
            || auth()->user()->isSuperAdmin(),
            403
        );

        $destination = $settings->get('backup.destination', 'local');

        if ($destination !== 'local') {
            return back()
                ->withErrors([
                    'backup' => 'Google backup configuration has been saved, but the server storage adapter must be installed before cloud uploads can run. Switch to Local for Backup Now.',
                ])
                ->with('platform_settings_tab', 'backup');
        }

        $exit = Artisan::call('platform:backup', ['--destination' => 'local']);

        if ($exit !== 0) {
            return back()
                ->withErrors([
                    'backup' => 'Backup failed. Check the latest backup record and confirm mysqldump is installed.',
                ])
                ->with('platform_settings_tab', 'backup');
        }

        return back()
            ->with('success', 'Local database backup completed.')
            ->with('platform_settings_tab', 'backup');
    }

    private function replaceBrandAsset(
        UploadedFile $file,
        string $settingKey,
        SettingsService $settings,
        string $prefix
    ): void {
        $disk = Storage::disk('public');
        $oldPath = $settings->get($settingKey);

        // Never use a client-controlled filename/extension for a file placed on
        // the public disk. Derive the suffix from decoded image bytes instead.
        $bytes = $file->get();
        $image = @getimagesizefromstring($bytes);
        $extension = match ($image[2] ?? null) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
            default => null,
        };

        abort_if($extension === null, 422, 'Unsupported branding image content.');

        $filename = $prefix.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$extension;
        $newPath = 'branding/'.$filename;
        $stored = $disk->put($newPath, $bytes);

        if (! $stored) {
            abort(500, 'Unable to store the uploaded branding file.');
        }

        $settings->set($settingKey, $newPath, 'string', 'branding', true);

        if (
            is_string($oldPath)
            && $oldPath !== ''
            && $oldPath !== $newPath
            && str_starts_with($oldPath, 'branding/')
            && $disk->exists($oldPath)
        ) {
            $disk->delete($oldPath);
        }
    }
}

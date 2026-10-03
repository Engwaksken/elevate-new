<?php

namespace Tests\Feature\Admin;

use App\Models\CertificateTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTemplateLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']);
        $user->roles()->attach($role);

        $this->actingAs($user);

        return $user;
    }

    private function template(): CertificateTemplate
    {
        return CertificateTemplate::create([
            'name' => 'Default',
            'context_type' => 'default',
            'background_path' => 'certificate-templates/background.png',
            'orientation' => 'landscape',
            'is_active' => true,
        ]);
    }

    public function test_layout_defaults_cover_every_field(): void
    {
        $template = $this->template();
        $layout = $template->fieldLayout();

        $this->assertArrayHasKey('name', $layout);
        $this->assertArrayHasKey('course', $layout);
        $this->assertArrayHasKey('certificate_number', $layout);
        $this->assertArrayHasKey('participant_id', $layout);
        $this->assertArrayHasKey('start_period', $layout);
        $this->assertArrayHasKey('end_period', $layout);
        $this->assertTrue($layout['name']['enabled']);
        $this->assertFalse($layout['issued']['enabled']);
    }

    public function test_admin_can_save_a_custom_layout(): void
    {
        $this->admin();
        Storage::fake('public');
        Storage::disk('public')->put('certificate-templates/background.png', 'x');

        $template = $this->template();

        $this->put(route('admin.elearning.certificates.templates.design.update', $template), [
            'layout' => [
                'name' => ['enabled' => '1', 'x' => 50, 'y' => 40, 'width' => 80, 'font_size' => 40, 'color' => '#800000', 'bold' => '1'],
                'participant_id' => ['x' => 20, 'y' => 80, 'width' => 30, 'align' => 'left', 'font_size' => 12],
                'start_period' => ['x' => 20, 'y' => 85, 'width' => 30, 'align' => 'left', 'prefix' => 'Start: '],
                'certificate_number' => ['enabled' => '1', 'x' => 80, 'y' => 85, 'width' => 30, 'align' => 'right'],
            ],
        ])->assertRedirect();

        $saved = $template->fresh()->fieldLayout();

        $this->assertSame(40.0, (float) $saved['name']['font_size']);
        $this->assertTrue((bool) $saved['name']['bold']);
        $this->assertFalse((bool) $saved['name']['italic']);
        $this->assertSame('#800000', $saved['name']['color']);
        $this->assertSame('left', $saved['participant_id']['align']);

        // Fields omitted from the request fall back to their enabled default.
        $this->assertFalse((bool) $saved['issued']['enabled']);
    }
}

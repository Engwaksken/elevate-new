<?php

namespace Tests\Feature;

use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryBulkAddTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function librarian(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $user;
    }

    public function test_library_page_offers_the_add_multiple_form(): void
    {
        $this->actingAs($this->librarian())
            ->get(route('admin.library.index'))
            ->assertOk()
            ->assertSee('Add Multiple')
            ->assertSee(route('admin.library.bulk-store'), false);
    }

    public function test_each_uploaded_file_becomes_a_resource_with_shared_settings(): void
    {
        $this->actingAs($this->librarian())
            ->post(route('admin.library.bulk-store'), [
                'files' => [
                    UploadedFile::fake()->create('career_guide-2026.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('budget.xlsx', 50),
                ],
                'titles' => [0 => '', 1 => 'Household Budget Template'],
                'language' => 'English',
                'access_level' => 'public',
                'download_policy' => 'request',
                'tags_text' => 'finance, careers',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 library resources created.');

        $resources = LibraryResource::orderBy('id')->get();
        $this->assertSame(['Career guide 2026', 'Household Budget Template'], $resources->pluck('title')->all());

        foreach ($resources as $resource) {
            $this->assertSame('public', $resource->access_level);
            $this->assertSame('request', $resource->download_policy);
            $this->assertSame(['finance', 'careers'], $resource->tags);
            $this->assertTrue($resource->is_active);
            Storage::disk('local')->assertExists($resource->file_path);
        }
    }

    public function test_bulk_add_rejects_unsupported_files_and_creates_nothing(): void
    {
        $this->actingAs($this->librarian())
            ->post(route('admin.library.bulk-store'), [
                'files' => [
                    UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf'),
                    UploadedFile::fake()->create('script.exe', 10),
                ],
                'language' => 'English',
                'access_level' => 'authenticated',
            ])
            ->assertSessionHasErrors('files.1');

        $this->assertSame(0, LibraryResource::count());
    }

    public function test_bulk_add_requires_at_least_one_file(): void
    {
        $this->actingAs($this->librarian())
            ->post(route('admin.library.bulk-store'), ['language' => 'English', 'access_level' => 'public'])
            ->assertSessionHasErrors('files');
    }
}

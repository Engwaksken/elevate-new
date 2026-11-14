<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryCourseAccessTest extends TestCase
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

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function course(string $title): Course
    {
        return Course::create(['title' => $title, 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);
    }

    private function book(string $title, array $courses = []): LibraryResource
    {
        Storage::disk('local')->put("library/{$title}.pdf", '%PDF-1.4 test');
        $book = LibraryResource::create([
            'title' => $title, 'language' => 'English', 'access_level' => 'authenticated',
            'download_policy' => 'free', 'is_active' => true, 'file_path' => "library/{$title}.pdf",
        ]);
        $book->courses()->sync(collect($courses)->pluck('id')->all());

        return $book;
    }

    public function test_staff_can_pick_courses_when_adding_single_and_multiple_books(): void
    {
        $digital = $this->course('Digital Skills');
        $tailoring = $this->course('Tailoring');
        $admin = $this->librarian();

        $this->actingAs($admin)->get(route('admin.library.index'))
            ->assertOk()->assertSee('name="course_ids[]"', false)->assertSee('All courses');

        $this->post(route('admin.library.store'), [
            'title' => 'Coding Basics', 'language' => 'English', 'access_level' => 'authenticated',
            'download_policy' => 'free', 'is_active' => '1', 'course_ids' => [$digital->id, $tailoring->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$digital->id, $tailoring->id], LibraryResource::where('title', 'Coding Basics')->sole()->courses->pluck('id')->all());

        $this->post(route('admin.library.bulk-store'), [
            'files' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')],
            'language' => 'English', 'access_level' => 'authenticated', 'course_ids' => [$tailoring->id],
        ])->assertSessionHasNoErrors();

        foreach (['A', 'B'] as $title) {
            $this->assertSame([$tailoring->id], LibraryResource::where('title', $title)->sole()->courses->pluck('id')->all());
        }

        // Editing with every course unticked opens the book to all participants again.
        $book = LibraryResource::where('title', 'Coding Basics')->sole();
        $this->put(route('admin.library.update', $book), [
            'title' => 'Coding Basics', 'language' => 'English', 'access_level' => 'authenticated',
            'download_policy' => 'free', 'is_active' => '1', 'sync_courses' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $book->courses()->count());

        $this->get(route('admin.library.index', ['course_id' => $tailoring->id]))
            ->assertOk()->assertSee('>A<', false)->assertDontSee('Coding Basics');
    }

    public function test_all_signed_in_users_can_see_course_linked_books(): void
    {
        $digital = $this->course('Digital Skills');
        $tailoring = $this->course('Tailoring');
        $general = $this->book('General Handbook');
        $coding = $this->book('Coding Guide', [$digital]);
        $sewing = $this->book('Sewing Patterns', [$tailoring]);

        $user = $this->participant();

        $this->actingAs($user)->get(route('library.index'))
            ->assertOk()->assertSee('General Handbook')->assertSee('Coding Guide')->assertSee('Sewing Patterns');

        $this->get(route('library.show', $coding))->assertOk();
        $this->get(route('library.show', $sewing))->assertOk();
        $this->get(route('library.read', $sewing))->assertOk();
        $this->get(route('library.download', $sewing))->assertOk();
        $this->get(route('library.show', $general))->assertOk();

        // Staff see everything.
        $this->actingAs($this->librarian())->get(route('library.show', $sewing))->assertOk();
    }

    public function test_withdrawn_participants_keep_access_to_shared_library_books(): void
    {
        $digital = $this->course('Digital Skills');
        $coding = $this->book('Coding Guide', [$digital]);
        $user = $this->participant();
        Enrolment::create(['user_id' => $user->id, 'course_id' => $digital->id, 'status' => 'withdrawn', 'enrolled_at' => now()]);

        $this->actingAs($user)->get(route('library.show', $coding))->assertOk();
    }

    public function test_participant_app_shows_course_linked_books_to_every_participant(): void
    {
        $digital = $this->course('Digital Skills');
        $this->book('General Handbook');
        $coding = $this->book('Coding Guide', [$digital]);
        $user = $this->participant();

        Sanctum::actingAs($user);

        $titles = collect($this->getJson('/api/v1/participant/library')->assertOk()->json('data'))->pluck('title');
        $this->assertContains('General Handbook', $titles);
        $this->assertContains('Coding Guide', $titles);

        $this->getJson("/api/v1/participant/library/{$coding->id}")->assertOk();

        $this->assertDatabaseMissing('enrolments', ['user_id' => $user->id, 'course_id' => $digital->id]);
    }
}

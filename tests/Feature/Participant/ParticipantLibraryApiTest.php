<?php

namespace Tests\Feature\Participant;

use App\Models\LibraryCategory;
use App\Models\LibraryDownloadRequest;
use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantLibraryApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant/library';

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        Sanctum::actingAs($this->participant, ['participant']);
    }

    private function book(array $attrs = []): LibraryResource
    {
        $file = $attrs['file_path'] ?? 'library/'.uniqid().'.pdf';
        Storage::disk('local')->put($file, "%PDF-1.4\n% sample\n");

        return LibraryResource::create($attrs + ['title' => 'Career Guide', 'author' => 'Jane Doe', 'file_path' => $file,
            'access_level' => 'authenticated', 'download_policy' => 'free', 'is_active' => true]);
    }

    public function test_lists_only_books_participants_may_see_with_categories_and_search(): void
    {
        $category = LibraryCategory::create(['name' => 'Careers', 'slug' => 'careers', 'is_active' => true]);
        $this->book(['title' => 'Interview Skills', 'library_category_id' => $category->id]);
        $this->book(['title' => 'Public Primer', 'access_level' => 'public']);
        $this->book(['title' => 'Staff Handbook', 'access_level' => 'staff']);
        $this->book(['title' => 'Retired Book', 'is_active' => false]);

        $data = $this->getJson(self::BASE)->assertOk()->json();
        $this->assertEqualsCanonicalizing(['Interview Skills', 'Public Primer'], collect($data['data'])->pluck('title')->all());
        $this->assertSame('Careers', $data['categories'][0]['name']);

        $this->assertSame(['Interview Skills'], collect($this->getJson(self::BASE.'?search=interview')->json('data'))->pluck('title')->all());
        $this->assertSame(['Interview Skills'], collect($this->getJson(self::BASE.'?category='.$category->id)->json('data'))->pluck('title')->all());

        $staffOnly = LibraryResource::where('title', 'Staff Handbook')->first();
        $this->getJson(self::BASE.'/'.$staffOnly->id)->assertNotFound();
    }

    public function test_reading_saves_progress_shared_with_continue_reading(): void
    {
        $book = $this->book();

        $text = $this->get(self::BASE."/{$book->id}/file")->assertOk()->assertHeader('Content-Type', 'text/plain; charset=US-ASCII')->getContent();
        $this->assertStringStartsWith('%PDF', base64_decode($text));
        $this->assertSame([$book->id], collect($this->getJson(self::BASE.'/mine')->json('continue'))->pluck('id')->all());

        $this->postJson(self::BASE."/{$book->id}/progress", ['page' => 12, 'total_pages' => 40])
            ->assertOk()->assertJsonPath('progress.page', 12)->assertJsonPath('progress.completed', false);
        $this->getJson(self::BASE."/{$book->id}")->assertJsonPath('data.progress.page', 12)->assertJsonPath('data.progress.total_pages', 40);

        $this->postJson(self::BASE."/{$book->id}/progress", ['page' => 40, 'total_pages' => 40])->assertJsonPath('progress.completed', true);
        $this->assertSame([], $this->getJson(self::BASE.'/mine')->json('continue'), 'Finished books leave Continue reading.');
    }

    public function test_non_pdf_books_are_not_served_to_the_reader(): void
    {
        $book = $this->book(['file_path' => 'library/notes.docx']);

        $this->getJson(self::BASE."/{$book->id}")->assertJsonPath('data.readable_in_app', false);
        $this->getJson(self::BASE."/{$book->id}/file")->assertNotFound();
    }

    public function test_bookmarks_toggle_and_appear_in_mine(): void
    {
        $book = $this->book();

        $this->postJson(self::BASE."/{$book->id}/bookmark")->assertJsonPath('bookmarked', true);
        $this->assertSame([$book->id], collect($this->getJson(self::BASE.'/mine')->json('bookmarks'))->pluck('id')->all());
        $this->postJson(self::BASE."/{$book->id}/bookmark")->assertJsonPath('bookmarked', false);
        $this->assertSame([], $this->getJson(self::BASE.'/mine')->json('bookmarks'));
    }

    public function test_download_requests_notify_the_library_team_once(): void
    {
        $librarian = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $librarian->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $free = $this->book();
        $restricted = $this->book(['download_policy' => 'request']);

        $this->getJson(self::BASE."/{$restricted->id}")->assertJsonPath('data.can_download', false);
        $this->postJson(self::BASE."/{$restricted->id}/request-download", ['reason' => 'Offline study'])->assertJsonPath('status', 'sent');
        $this->postJson(self::BASE."/{$restricted->id}/request-download")->assertJsonPath('status', 'already_pending');
        $this->postJson(self::BASE."/{$free->id}/request-download")->assertJsonPath('status', 'already_allowed');

        $this->assertSame(1, LibraryDownloadRequest::count());
        $this->assertSame(1, UserNotification::where('user_id', $librarian->id)->count());
        $this->getJson(self::BASE."/{$restricted->id}")->assertJsonPath('data.download_request.status', 'pending');
    }

    public function test_staff_and_guests_cannot_use_the_participant_library(): void
    {
        Sanctum::actingAs(User::factory()->create(['user_type' => 'staff', 'status' => 'active']), ['participant']);
        $this->getJson(self::BASE)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->getJson(self::BASE)->assertUnauthorized();
    }
}

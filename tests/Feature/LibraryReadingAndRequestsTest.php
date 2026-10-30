<?php

namespace Tests\Feature;

use App\Models\LibraryDownloadRequest;
use App\Models\LibraryReadingProgress;
use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryReadingAndRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
    }

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function librarian(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $user;
    }

    private function book(string $policy = 'free', string $file = 'library/guide.pdf'): LibraryResource
    {
        Storage::disk('local')->put($file, "%PDF-1.4\n% sample\n");

        return LibraryResource::create(['title' => 'Career Guide', 'author' => 'Jane Doe', 'file_path' => $file, 'language' => 'English',
            'access_level' => 'authenticated', 'download_policy' => $policy, 'is_active' => true]);
    }

    public function test_free_books_download_and_on_request_books_can_be_read_but_not_downloaded(): void
    {
        $user = $this->participant();
        $free = $this->book('free');
        $restricted = $this->book('request', 'library/restricted.pdf');

        $this->actingAs($user)->get(route('library.download', $free))->assertOk()->assertDownload();

        $this->actingAs($user)->get(route('library.download', $restricted))
            ->assertRedirect(route('library.show', $restricted))->assertSessionHas('warning');
        $this->actingAs($user)->get(route('library.download', [$restricted, 'preview' => 'raw']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($user)->get(route('library.show', $restricted))->assertOk()
            ->assertSee('Request download')->assertSee('Read online')->assertDontSee(route('library.download', $restricted).'"', false);
    }

    public function test_download_request_is_approved_by_library_staff_and_then_expires(): void
    {
        $user = $this->participant();
        $librarian = $this->librarian();
        $book = $this->book('request');

        $this->actingAs($user)->from(route('library.show', $book))
            ->post(route('library.request-download', $book), ['reason' => 'Limited internet at home'])
            ->assertRedirect()->assertSessionHas('success');
        $this->actingAs($user)->post(route('library.request-download', $book))->assertSessionHas('warning');
        $this->assertSame(1, LibraryDownloadRequest::count());
        $this->assertTrue(UserNotification::where('user_id', $librarian->id)->where('type', 'library_download_request')->exists());

        $this->actingAs($user)->get(route('admin.library.requests.index'))->assertForbidden();

        $request = LibraryDownloadRequest::sole();
        $this->actingAs($librarian)->get(route('admin.library.requests.index'))->assertOk()->assertSee('Limited internet at home');
        $this->actingAs($librarian)->post(route('admin.library.requests.approve', $request), ['note' => 'Enjoy'])->assertRedirect();
        $this->actingAs($librarian)->post(route('admin.library.requests.approve', $request))->assertStatus(422);

        $this->assertTrue(UserNotification::where('user_id', $user->id)->where('type', 'library_download_approved')->exists());
        $this->actingAs($user)->get(route('library.download', $book))->assertOk()->assertDownload();

        $this->travel(LibraryDownloadRequest::APPROVAL_DAYS + 1)->days();
        $this->actingAs($user)->get(route('library.download', $book))->assertRedirect(route('library.show', $book));
    }

    public function test_declining_needs_a_reason_and_tells_the_reader(): void
    {
        $user = $this->participant();
        $book = $this->book('request');
        $request = LibraryDownloadRequest::create(['user_id' => $user->id, 'library_resource_id' => $book->id, 'status' => 'pending']);

        $this->actingAs($this->librarian())->post(route('admin.library.requests.decline', $request))->assertSessionHasErrors('note');
        $this->post(route('admin.library.requests.decline', $request), ['note' => 'Online reading only'])->assertRedirect();

        $this->assertSame('declined', $request->fresh()->status);
        $this->assertStringContainsString('Online reading only', UserNotification::where('user_id', $user->id)->where('type', 'library_download_declined')->sole()->message);
        $this->actingAs($user)->get(route('library.show', $book))->assertSee('Online reading only')->assertSee('Request download');
    }

    public function test_reader_gets_the_pdf_as_base64_text_and_only_for_pdfs_she_can_access(): void
    {
        $user = $this->participant();
        $book = $this->book('request');

        $response = $this->actingAs($user)->get(route('library.read.file', $book))->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=US-ASCII');
        $this->assertSame("%PDF-1.4\n% sample\n", base64_decode($response->getContent(), true));

        Storage::disk('local')->put('library/notes.docx', 'not a pdf');
        $word = LibraryResource::create(['title' => 'Notes', 'file_path' => 'library/notes.docx', 'language' => 'English', 'access_level' => 'authenticated', 'is_active' => true]);
        $this->actingAs($user)->get(route('library.read.file', $word))->assertNotFound();

        $staffOnly = LibraryResource::create(['title' => 'Staff manual', 'file_path' => 'library/guide.pdf', 'language' => 'English', 'access_level' => 'staff', 'is_active' => true]);
        $this->actingAs($user)->get(route('library.read.file', $staffOnly))->assertForbidden();
        $this->get(route('library.read.file', $book))->assertOk();
        auth()->logout();
        $this->get(route('library.read.file', $book))->assertRedirect();
    }

    public function test_reading_progress_is_saved_and_resumed(): void
    {
        $user = $this->participant();
        $book = $this->book();

        $this->actingAs($user)->get(route('library.read', $book))->assertOk()
            ->assertSee('data-library-reader', false)->assertSee('data-start-page="1"', false)
            ->assertSee(route('library.read.file', $book), false);
        $this->actingAs($user)->postJson(route('library.progress', $book), ['page' => 12, 'total_pages' => 40])
            ->assertOk()->assertJson(['page' => 12, 'percent' => 30, 'completed' => false]);

        $this->actingAs($user)->get(route('library.read', $book))->assertSee('data-start-page="12"', false);
        $this->actingAs($user)->get(route('library.show', $book))->assertSee('Continue reading (page 12)')->assertSee('Page 12 of 40');

        $progress = LibraryReadingProgress::sole();
        $this->assertSame(2, $progress->opened_count);

        $this->actingAs($user)->postJson(route('library.progress', $book), ['page' => 40, 'total_pages' => 40])->assertJson(['completed' => true]);
        $this->assertNotNull($progress->fresh()->completed_at);
        $this->actingAs($user)->get(route('library.read', $book))->assertSee('data-start-page="1"', false);
    }

    public function test_bookmarks_toggle_and_my_library_lists_reading_bookmarks_and_requests(): void
    {
        $user = $this->participant();
        $book = $this->book('request');

        $this->actingAs($user)->postJson(route('library.bookmark', $book))->assertJson(['bookmarked' => true]);
        $this->actingAs($user)->get(route('library.show', $book))->assertSee('Bookmarked (remove)');
        $this->actingAs($user)->postJson(route('library.bookmark', $book))->assertJson(['bookmarked' => false]);
        $this->actingAs($user)->postJson(route('library.bookmark', $book))->assertJson(['bookmarked' => true]);

        $this->actingAs($user)->get(route('library.read', $book));
        $this->actingAs($user)->postJson(route('library.progress', $book), ['page' => 3, 'total_pages' => 10]);
        LibraryDownloadRequest::create(['user_id' => $user->id, 'library_resource_id' => $book->id, 'status' => 'pending']);

        $this->actingAs($user)->get(route('library.mine'))->assertOk()
            ->assertSee('Continue reading')->assertSee('Career Guide')->assertSee('Page 3 of 10')
            ->assertSee('Waiting for reply')->assertSee('data-eh-pane="bookmarks"', false);

        $other = $this->participant();
        $this->actingAs($other)->get(route('library.mine'))->assertOk()->assertSee('Nothing in progress')->assertDontSee('Page 3 of 10');
    }

    public function test_staff_set_the_download_policy_per_book(): void
    {
        $librarian = $this->librarian();
        $book = $this->book();

        $this->actingAs($librarian)->put(route('admin.library.update', $book), [
            'title' => 'Career Guide', 'language' => 'English', 'access_level' => 'authenticated', 'download_policy' => 'request', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($book->fresh()->requiresDownloadApproval());

        $this->actingAs($librarian)->get(route('admin.library.index'))->assertOk()->assertSee('Download on request')->assertSee('Download requests');
    }

    public function test_my_library_uses_the_participant_workspace_with_its_sidebar(): void
    {
        $this->actingAs($this->participant())->get(route('library.mine'))
            ->assertOk()
            ->assertSee('participant-app-shell', false);
    }

    public function test_signed_in_participant_can_get_back_to_the_dashboard_from_the_public_site(): void
    {
        $this->actingAs($this->participant())->get(route('library.index'))
            ->assertOk()
            ->assertDontSee('participant-app-shell', false)
            ->assertSee('href="'.route('dashboard').'"', false);
    }
}

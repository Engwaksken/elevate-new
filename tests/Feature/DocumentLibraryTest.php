<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\DocumentShare;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use RefreshDatabase;

    private Department $finance;
    private Department $programmes;
    private User $head;
    private User $member;
    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->head = $this->staff('Fiona Head');
        $this->member = $this->staff('Mark Member');
        $this->outsider = $this->staff('Olga Outsider');

        $this->finance = Department::create(['name' => 'Finance Unit', 'code' => 'TFIN', 'head_user_id' => $this->head->id, 'is_active' => true]);
        $this->programmes = Department::create(['name' => 'Programmes Unit', 'code' => 'TPRG', 'is_active' => true]);

        Employee::create(['user_id' => $this->member->id, 'employee_number' => 'E1', 'department_id' => $this->finance->id]);
        Employee::create(['user_id' => $this->outsider->id, 'employee_number' => 'E2', 'department_id' => $this->programmes->id]);
    }

    private function staff(string $name): User
    {
        return User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => $name]);
    }

    private function upload(User $user, string $name = 'Budget.xlsx', ?DocumentFolder $folder = null, string $content = 'v1')
    {
        return $this->actingAs($user)->post(route('staff.documents.upload', $this->finance), array_filter([
            'folder_id' => $folder?->id,
            'files' => [UploadedFile::fake()->createWithContent($name, $content)],
        ]));
    }

    private function folder(string $name = 'Reports'): DocumentFolder
    {
        return DocumentFolder::create(['department_id' => $this->finance->id, 'name' => $name, 'created_by' => $this->head->id]);
    }

    public function test_members_see_their_department_site_and_others_do_not(): void
    {
        $this->actingAs($this->member)->get(route('staff.documents.index'))
            ->assertOk()->assertSee('Finance Unit')->assertDontSee('Programmes Unit');

        $this->actingAs($this->member)->get(route('staff.documents.library', $this->finance))
            ->assertOk()->assertSee('Drag files here')->assertSee('Can edit');

        $this->actingAs($this->outsider)->get(route('staff.documents.library', $this->finance))->assertForbidden();
    }

    public function test_member_creates_folders_and_uploads_files(): void
    {
        $this->actingAs($this->member)->post(route('staff.documents.folders.store', $this->finance), ['name' => 'Payroll'])
            ->assertRedirect();
        $folder = DocumentFolder::where('name', 'Payroll')->firstOrFail();

        $this->upload($this->member, 'March payroll.xlsx', $folder)->assertSessionHas('success');

        $file = DocumentFile::firstOrFail();
        $this->assertSame($folder->id, $file->folder_id);
        $this->assertSame('xlsx', $file->extension);
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($this->member)->get(route('staff.documents.library', ['department' => $this->finance, 'folder' => $folder->id]))
            ->assertOk()->assertSee('March payroll.xlsx')->assertSee('Payroll');

        // Duplicate folder names in the same place are refused.
        $this->actingAs($this->member)->post(route('staff.documents.folders.store', $this->finance), ['name' => 'payroll'])
            ->assertSessionHasErrors('name');
    }

    public function test_uploading_the_same_name_adds_a_version_and_old_versions_can_be_restored(): void
    {
        $this->upload($this->member, 'Policy.docx', null, 'first');
        $this->upload($this->member, 'Policy.docx', null, 'second');

        $file = DocumentFile::firstOrFail();
        $this->assertSame(1, DocumentFile::count());
        $this->assertSame(2, $file->version);
        $this->assertSame('second', Storage::disk('local')->get($file->path));

        $v1 = $file->versions()->where('version', 1)->firstOrFail();
        $this->actingAs($this->member)->post(route('staff.documents.versions.restore', $v1))->assertSessionHas('success');

        $file->refresh();
        $this->assertSame(3, $file->version);
        $this->assertSame('first', Storage::disk('local')->get($file->path));

        $this->actingAs($this->member)->get(route('staff.documents.library', ['department' => $this->finance, 'panel' => 'versions', 'item' => 'file:'.$file->id]))
            ->assertOk()->assertSee('Restored from version 1')->assertSee('3.0');
    }

    public function test_blocked_file_types_are_rejected(): void
    {
        $this->upload($this->member, 'shell.php')->assertSessionHasErrors('files');
        $this->assertSame(0, DocumentFile::count());
    }

    public function test_only_full_control_can_delete_and_share(): void
    {
        $this->upload($this->member);
        $file = DocumentFile::firstOrFail();

        $this->actingAs($this->member)->delete(route('staff.documents.destroy', ['file', $file->id]))->assertForbidden();
        $this->actingAs($this->member)->post(route('staff.documents.share', ['file', $file->id]), ['user_ids' => [$this->outsider->id], 'role' => 'view'])
            ->assertForbidden();

        $path = $file->path;
        $this->actingAs($this->head)->delete(route('staff.documents.destroy', ['file', $file->id]))->assertSessionHas('success');
        $this->assertSame(0, DocumentFile::count());
        $this->assertSame(1, DocumentFile::onlyTrashed()->count());
        Storage::disk('local')->assertExists($path); // still in the recycle bin
    }

    public function test_deleted_folders_go_to_the_recycle_bin_and_can_be_restored(): void
    {
        $folder = $this->folder();
        $sub = DocumentFolder::create(['department_id' => $this->finance->id, 'parent_id' => $folder->id, 'name' => 'Q1']);
        $this->upload($this->member, 'Inside.txt', $sub);

        $this->actingAs($this->head)->delete(route('staff.documents.destroy', ['folder', $folder->id]))->assertSessionHas('success');
        $this->assertSame(0, DocumentFolder::count());
        $this->assertSame(0, DocumentFile::count());

        $this->actingAs($this->head)->get(route('staff.documents.recycle-bin', $this->finance))
            ->assertOk()->assertSee('Reports')->assertDontSee('Inside.txt')->assertSee('1 file inside');

        // Members (edit only) don't see what others deleted and can't restore it.
        $this->actingAs($this->member)->get(route('staff.documents.recycle-bin', $this->finance))->assertOk()->assertDontSee('Reports');
        $this->actingAs($this->member)->post(route('staff.documents.recycle-bin.restore', ['folder', $folder->id]))->assertForbidden();

        // A new folder took the name meanwhile, so the restored one is renamed.
        $this->folder('Reports');
        $this->actingAs($this->head)->post(route('staff.documents.recycle-bin.restore', ['folder', $folder->id]))->assertSessionHas('success');

        $this->assertSame('Reports (restored)', $folder->fresh()->name);
        $this->assertSame(3, DocumentFolder::count());
        $this->assertSame($sub->id, DocumentFile::firstOrFail()->folder_id);
    }

    public function test_purging_and_emptying_the_recycle_bin_removes_files_for_good(): void
    {
        $this->upload($this->member, 'One.txt', null, 'one');
        $this->upload($this->member, 'One.txt', null, 'two'); // second version
        $this->upload($this->member, 'Two.txt');
        [$one, $two] = DocumentFile::orderBy('id')->get()->all();
        $paths = $one->versions()->pluck('path')->push($two->path)->all();

        $this->actingAs($this->head)->delete(route('staff.documents.destroy', ['file', $one->id]));
        $this->actingAs($this->head)->delete(route('staff.documents.destroy', ['file', $two->id]));

        $this->actingAs($this->head)->delete(route('staff.documents.recycle-bin.purge', ['file', $one->id]))->assertSessionHas('success');
        $this->assertSame(1, DocumentFile::onlyTrashed()->count());

        $this->actingAs($this->member)->delete(route('staff.documents.recycle-bin.empty', $this->finance))->assertForbidden();
        $this->actingAs($this->head)->delete(route('staff.documents.recycle-bin.empty', $this->finance))->assertSessionHas('success');

        $this->assertSame(0, DocumentFile::withTrashed()->count());
        foreach ($paths as $path) Storage::disk('local')->assertMissing($path);
    }

    public function test_old_recycle_bin_items_are_purged_automatically(): void
    {
        $this->upload($this->member, 'Old.txt');
        $file = DocumentFile::firstOrFail();
        $this->actingAs($this->head)->delete(route('staff.documents.destroy', ['file', $file->id]));
        DocumentFile::onlyTrashed()->update(['deleted_at' => now()->subDays(DocumentFolder::RETENTION_DAYS + 1)]);

        $this->assertSame(1, app(\App\Services\DocumentLibraryService::class)->purgeExpired());
        $this->assertSame(0, DocumentFile::withTrashed()->count());
    }

    public function test_items_can_be_moved_between_folders(): void
    {
        $from = $this->folder('From');
        $to = $this->folder('To');
        $this->upload($this->member, 'Move me.txt', $from);
        $file = DocumentFile::firstOrFail();

        $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'move', 'items' => ['file:'.$file->id, 'folder:'.$from->id], 'destination' => (string) $to->id,
        ])->assertSessionHas('success');

        // The file sat inside the moved folder, so it travels with it rather than on its own.
        $this->assertSame($to->id, $from->fresh()->parent_id);
        $this->assertSame($from->id, $file->fresh()->folder_id);

        // A folder can't be moved into its own subfolder.
        $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'move', 'items' => ['folder:'.$to->id], 'destination' => (string) $from->id,
        ])->assertSessionHasErrors('items');

        // Name clashes in the destination are refused.
        $this->upload($this->member, 'Move me.txt');
        $rootFile = DocumentFile::whereNull('folder_id')->firstOrFail();
        $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'move', 'items' => ['file:'.$rootFile->id], 'destination' => (string) $from->id,
        ])->assertSessionHasErrors('items');
    }

    public function test_view_only_people_cannot_move_and_edit_people_cannot_bulk_delete(): void
    {
        $folder = $this->folder();
        $this->upload($this->member, 'A.txt', $folder);
        DocumentShare::create(['shareable_type' => 'folder', 'shareable_id' => $folder->id, 'user_id' => $this->outsider->id, 'role' => 'view']);
        $file = DocumentFile::firstOrFail();

        $this->actingAs($this->outsider)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'move', 'items' => ['file:'.$file->id], 'destination' => 'root',
        ])->assertForbidden();
        $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'delete', 'items' => ['file:'.$file->id],
        ])->assertForbidden();

        $this->actingAs($this->head)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'delete', 'items' => ['file:'.$file->id, 'folder:'.$folder->id],
        ])->assertSessionHas('success');
        $this->assertSame(0, DocumentFolder::count());
        $this->assertSame(1, DocumentFolder::onlyTrashed()->where('is_delete_root', true)->count());
    }

    public function test_selected_items_download_as_a_zip(): void
    {
        $folder = $this->folder();
        $this->upload($this->member, 'Inner.txt', $folder, 'inner');
        $this->upload($this->member, 'Top.txt', null, 'top');
        $top = DocumentFile::where('name', 'Top.txt')->firstOrFail();

        $response = $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'download', 'items' => ['folder:'.$folder->id, 'file:'.$top->id],
        ])->assertOk();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $this->assertSame('inner', $zip->getFromName('Reports/Inner.txt'));
        $this->assertSame('top', $zip->getFromName('Top.txt'));
        $zip->close();

        // A single file comes back as itself, not zipped.
        $this->actingAs($this->member)->post(route('staff.documents.bulk', $this->finance), [
            'action' => 'download', 'items' => ['file:'.$top->id],
        ])->assertOk()->assertDownload('Top.txt');
    }

    public function test_sharing_a_folder_gives_view_access_to_it_and_everything_inside(): void
    {
        $folder = $this->folder();
        $sub = DocumentFolder::create(['department_id' => $this->finance->id, 'parent_id' => $folder->id, 'name' => 'Q1']);
        $this->upload($this->member, 'Q1 report.pdf', $sub, '%PDF-1.4 test');
        $file = DocumentFile::firstOrFail();

        $this->actingAs($this->head)->post(route('staff.documents.share', ['folder', $folder->id]), [
            'user_ids' => [$this->outsider->id], 'role' => 'view', 'notify' => 1, 'message' => 'For the audit',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('document_shares', ['shareable_type' => 'folder', 'shareable_id' => $folder->id, 'user_id' => $this->outsider->id, 'role' => 'view']);
        $this->assertTrue(UserNotification::where('user_id', $this->outsider->id)->where('type', 'document_share')->exists());

        $this->actingAs($this->outsider)->get(route('staff.documents.shared'))->assertOk()->assertSee('Reports')->assertSee('Fiona Head');
        $this->actingAs($this->outsider)->get(route('staff.documents.library', ['department' => $this->finance, 'folder' => $sub->id]))
            ->assertOk()->assertSee('Q1 report.pdf')->assertSee('Can view');
        $this->actingAs($this->outsider)->get(route('staff.documents.download', $file))->assertOk();

        // Still no access to the site root or to upload.
        $this->actingAs($this->outsider)->get(route('staff.documents.library', $this->finance))->assertForbidden();
        $this->upload($this->outsider, 'Mine.txt', $folder)->assertForbidden();
    }

    public function test_edit_share_allows_uploads_and_role_changes_and_removal(): void
    {
        $folder = $this->folder();
        $this->actingAs($this->head)->post(route('staff.documents.share', ['folder', $folder->id]), [
            'user_ids' => [$this->outsider->id], 'role' => 'edit',
        ]);
        $share = DocumentShare::firstOrFail();

        $this->upload($this->outsider, 'Notes.txt', $folder)->assertSessionHas('success');

        $this->actingAs($this->head)->put(route('staff.documents.shares.update', $share), ['role' => 'view'])->assertSessionHas('success');
        $this->upload($this->outsider, 'More.txt', $folder)->assertForbidden();

        $this->actingAs($this->head)->delete(route('staff.documents.shares.destroy', $share))->assertSessionHas('success');
        $this->actingAs($this->outsider)->get(route('staff.documents.library', ['department' => $this->finance, 'folder' => $folder->id]))->assertForbidden();
    }

    public function test_site_share_gives_access_to_the_whole_library(): void
    {
        $this->actingAs($this->head)->post(route('staff.documents.share', ['site', $this->finance->id]), [
            'user_ids' => [$this->outsider->id], 'role' => 'view',
        ])->assertSessionHas('success');

        $this->actingAs($this->outsider)->get(route('staff.documents.index'))->assertOk()->assertSee('Finance Unit');
        $this->actingAs($this->outsider)->get(route('staff.documents.library', $this->finance))->assertOk();
    }

    public function test_access_pane_lists_owner_members_and_shares(): void
    {
        $folder = $this->folder();
        DocumentShare::create(['shareable_type' => 'folder', 'shareable_id' => $folder->id, 'user_id' => $this->outsider->id, 'role' => 'view', 'granted_by' => $this->head->id]);

        $this->actingAs($this->head)
            ->get(route('staff.documents.library', ['department' => $this->finance, 'panel' => 'access', 'item' => 'folder:'.$folder->id]))
            ->assertOk()
            ->assertSee('Grant access')
            ->assertSee('Olga Outsider')
            ->assertSee('Mark Member')
            ->assertSee('Owners');

        // Members can see who has access but not manage it.
        $this->actingAs($this->member)
            ->get(route('staff.documents.library', ['department' => $this->finance, 'panel' => 'access', 'item' => 'folder:'.$folder->id]))
            ->assertOk()->assertDontSee('Grant access')->assertSee('Olga Outsider');
    }

    public function test_administrators_see_every_department(): void
    {
        $admin = $this->staff('Ada Admin');
        $admin->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'administrator'])->id);

        $this->actingAs($admin)->get(route('staff.documents.index'))->assertOk()->assertSee('Finance Unit')->assertSee('Programmes Unit');
        $this->actingAs($admin)->get(route('staff.documents.library', $this->programmes))->assertOk()->assertSee('Full control');
    }

    public function test_rename_keeps_the_extension_and_tiles_view_renders(): void
    {
        $this->upload($this->member, 'draft.docx');
        $file = DocumentFile::firstOrFail();

        $this->actingAs($this->member)->put(route('staff.documents.rename', ['file', $file->id]), ['name' => 'Final plan'])
            ->assertSessionHas('success');
        $this->assertSame('Final plan.docx', $file->fresh()->name);

        $this->actingAs($this->member)->get(route('staff.documents.library', ['department' => $this->finance, 'view' => 'tiles']))
            ->assertOk()->assertSee('Final plan.docx');
        $this->actingAs($this->member)->get(route('staff.documents.library', ['department' => $this->finance, 'q' => 'plan']))
            ->assertOk()->assertSee('1 result');
    }
}

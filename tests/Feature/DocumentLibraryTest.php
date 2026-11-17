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
        Storage::disk('local')->assertMissing($path);
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

<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipantsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    private function viewer(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $role = Role::firstOrCreate(['slug' => 'participant-listing'], ['name' => 'Participant Listing']);
        $role->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'users.view'], ['name' => 'Users View', 'module' => 'users'])->id,
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    public function test_participants_page_lists_only_participant_accounts(): void
    {
        User::factory()->create(['name' => 'Ada Participant', 'user_type' => 'participant', 'status' => 'active']);
        User::factory()->create(['name' => 'Sam Staff', 'user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($this->admin())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee('Participants')
            ->assertSee('Ada Participant')
            ->assertDontSee('Sam Staff');
    }

    public function test_participants_page_ignores_the_user_type_filter(): void
    {
        User::factory()->create(['name' => 'Grace Learner', 'user_type' => 'participant', 'status' => 'active']);
        User::factory()->create(['name' => 'Miles Instructor', 'user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['user_type' => 'staff']))
            ->assertOk()
            ->assertSee('Grace Learner')
            ->assertDontSee('Miles Instructor');
    }

    public function test_participants_page_requires_the_users_view_permission(): void
    {
        $stranger = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($stranger)
            ->get(route('admin.participants.index'))
            ->assertForbidden();
    }

    public function test_read_only_viewers_do_not_see_edit_controls_or_user_forms(): void
    {
        User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $html = $this->actingAs($this->viewer())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('createUserModal', $html);
        $this->assertStringNotContainsString('data-modal-open="editUser', $html);
    }

    public function test_admin_can_bulk_delete_selected_participants_only(): void
    {
        $first = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $second = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $kept = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($this->admin())
            ->delete(route('admin.participants.bulk-destroy'), ['ids' => [$first->id, $second->id, $staff->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $first->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
        $this->assertDatabaseHas('users', ['id' => $kept->id]);
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    public function test_bulk_delete_requires_the_users_delete_permission(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($this->viewer())
            ->delete(route('admin.participants.bulk-destroy'), ['ids' => [$participant->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $participant->id]);
    }

    public function test_the_learning_workspace_links_to_course_calls(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.workspace.learning'))
            ->assertOk()
            ->assertSee('Course Calls');
    }
}

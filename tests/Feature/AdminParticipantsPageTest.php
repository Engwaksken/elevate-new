<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Course;
use App\Models\Enrolment;
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

    public function test_saved_user_roles_render_as_checked_in_the_edit_form(): void
    {
        $participant = User::factory()->create(['name' => 'Ada Participant', 'user_type' => 'participant', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'learner-role-checkbox-test'], ['name' => 'Learner role checkbox test']);
        $participant->roles()->attach($role);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<input type="checkbox" name="roles\[\]" value="'.preg_quote((string) $role->id, '/').'" checked/',
            $html
        );
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

    public function test_participants_page_shows_core_course_and_can_assign_missing_compulsory_course(): void
    {
        $participant = User::factory()->create(['name' => 'Learner With Missing Course', 'user_type' => 'participant', 'status' => 'active']);
        $core = Course::create(['title' => 'Digital Skills Core', 'status' => 'published']);
        $required = Course::create(['title' => 'Compulsory Orientation', 'status' => 'published']);
        $core->compulsoryCourses()->attach($required->id);
        Enrolment::create([
            'user_id' => $participant->id,
            'course_id' => $core->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee('Core course:')
            ->assertSee('Digital Skills Core')
            ->assertSee('Missing compulsory:')
            ->assertSee('Compulsory Orientation')
            ->assertSee('Assign missing');

        $this->post(route('admin.participants.sync-compulsory', $participant))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrolments', [
            'user_id' => $participant->id,
            'course_id' => $required->id,
            'status' => 'enrolled',
        ]);
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

    public function test_admin_can_bulk_delete_selected_users_and_participants(): void
    {
        $first = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $second = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $kept = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.bulk-destroy'), ['ids' => [$first->id, $second->id, $staff->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $first->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('users', ['id' => $kept->id]);
    }

    public function test_bulk_delete_cannot_remove_super_administrators_or_the_current_user(): void
    {
        $admin = $this->admin();
        $super = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $super->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        $this->actingAs($admin)
            ->delete(route('admin.users.bulk-destroy'), ['ids' => [$admin->id, $super->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $super->id]);
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

    public function test_participants_page_shows_registration_and_branch_statistics(): void
    {
        $kampala = \App\Models\Branch::create(['name' => 'Kampala Hub', 'code' => 'KLA', 'is_active' => true]);
        $gulu = \App\Models\Branch::create(['name' => 'Gulu Hub', 'code' => 'GUL', 'is_active' => true]);

        $today = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $today->branches()->attach($kampala);

        $lastYear = User::factory()->create(['user_type' => 'participant', 'status' => 'pending']);
        $lastYear->forceFill(['created_at' => now()->subYear()])->save();
        $lastYear->branches()->attach($gulu);

        User::factory()->create(['user_type' => 'employer', 'status' => 'active']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee('Participants by Branch')
            ->assertSee('Kampala Hub')
            ->assertSee('Gulu Hub')
            ->assertSee('Registered Today')
            ->assertSee('Registered This Month')
            ->assertSee('Registered This Year')
            ->assertDontSee('Employers');

        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['today']);
        $this->assertSame(1, $stats['year']);
        $this->assertSame(1, $stats['inactive']);

        $branchStats = $response->viewData('branchStats');
        $this->assertSame(2, $branchStats['all']);
        $this->assertSame([1, 1], $branchStats['items']->pluck('count')->all());

        $filtered = $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['branch_id' => $gulu->id]))
            ->assertOk();

        $this->assertSame(1, $filtered->viewData('stats')['total']);
        $this->assertSame(0, $filtered->viewData('stats')['today']);
        $this->assertSame(['Gulu Hub', 'Kampala Hub'], $filtered->viewData('branchStats')['items']->pluck('branch.name')->all());
    }
}

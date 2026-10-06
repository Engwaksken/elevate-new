<?php

namespace Tests\Feature;

use App\Models\ItSupportTicket;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffHelpSupportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attributes = []): User
    {
        return User::factory()->create([
            'user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now(), ...$attributes,
        ]);
    }

    private function itLead(): User
    {
        $role = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $user = $this->staff();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_without_permissions_can_open_help_and_support(): void
    {
        $this->actingAs($this->staff())->get(route('staff.support.index'))
            ->assertOk()
            ->assertViewIs('staff.support.index')
            ->assertSee('Help &amp; Support', false)
            ->assertSee('New help request')
            ->assertSee('Laptop / hardware')
            ->assertSee('Network / internet')
            ->assertSee('General help');
    }

    public function test_staff_can_submit_a_request_and_it_team_is_notified_and_audited(): void
    {
        $staff = $this->staff();
        $itLead = $this->itLead();
        $assistantRole = Role::firstOrCreate(['slug' => 'it-assistant'], ['name' => 'IT Assistant']);
        $assistant = $this->staff();
        $assistant->roles()->attach($assistantRole);
        $bystander = $this->staff();

        $this->actingAs($staff)->post(route('staff.support.store'), [
            'subject' => 'Laptop screen flickers',
            'description' => 'Since this morning the screen flickers. Asset tag WITU-0042.',
            'category' => 'hardware',
            'priority' => 'high',
        ])->assertRedirect(route('staff.support.index'))->assertSessionHas('success');

        $ticket = ItSupportTicket::query()->where('requester_id', $staff->id)->sole();
        $this->assertSame('open', $ticket->status);
        $this->assertSame('hardware', $ticket->category);
        $this->assertSame('high', $ticket->priority);

        $this->assertDatabaseHas('audit_logs', ['auditable_type' => ItSupportTicket::class, 'auditable_id' => $ticket->id, 'action' => 'created']);
        foreach ([$itLead, $assistant] as $member) {
            $notice = UserNotification::query()->where('user_id', $member->id)->sole();
            $this->assertSame('it_support_ticket', $notice->type);
            $this->assertStringNotContainsString('Laptop screen flickers', $notice->title.' '.$notice->message.' '.json_encode($notice->data));
        }
        $this->assertSame(0, UserNotification::query()->where('user_id', $bystander->id)->count());
        $this->assertSame(0, UserNotification::query()->where('user_id', $staff->id)->count());
    }

    public function test_submission_is_validated(): void
    {
        $this->actingAs($this->staff())->from(route('staff.support.index'))->post(route('staff.support.store'), [
            'subject' => '', 'description' => '', 'category' => 'not-a-category', 'priority' => 'whenever',
        ])->assertSessionHasErrors(['subject', 'description', 'category', 'priority']);

        $this->assertSame(0, ItSupportTicket::count());
    }

    public function test_staff_only_see_their_own_requests_including_exports(): void
    {
        $staff = $this->staff();
        $other = $this->staff();
        $mine = ItSupportTicket::factory()->create(['requester_id' => $staff->id, 'subject' => 'My printer is jammed', 'category' => 'printer']);
        $theirs = ItSupportTicket::factory()->create(['requester_id' => $other->id, 'subject' => 'Someone else VPN issue']);

        $this->actingAs($staff)->get(route('staff.support.index'))
            ->assertOk()->assertSee($mine->subject)->assertDontSee($theirs->subject);

        $csv = $this->actingAs($staff)->get(route('staff.support.index', ['export' => 'csv']));
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('My printer is jammed', $body);
        $this->assertStringNotContainsString('Someone else VPN issue', $body);

        $this->actingAs($staff)->get(route('staff.support.index', ['export' => 'pdf']))->assertOk();
    }

    public function test_it_team_ticket_can_still_be_filtered_in_queue_by_new_category(): void
    {
        $itLead = $this->itLead();
        ItSupportTicket::factory()->create(['category' => 'network', 'subject' => 'Office wifi down']);

        $this->actingAs($itLead)->get(route('it-support.tickets.index', ['category' => 'network']))
            ->assertOk()->assertSee('Office wifi down');
    }

    public function test_participants_and_guests_cannot_use_the_staff_route(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($participant)->get(route('staff.support.index'))->assertForbidden();
        $this->actingAs($participant)->post(route('staff.support.store'), [
            'subject' => 'x', 'description' => 'y', 'category' => 'general', 'priority' => 'normal',
        ])->assertForbidden();
        $this->assertSame(0, ItSupportTicket::count());

        auth()->logout();
        $this->get(route('staff.support.index'))->assertRedirect();
    }

    public function test_sidebar_shows_help_and_support_to_plain_staff_but_queue_only_to_it(): void
    {
        $this->actingAs($this->staff())->get(route('staff.support.index'))
            ->assertSee(route('staff.support.index'), false)
            ->assertDontSee('IT Support Queue');

        $this->actingAs($this->itLead())->get(route('staff.support.index'))
            ->assertSee(route('staff.support.index'), false)
            ->assertSee('IT Support Queue')
            ->assertSee(route('it-support.tickets.index'), false);
    }

    public function test_instructor_sidebar_has_help_and_support(): void
    {
        $instructor = $this->staff();
        $instructor->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));

        $this->actingAs($instructor)->get(route('staff.support.index'))
            ->assertOk()
            ->assertSee('Instructor / Trainer')
            ->assertSee(route('staff.support.index'), false);
    }
}

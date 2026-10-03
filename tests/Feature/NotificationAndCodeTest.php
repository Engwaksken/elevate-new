<?php

namespace Tests\Feature;

use App\Mail\SystemMail;
use App\Models\Milestone;
use App\Models\Programme;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Workplan;
use App\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationAndCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_a_task_notifies_the_assignee_in_app_and_by_email(): void
    {
        Mail::fake();

        $actor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $assignee = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email' => 'assignee@example.com']);

        $this->actingAs($actor);

        Task::create([
            'title' => 'Prepare the report',
            'assigned_to' => $assignee->id,
            'created_by' => $actor->id,
            'status' => 'not_started',
        ]);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $assignee->id, 'type' => 'task']);
        Mail::assertSent(SystemMail::class, fn (SystemMail $mail) => $mail->hasTo('assignee@example.com'));
    }

    public function test_assigning_a_milestone_notifies_the_responsible_user(): void
    {
        Mail::fake();

        $actor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $owner = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email' => 'owner@example.com']);

        $this->actingAs($actor);

        $workplan = Workplan::create(['title' => 'Annual plan', 'period_type' => 'annual', 'status' => 'in_progress', 'created_by' => $actor->id]);
        Milestone::create(['workplan_id' => $workplan->id, 'title' => 'Deliver phase one', 'responsible_user_id' => $owner->id, 'status' => 'not_started']);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $owner->id, 'type' => 'milestone']);
        Mail::assertSent(SystemMail::class, fn (SystemMail $mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_code_generator_produces_sequential_unique_codes(): void
    {
        $generator = app(CodeGenerator::class);
        $year = now()->year;

        $first = $generator->next('PRG', Programme::class);
        $this->assertSame("PRG-{$year}-001", $first);

        Programme::create(['name' => 'Digital Skills', 'status' => 'active', 'code' => $first]);

        $this->assertSame("PRG-{$year}-002", $generator->next('PRG', Programme::class));
    }

    public function test_creating_a_programme_without_a_code_generates_one(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        $this->actingAs($admin)
            ->post(route('admin.programmes.store'), [
                'name' => 'Women in Tech',
                'status' => 'active',
            ])
            ->assertRedirect();

        $programme = Programme::where('name', 'Women in Tech')->firstOrFail();
        $this->assertNotNull($programme->code);
        $this->assertStringStartsWith('PRG-'.now()->year.'-', $programme->code);
    }
}

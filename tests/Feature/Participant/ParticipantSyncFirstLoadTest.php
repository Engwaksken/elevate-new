<?php

namespace Tests\Feature\Participant;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Enrolment;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The first sync after sign-in (no last_synced_at) sends only current data,
 * so sign-in is quick on phones with little data; later syncs still send
 * every change, including deletions.
 */
class ParticipantSyncFirstLoadTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant';

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        $course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
        Enrolment::create(['user_id' => $this->participant->id, 'course_id' => $course->id, 'status' => 'enrolled']);

        $employer = Employer::create(['owner_user_id' => User::factory()->create()->id, 'company_name' => 'Acme', 'status' => 'approved']);
        $job = fn (array $attrs) => Job::create($attrs + ['employer_id' => $employer->id, 'positions' => 1]);
        $job(['title' => 'Open role', 'status' => 'published', 'application_deadline' => today()->addWeek()]);
        $job(['title' => 'No deadline role', 'status' => 'published']);
        $job(['title' => 'Expired role', 'status' => 'published', 'application_deadline' => today()->subDay()]);
        $job(['title' => 'Draft role', 'status' => 'draft']);
        $job(['title' => 'Deleted role', 'status' => 'published'])->delete();

        $event = fn (array $attrs) => DB::table('events')->insert($attrs + ['created_at' => now(), 'updated_at' => now()]);
        $event(['title' => 'Upcoming meetup', 'starts_at' => now()->addDays(3), 'is_published' => 1]);
        $event(['title' => 'Last week workshop', 'starts_at' => now()->subWeek(), 'is_published' => 1]);
        $event(['title' => 'Old event', 'starts_at' => now()->subMonths(6), 'is_published' => 1]);
        $event(['title' => 'Unpublished event', 'starts_at' => now()->addDays(3), 'is_published' => 0]);
        $event(['title' => 'Deleted event', 'starts_at' => now()->addDays(3), 'is_published' => 1, 'deleted_at' => now()]);

        DB::table('user_notifications')->insert(collect(range(1, 130))->map(fn ($i) => [
            'user_id' => $this->participant->id, 'type' => 'info', 'title' => "Notice {$i}",
            'created_at' => now(), 'updated_at' => now(),
        ])->all());
    }

    public function test_first_sync_sends_only_current_data(): void
    {
        Sanctum::actingAs($this->participant, ['participant']);

        $data = $this->getJson(self::BASE.'/sync')->assertOk()->json();

        $this->assertEqualsCanonicalizing(['Open role', 'No deadline role'], collect($data['jobs'])->pluck('title')->all());
        $this->assertEqualsCanonicalizing(['Upcoming meetup', 'Last week workshop'], collect($data['events'])->pluck('title')->all());

        $this->assertCount(100, $data['notifications']);
        $this->assertSame('Notice 130', collect($data['notifications'])->sortByDesc('id')->first()['title']);
    }

    public function test_incremental_sync_still_sends_deletions(): void
    {
        Sanctum::actingAs($this->participant, ['participant']);

        $data = $this->getJson(self::BASE.'/sync?last_synced_at='.urlencode(now()->subHour()->format('Y-m-d H:i:s')))->assertOk()->json();

        $titles = collect($data['jobs'])->pluck('title');
        $this->assertTrue($titles->contains('Deleted role'), 'Deleted jobs must reach existing caches so they can be removed.');
        $this->assertTrue(collect($data['events'])->pluck('title')->contains('Deleted event'));
        $this->assertGreaterThan(100, count($data['notifications']), 'Later syncs are not capped.');
    }
}

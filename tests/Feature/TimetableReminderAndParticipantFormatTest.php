<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Programme;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\TimetableReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimetableReminderAndParticipantFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_reminder_command_is_available(): void
    {
        $this->artisan('timetable:send-reminders')->expectsOutputToContain('Sent 0 timetable reminder(s).')->assertSuccessful();
    }

    public function test_ten_minute_reminders_reach_participants_and_assigned_trainers_once(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-05 06:00:00', 'UTC'));
        $course = Course::create(['title'=>'Skills','status'=>'published']);
        $participant = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $trainer = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $other = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $course->instructors()->attach($trainer);
        Enrolment::create(['course_id'=>$course->id,'user_id'=>$participant->id,'status'=>'enrolled']);
        $slot = $course->timeSlots()->create(['title'=>'Lesson 1','starts_at'=>now()->utc()->addMinutes(11),'ends_at'=>now()->utc()->addMinutes(60),'timezone'=>'Africa/Kampala']);
        $service = app(TimetableReminderService::class);
        $this->assertSame(0, $service->sendDue());
        $this->travel(1)->minutes();
        $this->assertSame(2, $service->sendDue());
        $this->assertSame(0, $service->sendDue());
        $this->assertSame(1, UserNotification::where('user_id',$participant->id)->where('type','course_timetable_reminder')->count());
        $this->assertSame(1, UserNotification::where('user_id',$trainer->id)->where('type','course_timetable_reminder')->count());
        $this->assertSame(0, UserNotification::where('user_id',$other->id)->where('type','course_timetable_reminder')->count());
        $this->assertStringContainsString('10 minutes', UserNotification::where('user_id',$participant->id)->where('type','course_timetable_reminder')->sole()->message);
        $slot->update(['starts_at'=>now()->utc()->addMinutes(9)]);
        $this->assertSame(2, $service->sendDue());
        $slot->update(['status'=>'cancelled']);
        $this->assertSame(0, $service->sendDue());
        $this->travelBack();
    }

    public function test_withdrawn_and_inactive_users_are_excluded_and_sync_is_a_full_reminder_snapshot(): void
    {
        $course = Course::create(['title'=>'Skills','status'=>'published']);
        $user = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $enrolment = Enrolment::create(['course_id'=>$course->id,'user_id'=>$user->id,'status'=>'enrolled']);
        $slot = $course->timeSlots()->create(['title'=>'Lesson','starts_at'=>now()->utc()->addMinutes(10),'ends_at'=>now()->utc()->addHour(),'timezone'=>'Africa/Kampala']);
        Sanctum::actingAs($user, ['participant']);
        $this->getJson('/api/v1/participant/sync?last_synced_at='.urlencode(now()->toIso8601String()))->assertOk()->assertJsonCount(1,'timetable_reminders');
        $enrolment->update(['status'=>'withdrawn']);
        $this->assertSame(0, app(TimetableReminderService::class)->sendDue());
        $this->getJson('/api/v1/participant/sync')->assertJsonCount(0,'timetable_reminders');
        $enrolment->update(['status'=>'enrolled']); $user->update(['status'=>'inactive']);
        $this->assertSame(0, app(TimetableReminderService::class)->sendDue());
    }

    public function test_id_normalization_preserves_canonical_ids_aliases_and_deleted_sequence_numbers(): void
    {
        $program = Programme::create(['name'=>'Program','code'=>'PRG']);
        $branch = Branch::create(['name'=>'Kampala','code'=>'KLA']);
        $cohort = Cohort::create(['name'=>'Cohort 1','code'=>'1','branch_id'=>$branch->id,'programme_id'=>$program->id]);
        $course = Course::create(['title'=>'Skills','programme_id'=>$program->id,'branch_id'=>$branch->id]);
        $first = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $second = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $attributes = ['course_id'=>$course->id,'cohort_id'=>$cohort->id,'enrolled_at'=>'2026-10-02'];
        $a = Enrolment::create($attributes + ['user_id'=>$first->id]);
        $b = Enrolment::create($attributes + ['user_id'=>$second->id]);
        DB::table('enrolments')->where('id',$a->id)->update(['enrolment_code'=>'PRG/KLA/1/26/001']);
        DB::table('users')->where('id',$first->id)->update(['participant_code'=>'PRG/KLA/1/26/001']);
        DB::table('enrolments')->where('id',$b->id)->update(['enrolment_code'=>'PRG/KLA/C1/26/001']);
        DB::table('users')->where('id',$second->id)->update(['participant_code'=>'PRG/KLA/C1/26/001']);
        DB::table('enrolment_identity_sequences')->insert(['prefix'=>'PRG/KLA/1/26','last_number'=>50]);
        Schema::drop('participant_id_aliases');
        (require database_path('migrations/2026_10_02_170000_normalize_participant_ids.php'))->up();
        $this->assertSame('PRG/KLA/C1/26/001', $second->fresh()->participant_code);
        $this->assertSame('PRG/KLA/C1/26/051', $first->fresh()->participant_code);
        $this->assertDatabaseHas('participant_id_aliases', ['user_id'=>$first->id,'alias'=>'PRG/KLA/1/26/001']);
        $third = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $next = Enrolment::create($attributes + ['user_id'=>$third->id]);
        $this->assertSame('PRG/KLA/C1/26/052', $next->enrolment_code);
    }
}

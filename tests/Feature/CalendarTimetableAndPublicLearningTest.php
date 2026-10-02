<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CalendarEvent;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseTimeSlot;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CalendarTimetableAndPublicLearningTest extends TestCase
{
    use RefreshDatabase;

    private function participant(Course $course): User
    {
        $user = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        Enrolment::create(['course_id'=>$course->id,'user_id'=>$user->id,'status'=>'enrolled']);
        return $user;
    }

    private function slot(Course $course, string $title, string $date = '2026-10-15'): CourseTimeSlot
    {
        return $course->timeSlots()->create(['title'=>$title,
            'starts_at'=>CarbonImmutable::parse($date.' 08:00', 'UTC'),
            'ends_at'=>CarbonImmutable::parse($date.' 09:00', 'UTC'), 'timezone'=>'Africa/Kampala',
            'venue'=>'Classroom', 'meeting_link'=>'https://meet.example.test/'.$course->id]);
    }

    private function learningCourse(): Course
    {
        $course = Course::create(['title'=>'Digital Skills','summary'=>'Learn practical digital skills.','description'=>'Long internal description','status'=>'published']);
        $module = CourseModule::create(['course_id'=>$course->id,'title'=>'Private module outline','position'=>1,'is_published'=>true]);
        Lesson::create(['course_module_id'=>$module->id,'title'=>'Private lesson title','content'=>'Protected teaching content','content_type'=>'text','position'=>1,'is_published'=>true]);
        Assessment::create(['course_id'=>$course->id,'title'=>'Private assessment','type'=>'assignment','is_published'=>true]);
        return $course;
    }

    public function test_public_learning_displays_course_images_names_and_briefs_without_learning_content(): void
    {
        $course = $this->learningCourse();
        $this->get(route('learning.index'))->assertOk()->assertSee('Digital Skills')->assertSee('Learn practical digital skills.')
            ->assertSee('course-placeholder.svg')->assertDontSee('Private module outline')->assertDontSee('Private lesson title')->assertDontSee(' modules');
        $this->get(route('learning.course.show', $course))->assertOk()->assertDontSee('Private module outline')->assertDontSee('Private lesson title')->assertDontSee('Private assessment')->assertDontSee('Long internal description');
        $this->getJson('/api/v1/courses/'.$course->id)->assertOk()->assertJsonMissingPath('data.modules')->assertJsonMissingPath('data.assessments')->assertJsonPath('data.summary','Learn practical digital skills.');
    }

    public function test_enrolled_dashboard_has_lessons_but_public_details_remain_a_brief_even_after_login(): void
    {
        $course = $this->learningCourse();
        $slot = $this->slot($course, 'Scheduled workshop');
        $user = $this->participant($course);
        $this->get(route('learning.course.dashboard', $course))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('learning.course.show', $course))->assertOk()->assertDontSee('Private lesson title')->assertDontSee('Scheduled workshop')->assertSee('Open in My Learning');
        $this->get(route('learning.course.dashboard', $course))->assertOk()->assertSee('Private lesson title')->assertSee('Private assessment')->assertSee('Scheduled workshop');
        $this->get(route('learning.my-courses'))->assertOk()->assertSee(route('learning.course.dashboard', $course), false);
        $other = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        $this->actingAs($other)->get(route('learning.course.dashboard', $course))->assertForbidden();
        $this->actingAs(User::factory()->create(['user_type'=>'staff','status'=>'active']))->get(route('learning.course.dashboard', $course))->assertForbidden();
    }

    public function test_course_images_can_be_uploaded_replaced_and_removed_from_administration(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $admin->roles()->attach(Role::create(['slug'=>'administrator','name'=>'Administrator']));
        $data = ['title'=>'Illustrated Course','summary'=>'Short course summary','delivery_mode'=>'online','pass_mark'=>50,'status'=>'published'];
        $this->actingAs($admin)->post(route('admin.elearning.courses.store'), $data + ['thumbnail'=>UploadedFile::fake()->image('first.png')])->assertSessionHasNoErrors();
        $course = Course::where('title','Illustrated Course')->sole();
        $first = $course->thumbnail_path;
        Storage::disk('public')->assertExists($first);
        $this->get(route('learning.index'))->assertSee($first);
        $this->put(route('admin.elearning.courses.update', $course), $data + ['thumbnail'=>UploadedFile::fake()->image('second.jpg')])->assertSessionHasNoErrors();
        $second = $course->fresh()->thumbnail_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->put(route('admin.elearning.courses.update', $course), $data + ['remove_thumbnail'=>1])->assertSessionHasNoErrors();
        $this->assertNull($course->fresh()->thumbnail_path);
        Storage::disk('public')->assertMissing($second);
        $this->get(route('learning.index'))->assertSee('course-placeholder.svg');
    }

    public function test_participants_calendar_only_shows_their_course_timetables_and_scoped_activities(): void
    {
        $course = Course::create(['title'=>'My Course','status'=>'published']);
        $other = Course::create(['title'=>'Other Course','status'=>'published']);
        $this->slot($course,'My timetable session');
        $this->slot($other,'Other timetable session');
        $user = $this->participant($course);
        CalendarEvent::create(['title'=>'Private staff activity','event_type'=>'meeting','responsible_user_id'=>User::factory()->create(['user_type'=>'staff'])->id,'starts_at'=>'2026-10-15 09:00']);
        Event::create(['title'=>'Public workshop','event_type'=>'workshop','starts_at'=>'2026-10-15 09:00','is_published'=>true]);
        Event::create(['title'=>'Other course event','event_type'=>'workshop','starts_at'=>'2026-10-15 09:00','is_published'=>true,'course_id'=>$other->id]);
        $this->actingAs($user)->get(route('calendar.index',['month'=>'2026-10']))->assertOk()->assertSee('My timetable session')->assertSee('Public workshop')
            ->assertDontSee('Other timetable session')->assertDontSee('Private staff activity')->assertDontSee('Other course event');
        $this->get(route('calendar.index',['month'=>'2026-10','event_type'=>'course_timetable']))->assertOk()->assertSee('My timetable session')->assertDontSee('Public workshop');
    }

    public function test_all_staff_calendar_views_include_courses_without_instructor_assignment(): void
    {
        $a = Course::create(['title'=>'Published Course','status'=>'published']);
        $b = Course::create(['title'=>'Draft Course','status'=>'draft']);
        $this->slot($a,'Published timetable');
        $this->slot($b,'Draft timetable');
        $staff = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $this->actingAs($staff)->get(route('calendar.index',['month'=>'2026-10']))->assertOk()->assertSee('Published timetable')->assertSee('Draft timetable');
        $this->get(route('admin.events.calendar',['month'=>'2026-10']))->assertOk()->assertSee('Published timetable')->assertSee('Draft timetable');
    }

    public function test_calendar_reads_live_updates_and_excludes_stale_mirrored_timetables(): void
    {
        $course = Course::create(['title'=>'Course','status'=>'published']);
        $slot = $this->slot($course,'Original session');
        CalendarEvent::create(['eventable_type'=>CourseTimeSlot::class,'eventable_id'=>$slot->id,'title'=>'Stale mirror','event_type'=>'course_timetable','starts_at'=>'2026-10-15 09:00']);
        $staff = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $this->actingAs($staff)->get(route('calendar.index',['month'=>'2026-10']))->assertOk()->assertDontSee('Stale mirror')
            ->assertViewHas('events', fn ($entries) => $entries->getCollection()->where('title','Original session')->count() === 1);
        $slot->update(['title'=>'Rescheduled session','status'=>'cancelled','starts_at'=>CarbonImmutable::parse('2026-11-15 08:00','UTC'),'ends_at'=>CarbonImmutable::parse('2026-11-15 09:00','UTC')]);
        $this->get(route('calendar.index',['month'=>'2026-10']))->assertDontSee('Original session')->assertDontSee('Rescheduled session');
        $this->get(route('calendar.index',['month'=>'2026-11']))->assertSee('Rescheduled session')->assertSee('Cancelled')->assertDontSee($slot->meeting_link);
        $slot->delete();
        $this->get(route('calendar.index',['month'=>'2026-11']))->assertDontSee('Rescheduled session')->assertDontSee('Stale mirror');
    }

    public function test_calendar_places_sessions_on_the_local_date_and_blocks_inactive_users(): void
    {
        config(['app.timezone'=>'Africa/Kampala']);
        $course = Course::create(['title'=>'Course','status'=>'published']);
        $slot = $course->timeSlots()->create(['title'=>'Late UTC session','starts_at'=>CarbonImmutable::parse('2026-10-31 23:30','UTC'),'ends_at'=>CarbonImmutable::parse('2026-11-01 00:30','UTC'),'timezone'=>'Africa/Kampala']);
        $user = $this->participant($course);
        $this->actingAs($user)->get(route('calendar.index',['month'=>'2026-11']))->assertOk()->assertViewHas('days', fn ($days) => $days->get('2026-11-01')->first()['starts_at']->format('H:i') === '02:30');
        $user->update(['status'=>'inactive']);
        $this->get(route('calendar.index'))->assertForbidden();
    }
}

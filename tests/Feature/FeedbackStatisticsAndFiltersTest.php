<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseFeedbackResponse;
use App\Models\Event;
use App\Models\EventFeedbackResponse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeedbackStatisticsAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function participant(string $name = 'Participant'): User
    {
        return User::factory()->create(['name' => $name, 'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function courseFeedback(User $user, string $title, int $overall, string $submitted): CourseFeedbackResponse
    {
        $course = Course::create(['title' => $title, 'status' => 'published']);
        $enrolment = $user->enrolments()->create(['course_id' => $course->id, 'status' => 'enrolled', 'enrolled_at' => $submitted]);

        return CourseFeedbackResponse::create(['course_id' => $course->id, 'enrolment_id' => $enrolment->id, 'user_id' => $user->id, 'overall_rating' => $overall,
            'content_rating' => 4, 'instructor_rating' => 4, 'recommend_rating' => $overall, 'what_to_improve' => 'More practical sessions please', 'submitted_at' => $submitted]);
    }

    public function test_admin_feedback_page_shows_statistics_tabs_and_filters(): void
    {
        $amina = $this->participant('Amina');
        $grace = $this->participant('Grace');
        $this->courseFeedback($amina, 'Web Basics', 5, '2026-10-20 09:00');
        $this->courseFeedback($grace, 'Data Skills', 2, '2025-03-01 09:00');
        $event = Event::create(['title' => 'Career Fair', 'starts_at' => '2026-10-10 09:00', 'is_published' => true, 'feedback_enabled' => true]);
        $registration = $event->registrations()->create(['user_id' => $amina->id, 'status' => 'registered', 'registered_at' => now()]);
        EventFeedbackResponse::create(['event_id' => $event->id, 'event_registration_id' => $registration->id, 'user_id' => $amina->id, 'overall_rating' => 4, 'relevance_rating' => 4,
            'facilitation_rating' => 4, 'organisation_rating' => 3, 'recommend_rating' => 5, 'what_worked' => 'Great speakers', 'submitted_at' => '2026-10-11 09:00']);

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->actingAs($admin);

        $page = $this->get(route('admin.participant-feedback.index'))->assertOk()
            ->assertSee('Average rating')->assertSee('Satisfied')->assertSee('Would recommend')
            ->assertSee('data-eh-tab="event-feedback"', false)->assertSee('Great speakers')
            ->assertSee('id="course-response-', false);
        $this->assertSame(['total' => 3, 'courses' => 2, 'events' => 1, 'average' => 3.7, 'recommend' => 4.0, 'satisfied' => 67], $page->viewData('stats'));

        $thisMonth = $this->get(route('admin.participant-feedback.index', ['period' => 'month']));
        $this->assertSame(2, $thisMonth->viewData('stats')['total']);
        $this->assertSame(['Web Basics'], $thisMonth->viewData('courseResponses')->map(fn ($r) => $r->course->title)->all());

        $this->assertSame(1, $this->get(route('admin.participant-feedback.index', ['search' => 'Grace']))->viewData('stats')['total']);
        $this->assertSame(1, $this->get(route('admin.participant-feedback.index', ['rating' => 'low']))->viewData('stats')['total']);
        $custom = $this->get(route('admin.participant-feedback.index', ['period' => 'custom', 'from' => '2025-01-01', 'to' => '2025-12-31']));
        $this->assertSame(['Data Skills'], $custom->viewData('courseResponses')->map(fn ($r) => $r->course->title)->all());

        $this->actingAs($this->participant())->get(route('admin.participant-feedback.index'))->assertForbidden();
    }

    public function test_participant_feedback_page_lists_waiting_items_first_with_stats_and_filters(): void
    {
        $user = $this->participant();
        $this->courseFeedback($user, 'Rated Course', 4, '2026-10-02 09:00');
        $waitingCourse = Course::create(['title' => 'Unrated Course', 'status' => 'published']);
        $user->enrolments()->create(['course_id' => $waitingCourse->id, 'status' => 'enrolled', 'enrolled_at' => '2026-10-25 09:00']);
        $event = Event::create(['title' => 'Old Workshop', 'starts_at' => '2025-05-05 09:00', 'is_published' => true, 'feedback_enabled' => true]);
        $event->registrations()->create(['user_id' => $user->id, 'status' => 'registered', 'registered_at' => now()]);

        $page = $this->actingAs($user)->get(route('participant.feedback.index'))->assertOk()
            ->assertSee('Courses waiting for feedback')->assertSee('Your average rating')->assertSee('Give feedback')->assertSee('Feedback submitted');
        $this->assertSame(['courses_waiting' => 1, 'events_waiting' => 1, 'submitted' => 1, 'average' => 4.0], $page->viewData('stats'));
        $this->assertSame(['Unrated Course', 'Old Workshop', 'Rated Course'], collect($page->viewData('feedbackItems')->items())->pluck('title')->all());

        $titles = fn (array $query) => collect($this->actingAs($user)->get(route('participant.feedback.index', $query))->viewData('feedbackItems')->items())->pluck('title')->all();
        $this->assertSame(['Rated Course'], $titles(['status' => 'submitted']));
        $this->assertSame(['Old Workshop'], $titles(['type' => 'event']));
        $this->assertSame(['Unrated Course', 'Rated Course'], $titles(['period' => 'month']));
        $this->assertSame(['Old Workshop'], $titles(['search' => 'workshop']));
    }
}

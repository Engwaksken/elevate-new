<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyEntryAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function scoredSurvey(): array
    {
        $survey = Survey::create([
            'title' => 'Entry survey',
            'slug' => 'entry-survey',
            'access_type' => 'authenticated',
            'status' => 'published',
            'is_scored' => true,
            'pass_mark' => 50,
        ]);

        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_type' => 'single_choice',
            'question_text' => 'Do you have basic computer skills?',
            'options' => ['Yes', 'No'],
            'marks' => 2,
            'correct_answer' => ['value' => 'Yes'],
            'is_required' => true,
            'position' => 1,
        ]);

        return [$survey, $question];
    }

    private function courseWithSurvey(Survey $survey): Course
    {
        return Course::create([
            'title' => 'Digital Skills',
            'status' => 'published',
            'pass_mark' => 50,
            'self_enrolment_enabled' => true,
            'entry_survey_id' => $survey->id,
        ]);
    }

    public function test_passing_the_entry_survey_unlocks_enrolment(): void
    {
        [$survey, $question] = $this->scoredSurvey();
        $course = $this->courseWithSurvey($survey);
        $participant = $this->participant();

        $this->actingAs($participant)->put(route('participant.surveys.save', $survey), [
            'submit' => 1,
            'question_'.$question->id => 'Yes',
        ])->assertRedirect(route('participant.surveys.index'));

        $response = SurveyResponse::where('survey_id', $survey->id)->where('user_id', $participant->id)->firstOrFail();
        $this->assertSame('submitted', $response->status);
        $this->assertEquals(100, (float) $response->percentage);
        $this->assertTrue($response->passed);

        $this->actingAs($participant)->post(route('learning.enrol', $course))->assertRedirect(route('learning.my-courses'));
        $this->assertTrue(Enrolment::where('course_id', $course->id)->where('user_id', $participant->id)->exists());
    }

    public function test_failing_the_entry_survey_blocks_enrolment(): void
    {
        [$survey, $question] = $this->scoredSurvey();
        $course = $this->courseWithSurvey($survey);
        $participant = $this->participant();

        $this->actingAs($participant)->put(route('participant.surveys.save', $survey), [
            'submit' => 1,
            'question_'.$question->id => 'No',
        ]);

        $response = SurveyResponse::where('survey_id', $survey->id)->where('user_id', $participant->id)->firstOrFail();
        $this->assertEquals(0, (float) $response->percentage);
        $this->assertFalse($response->passed);

        $this->actingAs($participant)->post(route('learning.enrol', $course))->assertRedirect(route('learning.course.show', $course));
        $this->assertFalse(Enrolment::where('course_id', $course->id)->where('user_id', $participant->id)->exists());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningCatalogueHidesCompulsoryTest extends TestCase
{
    use RefreshDatabase;

    private function courses(): array
    {
        $main = Course::create(['title' => 'Tailoring Basics', 'code' => 'TB1', 'status' => 'published']);
        $compulsory = Course::create(['title' => 'Life Skills Orientation', 'code' => 'LS1', 'status' => 'published']);
        $main->compulsoryCourses()->attach($compulsory->id);

        return [$main, $compulsory];
    }

    public function test_learning_page_does_not_list_compulsory_courses(): void
    {
        $this->courses();

        $this->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Tailoring Basics')
            ->assertDontSee('Life Skills Orientation');
    }

    public function test_api_course_list_does_not_include_compulsory_courses(): void
    {
        [$main, $compulsory] = $this->courses();

        $ids = collect($this->getJson('/api/v1/courses')->assertOk()->json('data.data'))->pluck('id');

        $this->assertTrue($ids->contains($main->id));
        $this->assertFalse($ids->contains($compulsory->id));
    }

    public function test_enrolled_participants_can_still_open_a_compulsory_course(): void
    {
        [, $compulsory] = $this->courses();

        $this->get(route('learning.course.show', $compulsory))->assertOk()->assertSee('Life Skills Orientation');
    }
}

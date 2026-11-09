<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseImageServingTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_course_image_is_served_without_the_storage_symlink(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('cover.png', 40, 20)->store('course-thumbnails', 'public');
        $course = Course::create(['title' => 'Tailoring', 'code' => 'T1', 'status' => 'published', 'thumbnail_path' => $path]);

        $url = $course->thumbnail_url;
        $this->assertSame(route('course.image', ['path' => $path]), $url);
        $this->assertStringContainsString('/course-images/course-thumbnails/', $url);

        $this->get(route('learning.index'))->assertSee($url, false);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_missing_or_unexpected_files_are_not_served(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('course-thumbnails/notes.txt', 'secret');

        $this->get('/course-images/course-thumbnails/missing.png')->assertNotFound();
        $this->get('/course-images/course-thumbnails/notes.txt')->assertNotFound();
        $this->get('/course-images/cms/other.png')->assertNotFound();
    }

    public function test_course_without_image_uses_the_placeholder(): void
    {
        $course = Course::create(['title' => 'Tailoring', 'code' => 'T1', 'status' => 'published']);

        $this->assertStringEndsWith('images/course-placeholder.svg', $course->thumbnail_url);
    }
}

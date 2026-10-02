<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnrolmentIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
    }

    private function context(): array
    {
        $programme = Programme::create(['name' => 'Digital Empowerment', 'code' => 'DE']);
        $branch = Branch::create(['name' => 'Kampala', 'code' => 'KLA']);
        $cohort = Cohort::create(['name' => 'Cohort 1', 'code' => '1', 'programme_id' => $programme->id, 'branch_id' => $branch->id]);
        $course = Course::create(['title' => 'Digital Skills', 'programme_id' => $programme->id, 'branch_id' => $branch->id]);

        return [$course, $cohort];
    }

    public function test_enrollment_codes_increment_are_permanent_and_never_reuse_deleted_numbers(): void
    {
        [$course, $cohort] = $this->context();
        $user = $this->participant();
        $attributes = ['course_id' => $course->id, 'cohort_id' => $cohort->id, 'enrolled_at' => '2026-10-02'];
        $first = Enrolment::create($attributes + ['user_id' => $user->id]);
        $this->assertSame('DE/KLA/1/26/001', $first->enrolment_code);
        $this->assertSame($first->enrolment_code, $user->fresh()->participant_code);
        $first->update(['status' => 'completed', 'cohort_id' => null]);
        $this->assertSame('DE/KLA/1/26/001', $first->fresh()->enrolment_code);
        $second = Enrolment::create($attributes + ['user_id' => $this->participant()->id]);
        $this->assertSame('DE/KLA/1/26/002', $second->enrolment_code);
        $second->delete();
        $third = Enrolment::create($attributes + ['user_id' => $this->participant()->id]);
        $this->assertSame('DE/KLA/1/26/003', $third->enrolment_code);
        $again = Enrolment::firstOrCreate(['course_id' => $course->id, 'user_id' => $user->id], $attributes);
        $this->assertSame($first->enrolment_code, $again->enrolment_code);
    }

    public function test_sequence_scope_and_returning_participant_identity(): void
    {
        [$course, $cohort] = $this->context();
        $user = $this->participant();
        $first = Enrolment::create(['course_id' => $course->id, 'cohort_id' => $cohort->id, 'user_id' => $user->id, 'enrolled_at' => '2026-01-01']);
        $project = Project::create(['name' => 'Project', 'code' => 'TECH', 'programme_id' => $course->programme_id]);
        $next = Course::create(['title' => 'Next', 'project_id' => $project->id, 'branch_id' => $course->branch_id]);
        $second = Enrolment::create(['course_id' => $next->id, 'user_id' => $user->id, 'enrolled_at' => '2027-01-01']);
        $this->assertSame('TECH/KLA/0/27/001', $second->enrolment_code);
        $this->assertSame($first->enrolment_code, $user->fresh()->participant_code);
    }

    public function test_migration_backfills_existing_enrollments_in_order(): void
    {
        [$course, $cohort] = $this->context();
        $user = $this->participant();
        $first = Enrolment::create(['course_id' => $course->id, 'cohort_id' => $cohort->id, 'user_id' => $user->id, 'enrolled_at' => '2026-01-01']);
        Schema::table('enrolments', function ($table) { $table->dropUnique(['enrolment_code']); $table->dropColumn('enrolment_code'); });
        Schema::drop('enrolment_identity_sequences');
        DB::table('users')->where('id', $user->id)->update(['participant_code' => 'EH26-000001']);
        (require database_path('migrations/2026_10_02_110000_add_enrolment_identity_codes.php'))->up();
        $this->assertSame('DE/KLA/1/26/001', $first->fresh()->enrolment_code);
        $this->assertSame('DE/KLA/1/26/001', $user->fresh()->participant_code);
        $next = Enrolment::create(['course_id' => $course->id, 'cohort_id' => $cohort->id, 'user_id' => $this->participant()->id, 'enrolled_at' => '2026-01-01']);
        $this->assertSame('DE/KLA/1/26/002', $next->enrolment_code);
    }
}

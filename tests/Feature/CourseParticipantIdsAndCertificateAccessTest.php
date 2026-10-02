<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Certificate;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\EventCertificate;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseParticipantIdsAndCertificateAccessTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        $user = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        Sanctum::actingAs($user, ['participant']);
        return $user;
    }

    private function context(): array
    {
        $program = Programme::create(['name'=>'Program','code'=>'PRG']);
        $branch = Branch::create(['name'=>'Kampala','code'=>'KLA']);
        $cohort = Cohort::create(['name'=>'Kampala cohort','code'=>'KLA-C1-2026','programme_id'=>$program->id,'branch_id'=>$branch->id]);
        $course = Course::create(['title'=>'Digital Marketing','code'=>'DM','programme_id'=>$program->id,'branch_id'=>$branch->id,'status'=>'published']);
        return [$course,$cohort];
    }

    private function certificate(User $user, Course $course): Certificate
    {
        return Certificate::create(['course_id'=>$course->id,'user_id'=>$user->id,'certificate_number'=>'COURSE-'.$user->id,'issued_on'=>now(),'verification_token'=>'verify-'.$user->id]);
    }

    public function test_id_segment_is_the_course_number_not_the_cohort_label(): void
    {
        [$course,$cohort] = $this->context();
        $user = $this->participant();
        $enrolment = Enrolment::create(['course_id'=>$course->id,'cohort_id'=>$cohort->id,'user_id'=>$user->id,'enrolled_at'=>'2026-10-02']);
        $this->assertSame('PRG/KLA/C1/26/001', $enrolment->enrolment_code);
        $other = Course::create(['title'=>'Design','programme_id'=>$course->programme_id,'branch_id'=>$course->branch_id,'status'=>'published']);
        $second = Enrolment::create(['course_id'=>$other->id,'cohort_id'=>$cohort->id,'user_id'=>$user->id,'enrolled_at'=>'2026-10-02']);
        $this->assertSame('PRG/KLA/C2/26/001', $second->enrolment_code);
        $this->getJson('/api/v1/participant/courses/'.$other->id)->assertOk()->assertJsonPath('course.enrolment.participant_code','PRG/KLA/C2/26/001');
        $this->assertSame('PRG/KLA/C1/26/001', $user->fresh()->participant_code);
    }

    public function test_migration_fixes_the_screenshot_value_preserves_numbers_and_invalidates_generated_pdfs(): void
    {
        Storage::fake('local');
        [$course,$cohort] = $this->context();
        $user = $this->participant();
        $enrolment = Enrolment::create(['course_id'=>$course->id,'cohort_id'=>$cohort->id,'user_id'=>$user->id,'enrolled_at'=>'2026-10-02']);
        $old = 'PRG/KLA/CKLA-C1-2026/26/001';
        DB::table('enrolments')->where('id',$enrolment->id)->update(['enrolment_code'=>$old]);
        DB::table('users')->where('id',$user->id)->update(['participant_code'=>$old]);
        DB::table('enrolment_identity_sequences')->insert(['prefix'=>'PRG/KLA/CKLA-C1-2026/26','last_number'=>40]);
        $certificate = $this->certificate($user, $course);
        $certificate->update(['pdf_path'=>'certificates/'.$certificate->certificate_number.'.pdf']);
        (require database_path('migrations/2026_10_02_200000_use_course_in_participant_ids.php'))->up();
        $this->assertSame('PRG/KLA/C1/26/001', $enrolment->fresh()->enrolment_code);
        $this->assertSame('PRG/KLA/C1/26/001', $user->fresh()->participant_code);
        $this->assertDatabaseHas('participant_id_aliases', ['alias'=>$old,'user_id'=>$user->id]);
        $this->assertNull($certificate->fresh()->pdf_path);
        $newUser = $this->participant();
        $next = Enrolment::create(['course_id'=>$course->id,'cohort_id'=>$cohort->id,'user_id'=>$newUser->id,'enrolled_at'=>'2026-10-02']);
        $this->assertSame('PRG/KLA/C1/26/041', $next->enrolment_code);
    }

    public function test_migration_merges_cohort_sequences_without_duplicate_course_ids(): void
    {
        [$course,$cohort] = $this->context();
        $a = $this->participant(); $b = $this->participant();
        $rows = [];
        foreach ([$a,$b] as $i=>$user) {
            $row = Enrolment::create(['course_id'=>$course->id,'user_id'=>$user->id,'enrolled_at'=>'2026-10-02']);
            $old = 'PRG/KLA/OLD-COHORT-'.($i+1).'/26/001';
            DB::table('enrolments')->where('id',$row->id)->update(['enrolment_code'=>$old]);
            DB::table('users')->where('id',$user->id)->update(['participant_code'=>$old]);
            DB::table('enrolment_identity_sequences')->insert(['prefix'=>substr($old,0,-4),'last_number'=>5]);
            $rows[] = $row;
        }
        (require database_path('migrations/2026_10_02_200000_use_course_in_participant_ids.php'))->up();
        $this->assertNotSame($rows[0]->fresh()->enrolment_code, $rows[1]->fresh()->enrolment_code);
        $this->assertStringStartsWith('PRG/KLA/C1/26/', $rows[0]->fresh()->enrolment_code);
        $this->assertStringStartsWith('PRG/KLA/C1/26/', $rows[1]->fresh()->enrolment_code);
    }

    public function test_participant_can_list_preview_download_and_share_both_certificate_types(): void
    {
        Storage::fake('local');
        [$course,$cohort] = $this->context();
        $user = $this->participant();
        Enrolment::create(['course_id'=>$course->id,'cohort_id'=>$cohort->id,'user_id'=>$user->id]);
        $certificate = $this->certificate($user, $course);
        $event = Event::create(['title'=>'Workshop','event_type'=>'workshop','starts_at'=>now(),'course_id'=>$course->id,'is_published'=>true]);
        $eventCertificate = EventCertificate::create(['event_id'=>$event->id,'user_id'=>$user->id,'certificate_code'=>'EVENT-1','issued_at'=>now()]);
        $this->getJson('/api/v1/participant/certificates')->assertOk()->assertJsonCount(2,'data');
        foreach (['course'=>$certificate,'event'=>$eventCertificate] as $type=>$record) {
            $base = '/api/v1/participant/certificates/'.$type.'/'.$record->id;
            $preview = $this->get($base.'/preview')->assertOk()->assertHeader('Content-Type','application/pdf');
            $this->assertStringStartsWith('inline', $preview->headers->get('Content-Disposition'));
            $this->get($base.'/download')->assertOk()->assertDownload('certificate-'.($type === 'course' ? $certificate->certificate_number : $eventCertificate->certificate_code).'.pdf');
            $url = $this->postJson($base.'/share')->assertOk()->json('url');
            $this->get($url)->assertOk();
            $this->get($url.'&tampered=1')->assertForbidden();
            $this->travel(8)->days();
            $this->get($url)->assertForbidden();
            $this->travelBack();
        }
        $this->actingAs($user)->get(route('certificates.mine'))->assertOk()->assertSee('Preview')->assertSee('Share');
    }

    public function test_certificate_access_is_restricted_to_the_owner_without_issuing_new_certificates(): void
    {
        [$course,$cohort] = $this->context();
        $owner = $this->participant();
        $certificate = $this->certificate($owner, $course);
        $user = $this->participant();
        Enrolment::create(['course_id'=>$course->id,'user_id'=>$user->id,'progress_percent'=>100,'status'=>'completed']);
        $this->getJson('/api/v1/participant/certificates')->assertOk()->assertJsonCount(0,'data');
        $base = '/api/v1/participant/certificates/course/'.$certificate->id;
        $this->get($base.'/preview')->assertForbidden();
        $this->get($base.'/download')->assertForbidden();
        $this->postJson($base.'/share')->assertForbidden();
        $this->assertDatabaseCount('certificates', 1);
    }
}

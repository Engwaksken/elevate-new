<?php

use App\Http\Controllers\Admin\ProgrammeManagement\ActivityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Elearning\LessonController as AdminElearningLessonController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Employer\ApplicantController;
use App\Http\Controllers\Admin\HR\AppraisalController;
use App\Http\Controllers\HR\AppraisalExportController;
use App\Http\Controllers\Admin\HR\AppraisalKpiController;
use App\Http\Controllers\Admin\HR\AppraisalWorkflowController;
use App\Http\Controllers\Admin\Elearning\AssessmentBuilderController;
use App\Http\Controllers\Learning\AssessmentController;
use App\Http\Controllers\Admin\Assets\AssetAssignmentController;
use App\Http\Controllers\Admin\Assets\AssetController;
use App\Http\Controllers\Admin\Assets\AssetDisposalController;
use App\Http\Controllers\Admin\Assets\AssetMaintenanceController;
use App\Http\Controllers\Instructor\AttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\Elearning\BulkEnrolmentController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Admin\CareerAiController;
use App\Http\Controllers\Admin\Elearning\CertificateAdminController;
use App\Http\Controllers\Learning\CertificateController;
use App\Http\Controllers\Admin\Elearning\CertificateIndexController;
use App\Http\Controllers\Support\ChatbotController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\HR\ContractController;
use App\Http\Controllers\Admin\Elearning\CourseAssignmentController;
use App\Http\Controllers\Admin\CourseAttendanceReportController;
use App\Http\Controllers\Participant\CourseCallApplicationController;
use App\Http\Controllers\Admin\CourseCallController;
use App\Http\Controllers\Admin\CourseCallDisplayController;
use App\Http\Controllers\Learning\CourseCatalogueController;
use App\Http\Controllers\Admin\Elearning\CourseController;
use App\Http\Controllers\Admin\Elearning\CourseModuleController;
use App\Http\Controllers\Career\CoverLetterController;
use App\Http\Controllers\Career\CoverLetterUploadController;
use App\Http\Controllers\Instructor\DailyAttendanceController;
use App\Http\Controllers\Admin\ProgrammeManagement\DeliverableController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\Jobs\EmployerAdminController;
use App\Http\Controllers\Employer\EmployerJobController;
use App\Http\Controllers\Employer\EmployerProfileController;
use App\Http\Controllers\Admin\Elearning\EnrolmentAdminController;
use App\Http\Controllers\Learning\EnrolmentController;
use App\Http\Controllers\EventCheckinController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\EventEngagementController;
use App\Http\Controllers\Admin\EventEvaluationController;
use App\Http\Controllers\Admin\EventOperationsController;
use App\Http\Controllers\EventPortalController;
use App\Http\Controllers\Admin\ExecutiveDashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\Procurement\GoodsReceiptController;
use App\Http\Controllers\Admin\Elearning\GradebookController;
use App\Http\Controllers\Admin\ME\IndicatorController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Employer\InterviewController;
use App\Http\Controllers\Admin\Jobs\JobAdminController;
use App\Http\Controllers\Jobs\JobApplicationController;
use App\Http\Controllers\Jobs\JobBrowseController;
use App\Http\Controllers\Jobs\JobRecommendationController;
use App\Http\Controllers\Admin\HR\KpiTemplateController;
use App\Http\Controllers\Admin\Elearning\LearningFileAdminController;
use App\Http\Controllers\Learning\LearningFileController;
use App\Http\Controllers\Learning\LessonController as LearningLessonController;
use App\Http\Controllers\Admin\HR\LeaveApprovalController;
use App\Http\Controllers\HR\LeaveRequestController;
use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\Admin\Library\LibraryResourceController;
use App\Http\Controllers\Admin\ME\MEDashboardController;
use App\Http\Controllers\Admin\Mentorship\MentorAdminController;
use App\Http\Controllers\Admin\Mentorship\MentorMatchController;
use App\Http\Controllers\Mentorship\MentorProfileController;
use App\Http\Controllers\Admin\Mentorship\MentorRecommendationController;
use App\Http\Controllers\Mentorship\MentorshipDashboardController;
use App\Http\Controllers\Mentorship\MentorshipGoalController;
use App\Http\Controllers\Mentorship\MentorshipSessionController;
use App\Http\Controllers\Admin\MigrationController;
use App\Http\Controllers\Admin\ProgrammeManagement\MilestoneController;
use App\Http\Controllers\Instructor\ModuleAccessController;
use App\Http\Controllers\Admin\NotificationAdminController;
use App\Http\Controllers\Participant\NotificationController;
use App\Http\Controllers\Employer\OfferController;
use App\Http\Controllers\Admin\Jobs\OutcomeAdminController;
use App\Http\Controllers\Auth\ParticipantAuthController;
use App\Http\Controllers\Participant\DashboardController as ParticipantDashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Admin\ProgrammeController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Public\PublicSurveyController;
use App\Http\Controllers\Admin\Procurement\PurchaseOrderController;
use App\Http\Controllers\Admin\Procurement\PurchaseRequestController;
use App\Http\Controllers\Admin\Procurement\QuotationController;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ME\ResultsFrameworkController;
use App\Http\Controllers\Career\ResumeAiController;
use App\Http\Controllers\Career\ResumeController;
use App\Http\Controllers\Career\ResumeSectionController;
use App\Http\Controllers\Career\ResumeUploadController;
use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Jobs\SavedJobController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\HR\StaffAppraisalController;
use App\Http\Controllers\Auth\StaffAuthController;
use App\Http\Controllers\Admin\HR\StaffExitController;
use App\Http\Controllers\Admin\Procurement\SupplierController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Participant\SurveyResponseController;
use App\Http\Controllers\Admin\ProgrammeManagement\TaskController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProgrammeManagement\WorkplanController;

// ElevateHer360 consolidated web routes.
// Former phased route files are merged into this single file.

Route::view('/', 'home')->name('home');

/* ===== merged from web.phase2.php ===== */
Route::post('/support/chatbot',[ChatbotController::class,'message'])
    ->middleware('throttle:30,1')
    ->name('support.chatbot.message');
// Participant authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [ParticipantAuthController::class, 'create'])->name('register');
    Route::post('/register', [ParticipantAuthController::class, 'store'])->name('register.store');
    Route::get('/login', [ParticipantAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ParticipantAuthController::class, 'login'])->name('login.attempt');

    Route::get('/admin/login', [StaffAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [StaffAuthController::class, 'login'])->name('admin.login.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'email'])
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'update'])
    ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [ParticipantAuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', fn () => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('success', 'Email verified.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ParticipantDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'staff'])
    ->group(function () {
        Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');

        Route::delete('/programmes/bulk-delete', [ProgrammeController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('programmes.bulk-destroy');

        Route::resource('programmes', ProgrammeController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::delete('/projects/bulk-delete', [ProjectController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('projects.bulk-destroy');

        Route::resource('projects', ProjectController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::delete('/branches/bulk-delete', [BranchController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('branches.bulk-destroy');

        Route::resource('branches', BranchController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::delete('/cohorts/bulk-delete', [CohortController::class, 'bulkDestroy'])->middleware('permission:cohorts.manage')->name('cohorts.bulk-destroy');

        Route::resource('cohorts', CohortController::class)->except('show')
            ->middleware('permission:cohorts.manage');

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.manage')->name('roles.index');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:roles.manage')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage')->name('roles.update');
    });

/* ===== merged from web.phase3.php ===== */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth','staff'])
    ->group(function () {
        Route::get('/dashboard',[AdminDashboardController::class,'index'])->name('dashboard');

        Route::resource('users',UserController::class)->except(['show','destroy'])
            ->middleware('permission:users.edit');

        Route::get('/migrations',[MigrationController::class,'index'])
            ->middleware('permission:settings.manage')->name('migrations.index');
        Route::get('/migrations/create',[MigrationController::class,'create'])
            ->middleware('permission:settings.manage')->name('migrations.create');
        Route::post('/migrations',[MigrationController::class,'store'])
            ->middleware('permission:settings.manage')->name('migrations.store');
        Route::get('/migrations/{migration}',[MigrationController::class,'show'])
            ->middleware('permission:settings.manage')->name('migrations.show');

        Route::prefix('elearning')->name('elearning.')->group(function () {
            Route::delete('/courses/bulk-delete',[CourseController::class,'bulkDestroy'])
            ->middleware('permission:courses.delete')->name('courses.bulk-destroy');

        Route::resource('courses',CourseController::class)->except(['show'])
                ->middleware('permission:courses.edit');

            Route::post('/courses/{course}/modules',[CourseModuleController::class,'store'])
                ->middleware('permission:courses.edit')->name('modules.store');
            Route::put('/courses/{course}/modules/{module}',[CourseModuleController::class,'update'])
                ->middleware('permission:courses.edit')->name('modules.update');
            Route::delete('/courses/{course}/modules/{module}',[CourseModuleController::class,'destroy'])
                ->middleware('permission:courses.delete')->name('modules.destroy');

            Route::post('/modules/{module}/lessons',[AdminElearningLessonController::class,'store'])
                ->middleware('permission:courses.edit')->name('lessons.store');
            Route::put('/modules/{module}/lessons/{lesson}',[AdminElearningLessonController::class,'update'])
                ->middleware('permission:courses.edit')->name('lessons.update');
            Route::delete('/modules/{module}/lessons/{lesson}',[AdminElearningLessonController::class,'destroy'])
                ->middleware('permission:courses.delete')->name('lessons.destroy');
        });
    });

/* ===== merged from web.phase4.php ===== */
/*
|--------------------------------------------------------------------------
| Public Learning Routes
|--------------------------------------------------------------------------
*/

Route::get(
    '/learning',
    [CourseCatalogueController::class, 'index']
)->name('learning.index');

Route::get(
    '/learning/courses/{course}',
    [CourseCatalogueController::class, 'show']
)->name('learning.course.show');

Route::get(
    '/certificates/verify/{token}',
    [CertificateController::class, 'verify']
)->name('certificates.verify');


/*
|--------------------------------------------------------------------------
| Authenticated Participant Learning Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::post(
        '/learning/courses/{course}/enrol',
        [EnrolmentController::class, 'store']
    )->name('learning.enrol');

    Route::get(
        '/learning/my-courses',
        [EnrolmentController::class, 'myCourses']
    )->name('learning.my-courses');

    Route::get(
        '/learning/lessons/{lesson}',
        [LearningLessonController::class, 'show']
    )->name('learning.lesson.show');

    Route::post(
        '/learning/lessons/{lesson}/complete',
        [LearningLessonController::class, 'complete']
    )->name('learning.lesson.complete');

    Route::get(
        '/learning/assessments/{assessment}',
        [AssessmentController::class, 'show']
    )->name('learning.assessment.show');

    Route::post(
        '/learning/assessments/{assessment}',
        [AssessmentController::class, 'submit']
    )->name('learning.assessment.submit');
});


/*
|--------------------------------------------------------------------------
| Admin eLearning Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/elearning')
    ->name('admin.elearning.')
    ->middleware(['auth', 'staff'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Course Files
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/courses/{course}/files',
            [LearningFileAdminController::class, 'index']
        )
            ->middleware('permission:courses.edit')
            ->name('files.index');


        /*
        |--------------------------------------------------------------------------
        | Course Assignments
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/courses/{course}/assignments',
            [CourseAssignmentController::class, 'edit']
        )
            ->middleware('permission:courses.edit')
            ->name('assignments.edit');

        Route::put(
            '/courses/{course}/assignments',
            [CourseAssignmentController::class, 'update']
        )
            ->middleware('permission:courses.edit')
            ->name('assignments.update');


        /*
        |--------------------------------------------------------------------------
        | Course Enrolments
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/enrolments',
            [EnrolmentAdminController::class, 'index']
        )
            ->middleware('permission:students.view')
            ->name('enrolments.index');

        Route::post(
            '/enrolments',
            [EnrolmentAdminController::class, 'store']
        )
            ->middleware('permission:students.edit')
            ->name('enrolments.store');

        Route::put(
            '/enrolments/{enrolment}',
            [EnrolmentAdminController::class, 'update']
        )
            ->middleware('permission:students.edit')
            ->name('enrolments.update');

        Route::delete(
            '/enrolments/{enrolment}',
            [EnrolmentAdminController::class, 'destroy']
        )
            ->middleware('permission:students.edit')
            ->name('enrolments.destroy');
    });


/*
|--------------------------------------------------------------------------
| Instructor Routes
|--------------------------------------------------------------------------
*/

Route::prefix('instructor')
    ->name('instructor.')
    ->middleware(['auth', 'staff'])
    ->group(function () {

        Route::get(
            '/dashboard',
            [InstructorDashboardController::class, 'index']
        )->name('dashboard');

        Route::get(
            '/courses/{course}/attendance',
            [AttendanceController::class, 'create']
        )->name('attendance.create');

        Route::post(
            '/courses/{course}/attendance',
            [AttendanceController::class, 'store']
        )->name('attendance.store');
    });

/* ===== merged from web.phase5.php ===== */
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/learning/files/{file}/download',[LearningFileController::class,'download'])
        ->name('learning.files.download');

    Route::get('/mentorship',[MentorshipDashboardController::class,'index'])->name('mentorship.dashboard');
    Route::get('/mentorship/mentor-profile',[MentorProfileController::class,'edit'])->name('mentorship.mentor-profile.edit');
    Route::put('/mentorship/mentor-profile',[MentorProfileController::class,'update'])->name('mentorship.mentor-profile.update');
    Route::post('/mentorship/matches/{match}/sessions',[MentorshipSessionController::class,'store'])->name('mentorship.sessions.store');
    Route::put('/mentorship/sessions/{session}/complete',[MentorshipSessionController::class,'complete'])->name('mentorship.sessions.complete');
});

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth','staff'])->group(function () {
    Route::get('/courses/{course}/assessments',[AssessmentBuilderController::class,'index'])
        ->middleware('permission:courses.edit')->name('assessments.index');
    Route::post('/courses/{course}/assessments',[AssessmentBuilderController::class,'store'])
        ->middleware('permission:courses.edit')->name('assessments.store');
    Route::get('/courses/{course}/assessments/{assessment}',[AssessmentBuilderController::class,'edit'])
        ->middleware('permission:courses.edit')->name('assessments.edit');
    Route::post('/courses/{course}/assessments/{assessment}/questions',[AssessmentBuilderController::class,'addQuestion'])
        ->middleware('permission:courses.edit')->name('questions.store');
    Route::delete('/courses/{course}/assessments/{assessment}/questions/{question}',[AssessmentBuilderController::class,'destroyQuestion'])
        ->middleware('permission:courses.delete')->name('questions.destroy');

    Route::get('/courses/{course}/gradebook',[GradebookController::class,'index'])
        ->middleware('permission:courses.edit')->name('gradebook.index');
    Route::get('/gradebook/{attempt}',[GradebookController::class,'edit'])
        ->middleware('permission:courses.edit')->name('gradebook.edit');
    Route::put('/gradebook/{attempt}',[GradebookController::class,'update'])
        ->middleware('permission:courses.edit')->name('gradebook.update');

    Route::get('/bulk-enrolment',[BulkEnrolmentController::class,'create'])
        ->middleware('permission:students.edit')->name('bulk-enrolment.create');
    Route::post('/bulk-enrolment',[BulkEnrolmentController::class,'store'])
        ->middleware('permission:students.edit')->name('bulk-enrolment.store');

    Route::post('/courses/{course}/files',[LearningFileAdminController::class,'store'])
        ->middleware('permission:courses.edit')->name('files.store');

    Route::post('/certificates/{certificate}/generate',[CertificateAdminController::class,'generate'])
        ->middleware('permission:courses.edit')->name('certificates.generate');
});

Route::prefix('admin/mentorship')->name('admin.mentorship.')->middleware(['auth','staff'])->group(function () {
    Route::get('/mentors',[MentorAdminController::class,'index'])
        ->middleware('permission:mentors.manage')->name('mentors.index');
    Route::post('/mentors/{mentor}/approve',[MentorAdminController::class,'approve'])
        ->middleware('permission:mentors.manage')->name('mentors.approve');
    Route::post('/mentors/{mentor}/reject',[MentorAdminController::class,'reject'])
        ->middleware('permission:mentors.manage')->name('mentors.reject');

    Route::get('/matches',[MentorMatchController::class,'index'])
        ->middleware('permission:mentorship.match')->name('matches.index');
    Route::post('/matches',[MentorMatchController::class,'store'])
        ->middleware('permission:mentorship.match')->name('matches.store');
});

/* ===== merged from web.phase6.php ===== */
Route::get('/jobs',[JobBrowseController::class,'index'])->name('jobs.index');
Route::get('/jobs/{job}',[JobBrowseController::class,'show'])->name('jobs.show');

Route::middleware(['auth','verified'])->group(function () {
    Route::post('/jobs/{job}/apply',[JobApplicationController::class,'store'])->name('jobs.apply');
    Route::get('/my-job-applications',[JobApplicationController::class,'index'])->name('jobs.applications');
    Route::post('/job-applications/{application}/withdraw',[JobApplicationController::class,'withdraw'])->name('jobs.withdraw');

    Route::post('/mentorship/matches/{match}/goals',[MentorshipGoalController::class,'store'])->name('mentorship.goals.store');
    Route::put('/mentorship/goals/{goal}',[MentorshipGoalController::class,'update'])->name('mentorship.goals.update');

    Route::get('/employer/profile',[EmployerProfileController::class,'edit'])->name('employer.profile.edit');
    Route::put('/employer/profile',[EmployerProfileController::class,'update'])->name('employer.profile.update');
    Route::get('/employer/jobs',[EmployerJobController::class,'index'])->name('employer.jobs.index');
    Route::post('/employer/jobs',[EmployerJobController::class,'store'])->name('employer.jobs.store');
    Route::get('/employer/applicants',[ApplicantController::class,'index'])->name('employer.applicants.index');
    Route::put('/employer/applicants/{application}/status',[ApplicantController::class,'status'])->name('employer.applicants.status');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/mentorship/mentees/{mentee}/recommendations',[MentorRecommendationController::class,'show'])
        ->middleware('permission:mentorship.match')->name('mentorship.recommendations');

    Route::get('/jobs/employers',[EmployerAdminController::class,'index'])
        ->middleware('permission:employers.approve')->name('jobs.employers.index');
    Route::post('/jobs/employers/{employer}/approve',[EmployerAdminController::class,'approve'])
        ->middleware('permission:employers.approve')->name('jobs.employers.approve');
    Route::post('/jobs/employers/{employer}/reject',[EmployerAdminController::class,'reject'])
        ->middleware('permission:employers.approve')->name('jobs.employers.reject');

    Route::get('/jobs',[JobAdminController::class,'index'])
        ->middleware('permission:jobs.manage')->name('jobs.index');
    Route::post('/jobs',[JobAdminController::class,'store'])
        ->middleware('permission:jobs.manage')->name('jobs.store');
    Route::post('/jobs/import',[JobAdminController::class,'import'])
        ->middleware('permission:jobs.manage')->name('jobs.import');
    Route::put('/jobs/{job}',[JobAdminController::class,'update'])
        ->middleware('permission:jobs.manage')->name('jobs.update');
    Route::delete('/jobs/{job}',[JobAdminController::class,'destroy'])
        ->middleware('permission:jobs.manage')->name('jobs.destroy');
    Route::post('/jobs/{job}/publish',[JobAdminController::class,'publish'])
        ->middleware('permission:jobs.manage')->name('jobs.publish');
    Route::post('/jobs/{job}/reject',[JobAdminController::class,'reject'])
        ->middleware('permission:jobs.manage')->name('jobs.reject');

    Route::get('/outcomes',[OutcomeAdminController::class,'index'])
        ->middleware('permission:meal.view')->name('jobs.outcomes.index');
    Route::post('/outcomes/{outcome}/verify',[OutcomeAdminController::class,'verify'])
        ->middleware('permission:meal.manage')->name('jobs.outcomes.verify');
    Route::post('/outcomes/{outcome}/reject',[OutcomeAdminController::class,'reject'])
        ->middleware('permission:meal.manage')->name('jobs.outcomes.reject');
});

/* ===== merged from web.phase7.php ===== */
Route::get('/library',[LibraryController::class,'index'])->name('library.index');
Route::get('/library/{resource}',[LibraryController::class,'show'])->name('library.show');
Route::get('/library/{resource}/cover',[LibraryController::class,'cover'])->name('library.cover');
Route::get('/library/{resource}/download',[LibraryController::class,'download'])->name('library.download');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/career/resumes',[ResumeController::class,'index'])->name('career.resume.index');
    Route::get('/career/resumes/create',[ResumeController::class,'create'])->name('career.resume.create');
    Route::post('/career/resumes',[ResumeController::class,'store'])->name('career.resume.store');
    Route::get('/career/resumes/{resume}/edit',[ResumeController::class,'edit'])->name('career.resume.edit');
    Route::put('/career/resumes/{resume}',[ResumeController::class,'update'])->name('career.resume.update');
    Route::put('/career/resumes/{resume}/template',[ResumeController::class,'updateTemplate'])->name('career.resume.template');
    Route::post('/career/resumes/{resume}/default',[ResumeController::class,'makeDefault'])->name('career.resume.default');
    Route::delete('/career/resumes/{resume}',[ResumeController::class,'destroy'])->name('career.resume.destroy');
    Route::get('/career/resumes/{resume}/download',[ResumeController::class,'download'])->name('career.resume.download');

    Route::post('/career/resumes/upload',[ResumeUploadController::class,'store'])->name('career.resume.upload.store');
    Route::get('/career/resume-uploads/{upload}',[ResumeUploadController::class,'review'])->name('career.resume.upload.review');
    Route::get('/career/resume-uploads/{upload}/status',[ResumeUploadController::class,'status'])->name('career.resume.upload.status');
    Route::post('/career/resume-uploads/{upload}/import',[ResumeUploadController::class,'import'])->name('career.resume.upload.import');
    Route::get('/career/resume-uploads/{upload}/original',[ResumeUploadController::class,'original'])->name('career.resume.upload.original');
    Route::post('/career/resume-uploads/{upload}/replace',[ResumeUploadController::class,'replace'])->name('career.resume.upload.replace');
    Route::post('/career/resume-uploads/{upload}/retry',[ResumeUploadController::class,'retry'])->name('career.resume.upload.retry');
    Route::delete('/career/resume-uploads/{upload}',[ResumeUploadController::class,'destroy'])->name('career.resume.upload.destroy');

    Route::post('/career/resumes/{resume}/ai/improve',[ResumeAiController::class,'improve'])->name('career.resume.ai.improve');
    Route::post('/career/resumes/{resume}/ai/ats',[ResumeAiController::class,'ats'])->name('career.resume.ai.ats');
    Route::post('/career/resumes/{resume}/ai/tailor',[ResumeAiController::class,'tailor'])->name('career.resume.ai.tailor');

    Route::post('/career/cover-letters',[CoverLetterController::class,'store'])->name('career.cover-letter.store');
    Route::post('/career/cover-letters/generate',[CoverLetterController::class,'generate'])->name('career.cover-letter.generate');

    Route::post('/career/cover-letters/upload',[CoverLetterUploadController::class,'store'])->name('career.cover-letter.upload.store');
    Route::get('/career/cover-letter-uploads/{upload}',[CoverLetterUploadController::class,'review'])->name('career.cover-letter.upload.review');
    Route::get('/career/cover-letter-uploads/{upload}/status',[CoverLetterUploadController::class,'status'])->name('career.cover-letter.upload.status');
    Route::post('/career/cover-letter-uploads/{upload}/import',[CoverLetterUploadController::class,'import'])->name('career.cover-letter.upload.import');
    Route::get('/career/cover-letter-uploads/{upload}/original',[CoverLetterUploadController::class,'original'])->name('career.cover-letter.upload.original');
    Route::post('/career/cover-letter-uploads/{upload}/replace',[CoverLetterUploadController::class,'replace'])->name('career.cover-letter.upload.replace');
    Route::post('/career/cover-letter-uploads/{upload}/retry',[CoverLetterUploadController::class,'retry'])->name('career.cover-letter.upload.retry');
    Route::delete('/career/cover-letter-uploads/{upload}',[CoverLetterUploadController::class,'destroy'])->name('career.cover-letter.upload.destroy');

    Route::post('/career/resumes/{resume}/experience',[ResumeSectionController::class,'addExperience'])->name('career.resume.experience.store');
    Route::post('/career/resumes/{resume}/education',[ResumeSectionController::class,'addEducation'])->name('career.resume.education.store');
    Route::post('/career/resumes/{resume}/skills',[ResumeSectionController::class,'addSkill'])->name('career.resume.skill.store');

    Route::get('/jobs/saved',[SavedJobController::class,'index'])->name('jobs.saved');
    Route::post('/jobs/{job}/save',[SavedJobController::class,'store'])->name('jobs.save');
    Route::delete('/jobs/{job}/save',[SavedJobController::class,'destroy'])->name('jobs.unsave');
    Route::get('/jobs/recommendations',[JobRecommendationController::class,'index'])->name('jobs.recommendations');

    Route::post('/employer/applications/{application}/interviews',[InterviewController::class,'store'])->name('employer.interviews.store');
    Route::post('/employer/applications/{application}/offers',[OfferController::class,'store'])->name('employer.offers.store');
    Route::post('/library/{resource}/bookmark',[LibraryController::class,'bookmark'])->name('library.bookmark');
});

Route::prefix('admin/library')->name('admin.library.')->middleware(['auth','staff','permission:library.manage'])->group(function () {
    Route::get('/',[LibraryResourceController::class,'index'])->name('index');
    Route::post('/',[LibraryResourceController::class,'store'])->name('store');
    Route::put('/{resource}',[LibraryResourceController::class,'update'])->name('update');
    Route::delete('/{resource}',[LibraryResourceController::class,'destroy'])->name('destroy');
});

Route::prefix('admin/career-ai')->name('admin.career-ai.')->middleware(['auth','staff'])->group(function () {
    Route::get('/',[CareerAiController::class,'index'])->name('index');
    Route::put('/',[CareerAiController::class,'update'])->name('update');
});

/* ===== merged from web.phase8.php ===== */
Route::middleware(['auth'])->group(function () {
    Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/workplans',[WorkplanController::class,'index'])->middleware('permission:workplans.view')->name('workplans.index');
    Route::post('/workplans',[WorkplanController::class,'store'])->middleware('permission:workplans.create')->name('workplans.store');
    Route::post('/workplans/{workplan}/submit',[WorkplanController::class,'submit'])->middleware('permission:workplans.edit')->name('workplans.submit');
    Route::post('/workplans/{workplan}/approve',[WorkplanController::class,'approve'])->middleware('permission:workplans.approve')->name('workplans.approve');

    Route::post('/workplans/{workplan}/milestones',[MilestoneController::class,'store'])->middleware('permission:milestones.manage')->name('milestones.store');
    Route::put('/milestones/{milestone}',[MilestoneController::class,'update'])->middleware('permission:milestones.manage')->name('milestones.update');

    Route::post('/workplans/{workplan}/activities',[ActivityController::class,'store'])->middleware('permission:activities.manage')->name('activities.store');
    Route::put('/activities/{activity}/progress',[ActivityController::class,'updateProgress'])->middleware('permission:activities.manage')->name('activities.progress');

    Route::get('/indicators',[IndicatorController::class,'index'])->middleware('permission:indicators.view')->name('indicators.index');
    Route::post('/indicators',[IndicatorController::class,'store'])->middleware('permission:indicators.manage')->name('indicators.store');
    Route::post('/indicators/{indicator}/targets',[IndicatorController::class,'addTarget'])->middleware('permission:indicators.manage')->name('indicators.targets.store');
    Route::post('/indicators/{indicator}/calculate',[IndicatorController::class,'calculate'])->middleware('permission:indicators.manage')->name('indicators.calculate');
    Route::post('/indicator-results/{result}/verify',[IndicatorController::class,'verify'])->middleware('permission:indicators.verify')->name('indicator-results.verify');

    Route::get('/results-framework',[ResultsFrameworkController::class,'index'])->middleware('permission:meal.view')->name('results-framework.index');
    Route::post('/results-framework',[ResultsFrameworkController::class,'store'])->middleware('permission:meal.manage')->name('results-framework.store');
    Route::post('/results-framework/{framework}/results',[ResultsFrameworkController::class,'addResult'])->middleware('permission:meal.manage')->name('results-framework.results.store');

    Route::get('/meal',[MEDashboardController::class,'index'])->middleware('permission:meal.view')->name('meal.dashboard');
});

/* ===== merged from web.phase9.php ===== */
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/hr/leave',[LeaveRequestController::class,'index'])->name('hr.leave.index');
    Route::post('/hr/leave',[LeaveRequestController::class,'store'])->name('hr.leave.store');

    Route::get('/staff/appraisals',[StaffAppraisalController::class,'index'])->name('staff.appraisals.index');
    Route::get('/staff/appraisals/team',[StaffAppraisalController::class,'team'])->name('staff.appraisals.team');
    Route::get('/staff/appraisals/{appraisal}',[StaffAppraisalController::class,'show'])->name('staff.appraisals.show');

    Route::put('/staff/appraisals/{appraisal}/self-assessment',[StaffAppraisalController::class,'saveSelf'])->name('staff.appraisals.self.save');
    Route::post('/staff/appraisals/{appraisal}/submit',[StaffAppraisalController::class,'submitSelf'])->name('staff.appraisals.self.submit');

    Route::put('/staff/appraisals/{appraisal}/manager-review',[StaffAppraisalController::class,'saveManager'])->name('staff.appraisals.manager.save');
    Route::post('/staff/appraisals/{appraisal}/manager-submit',[StaffAppraisalController::class,'submitManager'])->name('staff.appraisals.manager.submit');

    Route::post('/staff/appraisals/{appraisal}/acknowledge',[StaffAppraisalController::class,'acknowledge'])->name('staff.appraisals.acknowledge');

    Route::get('/staff/appraisals/{appraisal}/export/excel',[AppraisalExportController::class,'excel'])->name('staff.appraisals.export.excel');
    Route::get('/staff/appraisals/{appraisal}/print',[AppraisalExportController::class,'print'])->name('staff.appraisals.print');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/tasks',[TaskController::class,'index'])->middleware('permission:tasks.manage')->name('tasks.index');
    Route::post('/activities/{activity}/tasks',[TaskController::class,'store'])->middleware('permission:tasks.manage')->name('tasks.store');
    Route::put('/tasks/{task}',[TaskController::class,'update'])->middleware('permission:tasks.manage')->name('tasks.update');

    Route::get('/deliverables',[DeliverableController::class,'index'])->middleware('permission:tasks.manage')->name('deliverables.index');
    Route::post('/activities/{activity}/deliverables',[DeliverableController::class,'store'])->middleware('permission:tasks.manage')->name('deliverables.store');
    Route::put('/deliverables/{deliverable}',[DeliverableController::class,'update'])->middleware('permission:tasks.manage')->name('deliverables.update');

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/jobs',[JobAdminController::class,'index'])->middleware('permission:hr.view')->name('jobs.index');
        Route::post('/jobs',[JobAdminController::class,'store'])->middleware('permission:hr.manage')->name('jobs.store');
        Route::post('/jobs/import',[JobAdminController::class,'import'])->middleware('permission:hr.manage')->name('jobs.import');
        Route::put('/jobs/{job}',[JobAdminController::class,'update'])->middleware('permission:hr.manage')->name('jobs.update');
        Route::delete('/jobs/{job}',[JobAdminController::class,'destroy'])->middleware('permission:hr.manage')->name('jobs.destroy');
        Route::post('/jobs/{job}/publish',[JobAdminController::class,'publish'])->middleware('permission:hr.manage')->name('jobs.publish');
        Route::post('/jobs/{job}/reject',[JobAdminController::class,'reject'])->middleware('permission:hr.manage')->name('jobs.reject');

        Route::get('/employees',[EmployeeController::class,'index'])->middleware('permission:hr.view')->name('employees.index');
        Route::post('/employees',[EmployeeController::class,'store'])->middleware('permission:hr.manage')->name('employees.store');

        Route::post('/employees/{employee}/contracts',[ContractController::class,'store'])->middleware('permission:hr.manage')->name('contracts.store');

        Route::get('/leave',[LeaveApprovalController::class,'index'])->middleware('permission:leave.view')->name('leave.index');
        Route::post('/leave/{leave}/supervisor-approve',[LeaveApprovalController::class,'supervisorApprove'])->middleware('permission:leave.approve')->name('leave.supervisor-approve');
        Route::post('/leave/{leave}/hr-approve',[LeaveApprovalController::class,'hrApprove'])->middleware('permission:leave.approve')->name('leave.hr-approve');
        Route::post('/leave/{leave}/reject',[LeaveApprovalController::class,'reject'])->middleware('permission:leave.approve')->name('leave.reject');

        Route::get('/kpi-templates',[KpiTemplateController::class,'index'])->middleware('permission:appraisals.view')->name('kpi-templates.index');
        Route::post('/kpi-templates',[KpiTemplateController::class,'store'])->middleware('permission:appraisals.manage')->name('kpi-templates.store');
        Route::put('/kpi-templates/{template}',[KpiTemplateController::class,'update'])->middleware('permission:appraisals.manage')->name('kpi-templates.update');
        Route::delete('/kpi-templates/{template}',[KpiTemplateController::class,'destroy'])->middleware('permission:appraisals.manage')->name('kpi-templates.destroy');

        Route::get('/appraisals',[AppraisalController::class,'index'])->middleware('permission:appraisals.view')->name('appraisals.index');
        Route::get('/appraisals/{appraisal}/kpis',[AppraisalKpiController::class,'show'])->middleware('permission:appraisals.view')->name('appraisals.kpis');
        Route::post('/appraisals/{appraisal}/kpi-template',[AppraisalKpiController::class,'assignTemplate'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-template');
        Route::post('/appraisals/{appraisal}/kpi-score',[AppraisalKpiController::class,'score'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-score');
        Route::post('/appraisal-cycles',[AppraisalController::class,'createCycle'])->middleware('permission:appraisals.manage')->name('appraisal-cycles.store');
        Route::post('/appraisals/assign',[AppraisalController::class,'assign'])->middleware('permission:appraisals.manage')->name('appraisals.assign');
        Route::post('/appraisals/{appraisal}/objectives',[AppraisalController::class,'addObjective'])->middleware('permission:appraisals.manage')->name('appraisals.objectives.store');
        Route::post('/appraisals/{appraisal}/recalculate',[AppraisalController::class,'recalculate'])->middleware('permission:appraisals.manage')->name('appraisals.recalculate');
        Route::post('/appraisals/{appraisal}/finalise',[AppraisalWorkflowController::class,'finalise'])->middleware('permission:appraisals.manage')->name('appraisals.finalise');

        Route::get('/exits',[StaffExitController::class,'index'])->middleware('permission:staff_exit.manage')->name('exits.index');
        Route::post('/exits',[StaffExitController::class,'store'])->middleware('permission:staff_exit.manage')->name('exits.store');
        Route::post('/exits/{exit}/clear',[StaffExitController::class,'clear'])->middleware('permission:staff_exit.manage')->name('exits.clear');
        Route::post('/exits/{exit}/complete',[StaffExitController::class,'complete'])->middleware('permission:staff_exit.manage')->name('exits.complete');
    });
});

/* ===== merged from web.phase10.php ===== */
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/suppliers',[SupplierController::class,'index'])->middleware('permission:procurement.view')->name('suppliers.index');
        Route::post('/suppliers',[SupplierController::class,'store'])->middleware('permission:procurement.create')->name('suppliers.store');
        Route::post('/suppliers/{supplier}/approve',[SupplierController::class,'approve'])->middleware('permission:procurement.approve')->name('suppliers.approve');

        Route::get('/requests',[PurchaseRequestController::class,'index'])->middleware('permission:procurement.view')->name('requests.index');
        Route::post('/requests',[PurchaseRequestController::class,'store'])->middleware('permission:procurement.create')->name('requests.store');
        Route::post('/requests/{purchaseRequest}/submit',[PurchaseRequestController::class,'submit'])->middleware('permission:procurement.create')->name('requests.submit');
        Route::post('/requests/{purchaseRequest}/approve',[PurchaseRequestController::class,'approve'])->middleware('permission:procurement.approve')->name('requests.approve');

        Route::get('/requests/{purchaseRequest}/quotations',[QuotationController::class,'index'])->middleware('permission:procurement.view')->name('quotations.index');
        Route::post('/requests/{purchaseRequest}/quotations',[QuotationController::class,'store'])->middleware('permission:procurement.create')->name('quotations.store');
        Route::post('/quotations/{quotation}/evaluate',[QuotationController::class,'evaluate'])->middleware('permission:procurement.approve')->name('quotations.evaluate');

        Route::get('/purchase-orders',[PurchaseOrderController::class,'index'])->middleware('permission:procurement.view')->name('purchase-orders.index');
        Route::post('/purchase-orders',[PurchaseOrderController::class,'store'])->middleware('permission:procurement.approve')->name('purchase-orders.store');

        Route::post('/purchase-orders/{purchaseOrder}/receive',[GoodsReceiptController::class,'store'])->middleware('permission:procurement.receive')->name('receipts.store');
    });

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/',[AssetController::class,'index'])->middleware('permission:assets.view')->name('index');
        Route::post('/',[AssetController::class,'store'])->middleware('permission:assets.manage')->name('store');

        Route::post('/{asset}/assign',[AssetAssignmentController::class,'assign'])->middleware('permission:assets.manage')->name('assign');
        Route::post('/assignments/{assignment}/return',[AssetAssignmentController::class,'return'])->middleware('permission:assets.manage')->name('return');

        Route::post('/{asset}/maintenance',[AssetMaintenanceController::class,'store'])->middleware('permission:assets.manage')->name('maintenance.store');
        Route::post('/maintenance/{maintenance}/complete',[AssetMaintenanceController::class,'complete'])->middleware('permission:assets.manage')->name('maintenance.complete');

        Route::post('/{asset}/disposal',[AssetDisposalController::class,'request'])->middleware('permission:assets.dispose')->name('disposal.request');
        Route::post('/{asset}/disposal/approve',[AssetDisposalController::class,'approve'])->middleware('permission:assets.dispose')->name('disposal.approve');
        Route::post('/{asset}/disposal/complete',[AssetDisposalController::class,'complete'])->middleware('permission:assets.dispose')->name('disposal.complete');
    });
});

/* ===== merged from web.phase11.php ===== */
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/executive-dashboard',[ExecutiveDashboardController::class,'index'])
        ->middleware('permission:reports.view')->name('executive-dashboard');

    Route::get('/search',GlobalSearchController::class)
        ->middleware('permission:reports.view')->name('search');

    Route::get('/settings',[SettingsController::class,'index'])
        ->middleware('permission:settings.manage')->name('settings.index');
    Route::post('/settings',[SettingsController::class,'store'])
        ->middleware('permission:settings.manage')->name('settings.store');
    Route::put('/settings/{setting}',[SettingsController::class,'update'])
        ->middleware('permission:settings.manage')->name('settings.update');
    Route::delete('/settings/{setting}',[SettingsController::class,'destroy'])
        ->middleware('permission:settings.manage')->name('settings.destroy');
    Route::get('/audit-logs',[AuditLogController::class,'index'])
        ->middleware('permission:reports.view')->name('audit-logs.index');

    Route::get('/notifications',[NotificationAdminController::class,'index'])
        ->middleware('permission:reports.view')->name('notifications.index');
});

/* ===== merged from web.phase12.php ===== */
Route::get('/events',[EventPortalController::class,'index'])->name('events.index');
Route::get('/events/{event}',[EventPortalController::class,'show'])->name('events.show');
Route::middleware(['auth','verified'])->group(function(){Route::post('/events/{event}/register',[EventPortalController::class,'register'])->name('events.register');});
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
 Route::get('/events',[EventController::class,'index'])->name('events.index');
 Route::post('/events',[EventController::class,'store'])->name('events.store');
 Route::put('/events/{event}',[EventController::class,'update'])->name('events.update');
 Route::delete('/events/{event}',[EventController::class,'destroy'])->name('events.destroy');
 Route::get('/events/{event}/attendance',[EventController::class,'attendance'])->name('events.attendance');
 Route::post('/events/{event}/attendance',[EventController::class,'saveAttendance'])->name('events.attendance.save');
});

/* ===== merged from web.phase13.php ===== */
Route::get('/events/{event}/calendar.ics',[EventCheckinController::class,'calendar'])->name('events.calendar');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/events/{event}/check-in/{token}',[EventCheckinController::class,'checkin'])->name('events.checkin');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/events/{event}/view',[EventOperationsController::class,'show'])->name('events.view');
    Route::post('/events/{event}/reminders',[EventOperationsController::class,'saveReminder'])->name('events.reminders.store');
    Route::delete('/events/{event}/reminders/{reminder}',[EventOperationsController::class,'deleteReminder'])->name('events.reminders.destroy');
    Route::get('/events/{event}/attendance.csv',[EventOperationsController::class,'csv'])->name('events.attendance.csv');

    Route::get('/attendance-analytics',[EventOperationsController::class,'analytics'])->name('attendance-analytics.index');
    Route::get('/attendance-analytics.csv',[EventOperationsController::class,'analyticsCsv'])->name('attendance-analytics.csv');
});

/* ===== merged from web.phase14.php ===== */
Route::get('/event-certificates/verify/{code}',[EventEngagementController::class,'verify'])->name('events.certificates.verify');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/events/{event}/feedback',[EventEngagementController::class,'feedback'])->name('events.feedback');
    Route::post('/events/{event}/feedback',[EventEngagementController::class,'saveFeedback'])->name('events.feedback.store');
    Route::get('/events/{event}/certificate',[EventEngagementController::class,'certificate'])->name('events.certificate');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::post('/events/{event}/evaluation-settings',[EventEvaluationController::class,'settings'])->name('events.evaluation-settings');
    Route::get('/events/{event}/feedback',[EventEvaluationController::class,'feedback'])->name('events.feedback');
    Route::get('/events/{event}/reminder-logs',[EventEvaluationController::class,'reminderLogs'])->name('events.reminder-logs');

    Route::get('/events-calendar',[EventEvaluationController::class,'calendar'])->name('events.calendar');
    Route::get('/events-meal-report',[EventEvaluationController::class,'mealReport'])->name('events.meal-report');
    Route::get('/events-meal-report.csv',[EventEvaluationController::class,'mealCsv'])->name('events.meal-report.csv');
});

/* ===== merged from web.phase15.php ===== */
Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth','staff'])->group(function () {
    Route::get('/assignments',[CourseAssignmentController::class,'index'])
        ->middleware('permission:courses.edit')->name('assignments.index');

    Route::put('/enrolments/{enrolment}',[EnrolmentAdminController::class,'update'])
        ->middleware('permission:students.edit')->name('enrolments.update');

    Route::delete('/enrolments/{enrolment}',[EnrolmentAdminController::class,'destroy'])
        ->middleware('permission:students.edit')->name('enrolments.destroy');

    // IMPORTANT:
    // Do not call this route files.index because the application already has a
    // per-course route with that name: /admin/elearning/courses/{course}/files.
    Route::get('/learning-files',[LearningFileAdminController::class,'index'])
        ->middleware('permission:courses.edit')->name('learning-files.index');

    Route::delete('/learning-files/{file}',[LearningFileAdminController::class,'destroy'])
        ->middleware('permission:courses.edit')->name('learning-files.destroy');
});

/* ===== merged from web.phase16.php ===== */
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/course-attendance-report',[CourseAttendanceReportController::class,'index'])
        ->name('course-attendance-report.index');

    Route::get('/course-attendance-report.csv',[CourseAttendanceReportController::class,'csv'])
        ->name('course-attendance-report.csv');

    Route::get('/participant-attendance-summary',[CourseAttendanceReportController::class,'participant'])
        ->name('participant-attendance-summary.index');
});

/* ===== merged from web.phase17.php ===== */
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/profile',[AdminProfileController::class,'edit'])->name('profile.edit');
    Route::put('/profile',[AdminProfileController::class,'update'])->name('profile.update');
    Route::put('/profile/password',[AdminProfileController::class,'password'])->name('profile.password');
});

/* ===== merged from web.phase23.integration.php ===== */
/*
|--------------------------------------------------------------------------
| Phase 23 integration routes
|--------------------------------------------------------------------------
|
| The project already had certificate PDF generation but no admin index route.
| This route exposes the existing certificate management Blade through a
| dedicated index controller without altering the existing generation route.
|
*/

Route::middleware(['auth'])
    ->prefix('admin/elearning')
    ->name('admin.elearning.')
    ->group(function () {
        Route::get('/certificates', [CertificateIndexController::class, 'index'])
            ->name('certificates.index');
    });

/* ===== merged from web.phase23.bulk-template.php ===== */
Route::middleware(['auth'])
    ->prefix('admin/elearning')
    ->name('admin.elearning.')
    ->group(function () {
        Route::get(
            '/bulk-enrolment/template.csv',
            [BulkEnrolmentController::class, 'template']
        )
            ->middleware('permission:students.edit')
            ->name('bulk-enrolment.template');
    });

/* ===== merged from web.phase25.php ===== */
Route::middleware(['auth','staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:course_calls.view')->group(function () {
        Route::get('/course-calls',[CourseCallController::class,'index'])->name('course-calls.index');
        Route::get('/course-calls/{courseCall}/applications',[CourseCallController::class,'applications'])->name('course-calls.applications');
    });

    Route::middleware('permission:course_calls.manage')->group(function () {
        Route::post('/course-calls',[CourseCallController::class,'store'])->name('course-calls.store');
        Route::put('/course-calls/{courseCall}',[CourseCallController::class,'update'])->name('course-calls.update');
        Route::delete('/course-calls/{courseCall}',[CourseCallController::class,'destroy'])->name('course-calls.destroy');
        Route::post('/course-calls/{courseCall}/questions',[CourseCallController::class,'addQuestion'])->name('course-calls.questions.store');
    });

    Route::put('/course-applications/{application}/review',[CourseCallController::class,'review'])
        ->middleware('permission:applications.review')
        ->name('course-applications.review');

    Route::middleware('permission:surveys.view')->group(function () {
        Route::get('/surveys',[SurveyController::class,'index'])->name('surveys.index');
        Route::get('/surveys/{survey}/responses',[SurveyController::class,'responses'])->name('surveys.responses');
    });

    Route::middleware('permission:surveys.manage')->group(function () {
        Route::post('/surveys',[SurveyController::class,'store'])->name('surveys.store');
        Route::get('/surveys/{survey}/builder',[SurveyController::class,'builder'])->name('surveys.builder');
        Route::post('/surveys/{survey}/sections',[SurveyController::class,'addSection'])->name('surveys.sections.store');
        Route::post('/surveys/{survey}/questions',[SurveyController::class,'addQuestion'])->name('surveys.questions.store');
        Route::delete('/surveys/{survey}/questions/{question}',[SurveyController::class,'destroyQuestion'])->name('surveys.questions.destroy');
    });

    /*
     * IMPORTANT:
     * Use the controller index, not Route::view().
     * The hardened settings page requires $settings and $backups.
     */
    Route::get('/platform-settings',[PlatformSettingsController::class,'index'])
        ->middleware('permission:settings.manage')
        ->name('platform-settings.index');

    Route::put('/platform-settings/branding',[PlatformSettingsController::class,'updateBranding'])
        ->middleware('permission:settings.branding')
        ->name('platform-settings.branding');

    Route::put('/platform-settings/maintenance',[PlatformSettingsController::class,'updateMaintenance'])
        ->middleware('permission:settings.maintenance')
        ->name('platform-settings.maintenance');

    Route::post('/platform-settings/backup-now',[PlatformSettingsController::class,'backupNow'])
        ->middleware('permission:settings.backups')
        ->name('platform-settings.backup-now');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureParticipantUser::class])->prefix('participant')->name('participant.')->group(function () {
    Route::get('/course-opportunities',[CourseCallApplicationController::class,'index'])->name('course-calls.index');
    Route::get('/course-opportunities/{courseCall}',[CourseCallApplicationController::class,'show'])->name('course-calls.show');
    Route::put('/course-opportunities/{courseCall}',[CourseCallApplicationController::class,'save'])->name('course-calls.save');

    Route::get('/surveys',[SurveyResponseController::class,'index'])->name('surveys.index');
    Route::get('/surveys/{survey}',[SurveyResponseController::class,'show'])->name('surveys.show');
    Route::put('/surveys/{survey}',[SurveyResponseController::class,'save'])->name('surveys.save');
});

Route::get('/surveys/{survey:slug}',[PublicSurveyController::class,'show'])->name('surveys.public.show');
Route::post('/surveys/{survey:slug}',[PublicSurveyController::class,'store'])->name('surveys.public.store');
Route::get('/surveys/{survey:slug}/qr.svg',[PublicSurveyController::class,'qr'])->name('surveys.public.qr');

/* ===== merged from web.phase25.hardening.php ===== */
Route::middleware(['auth','staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::put('/surveys/{survey}/reorder',[SurveyController::class,'reorder'])
        ->middleware('permission:surveys.manage')
        ->name('surveys.reorder');

    Route::put('/surveys/{survey}/assignments',[SurveyController::class,'assignments'])
        ->middleware('permission:surveys.manage')
        ->name('surveys.assignments');

    Route::get('/surveys/{survey}/responses.csv',[SurveyController::class,'exportCsv'])
        ->middleware('permission:survey_responses.export')
        ->name('surveys.responses.csv');

    Route::put('/platform-settings/storage',[PlatformSettingsController::class,'updateStorage'])
        ->middleware('permission:settings.backups')
        ->name('platform-settings.storage');
});

/* ===== merged from web.coursecalls.multicourse.php ===== */
Route::middleware(['auth','staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('permission:course_calls.view')->group(function () {
            Route::get('/course-calls', [CourseCallController::class, 'index'])
                ->name('course-calls.index');

            Route::get('/course-calls/{courseCall}', [CourseCallController::class, 'show'])
                ->name('course-calls.show');

            Route::get('/course-calls/{courseCall}/applications', [CourseCallController::class, 'applications'])
                ->name('course-calls.applications');
        });

        Route::middleware('permission:course_calls.manage')->group(function () {
            Route::post('/course-calls', [CourseCallController::class, 'store'])
                ->name('course-calls.store');

            Route::get('/course-calls/{courseCall}/edit', [CourseCallController::class, 'edit'])
                ->name('course-calls.edit');

            Route::put('/course-calls/{courseCall}', [CourseCallController::class, 'update'])
                ->name('course-calls.update');

            Route::delete('/course-calls/{courseCall}', [CourseCallController::class, 'destroy'])
                ->name('course-calls.destroy');

            Route::post('/course-calls/{courseCall}/questions', [CourseCallController::class, 'addQuestion'])
                ->name('course-calls.questions.store');
        });
    });

/* ===== merged from web.coursecalls.display.php ===== */
Route::middleware(['auth','staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get(
            '/course-calls/{courseCall}/qr.svg',
            [CourseCallDisplayController::class, 'qr']
        )
            ->middleware('permission:course_calls.view')
            ->name('course-calls.qr');
    });

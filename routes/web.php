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
use App\Http\Controllers\Support\SupportTicketQueueController;
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
use App\Http\Controllers\Admin\LearningReportController;
use App\Http\Controllers\Admin\ME\MEDashboardController;
use App\Http\Controllers\Admin\Mentorship\MentorAdminController;
use App\Http\Controllers\Admin\Mentorship\MentorMatchController;
use App\Http\Controllers\Mentorship\MentorProfileController;
use App\Http\Controllers\Admin\Mentorship\MentorRecommendationController;
use App\Http\Controllers\Mentorship\MentorshipDashboardController;
use App\Http\Controllers\Mentorship\MentorshipAssistantController;
use App\Http\Controllers\Mentorship\ParticipantGoalController as MentorshipParticipantGoalController;
use App\Http\Controllers\Mentorship\MentorshipGoalController;
use App\Http\Controllers\Mentorship\MentorshipSessionController;
use App\Http\Controllers\Mentorship\ParticipantMentorController;
use App\Http\Controllers\Mentorship\MentorshipSessionReportController;
use App\Http\Controllers\Participant\GoalController as ParticipantGoalController;
use App\Http\Controllers\Admin\ProgrammeTargetController;
use App\Http\Controllers\Admin\MigrationController;
use App\Http\Controllers\Admin\ProgrammeManagement\MilestoneController;
use App\Http\Controllers\Instructor\ModuleAccessController;
use App\Http\Controllers\Admin\NotificationAdminController;
use App\Http\Controllers\Participant\NotificationController;
use App\Http\Controllers\Participant\SupportTicketController;
use App\Http\Controllers\Employer\OfferController;
use App\Http\Controllers\Admin\Jobs\OutcomeAdminController;
use App\Http\Controllers\Auth\ParticipantAuthController;
use App\Http\Controllers\Participant\DashboardController as ParticipantDashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Admin\ParticipantGoalController as AdminParticipantGoalController;
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

Route::get('/shared/career/{type}/{id}', [\App\Http\Controllers\Career\DocumentShareController::class, 'shared'])
    ->where('type', 'resume|cover-letter')->whereNumber('id')
    ->middleware(['signed', 'throttle:30,1'])->name('career.documents.shared');
use App\Http\Controllers\BrandAssetController;

use App\Http\Controllers\Jobs\SavedJobController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\HR\StaffAppraisalController;
use App\Http\Controllers\Auth\StaffAuthController;
use App\Http\Controllers\Admin\HR\StaffExitController;
use App\Http\Controllers\Admin\Procurement\SupplierController;
use App\Http\Controllers\Admin\MasterLists\DepartmentController as MasterListDepartmentController;
use App\Http\Controllers\Admin\MasterLists\FundingSourceController as MasterListFundingSourceController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Participant\SurveyResponseController;
use App\Http\Controllers\Admin\ProgrammeManagement\TaskController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProgrammeManagement\WorkplanController;

Route::get('/brand-assets/{type}', [BrandAssetController::class, 'show'])
    ->whereIn('type', ['logo', 'favicon'])
    ->name('branding.asset');

Route::get('/manifest.webmanifest', [BrandAssetController::class, 'manifest'])
    ->name('manifest');

Route::get('/shared/career/portfolio/{file}', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'shared'])->middleware(['signed', 'throttle:30,1'])->name('career.portfolio.shared');
Route::get('/shared/certificates/{type}/{id}', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'shared'])->where('type','course|event')->whereNumber('id')->middleware(['signed','throttle:30,1'])->name('certificates.shared');

/*
|--------------------------------------------------------------------------
| ElevateHer360 Web Routes
|--------------------------------------------------------------------------
|
| Public, participant, staff/admin and instructor routes are consolidated
| here. Staff and instructors share /admin/dashboard. Legacy dashboard URLs
| remain only as compatibility redirects.
|
*/


Route::get('/', [\App\Http\Controllers\Public\ContentPageController::class, 'home'])->name('home');

// Public legal pages. The participant app and the app store listings link
// to these URLs, so they must stay public and keep these paths.
Route::get('/privacy-policy', [\App\Http\Controllers\Public\ContentPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [\App\Http\Controllers\Public\ContentPageController::class, 'terms'])->name('legal.terms');
Route::get('/faqs', [\App\Http\Controllers\Public\ContentPageController::class, 'faqs'])->name('public.faqs');
Route::get('/pages/{slug}', [\App\Http\Controllers\Public\ContentPageController::class, 'show'])->where('slug', '[a-z][a-z0-9-]*')->name('public.pages.show');

// Email open-tracking pixel (marks a notification as opened).
Route::get('/email/track/{token}', [\App\Http\Controllers\EmailTrackingController::class, 'track'])
    ->where('token', '[A-Za-z0-9]+')->name('email.track');

Route::middleware('guest')->group(function () {
    foreach (['mentor' => 'mentors', 'employer' => 'employers'] as $type => $path) {
        Route::get('/'.$path.'/signup', [\App\Http\Controllers\Public\PartnerSignupController::class, 'show'])->defaults('type', $type)->name('public.partners.'.$type);
        Route::post('/'.$path.'/signup', [\App\Http\Controllers\Public\PartnerSignupController::class, 'store'])->defaults('type', $type)->middleware('throttle:5,1')->name('public.partners.'.$type.'.store');

        Route::get('/'.$path.'/login', [\App\Http\Controllers\Auth\PartnerAuthController::class, 'showLogin'])->defaults('type', $type)->name('partners.'.$type.'.login');
        Route::post('/'.$path.'/login', [\App\Http\Controllers\Auth\PartnerAuthController::class, 'login'])->defaults('type', $type)->middleware('throttle:5,1')->name('partners.'.$type.'.login.attempt');
    }
});

// Mentors and employers sign out back to the public landing page.
Route::post('/partners/logout', [\App\Http\Controllers\Auth\PartnerAuthController::class, 'logout'])
    ->middleware('auth')->name('partners.logout');

Route::prefix('admin/cms')->name('admin.cms.')->middleware(['auth', 'staff', 'permission:cms.manage'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CmsPageController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\CmsPageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [\App\Http\Controllers\Admin\CmsPageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [\App\Http\Controllers\Admin\CmsPageController::class, 'update'])->name('update');
    Route::get('/{page}/preview', [\App\Http\Controllers\Admin\CmsPageController::class, 'preview'])->name('preview');
    Route::delete('/{page}', [\App\Http\Controllers\Admin\CmsPageController::class, 'destroy'])->name('destroy');
});

Route::post('/support/chatbot',[ChatbotController::class, 'message'])
    ->middleware('throttle:60,1')
    ->name('support.chatbot.message');
// Participant authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [ParticipantAuthController::class, 'create'])->name('register');
    Route::post('/register', [ParticipantAuthController::class, 'store'])->name('register.store');
    Route::get('/login', [ParticipantAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ParticipantAuthController::class, 'login'])->name('login.attempt');

    Route::get('/admin/login', [StaffAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [StaffAuthController::class, 'login'])->name('admin.login.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'email'])
    ->name('password.email');

Route::get('/admin/forgot-password', [PasswordResetController::class, 'staffRequestForm'])
    ->name('admin.password.request');

Route::post('/admin/forgot-password', [PasswordResetController::class, 'staffEmail'])
    ->name('admin.password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [ParticipantAuthController::class, 'logout'])->name('logout');

    // Browser support ticket contract: subject, description, category (optional),
    // priority (optional). StoreBrowserSupportTicketRequest applies the create
    // policy; the controller assigns requester_id from the session user.
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('participant.support-tickets.store');

    Route::get('/email/verify', fn () => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        $destination = $request->user()->isStaff() ? 'admin.dashboard' : 'dashboard';
        return redirect()->route($destination)->with('success', 'Email verified.');
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

        Route::post('/programmes/{programme}/targets', [ProgrammeTargetController::class, 'store'])
            ->middleware('permission:programmes.manage')->name('programmes.targets.store');
        Route::put('/programme-targets/{target}', [ProgrammeTargetController::class, 'update'])
            ->middleware('permission:programmes.manage')->name('programme-targets.update');
        Route::delete('/programme-targets/{target}', [ProgrammeTargetController::class, 'destroy'])
            ->middleware('permission:programmes.manage')->name('programme-targets.destroy');

        Route::delete('/projects/bulk-delete', [ProjectController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('projects.bulk-destroy');

        Route::resource('projects', ProjectController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::delete('/branches/bulk-delete', [BranchController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('branches.bulk-destroy');

        Route::resource('branches', BranchController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::delete('/cohorts/bulk-delete', [CohortController::class, 'bulkDestroy'])->middleware(['permission:cohorts.manage','role:administrator,super-administrator,super-admin'])->name('cohorts.bulk-destroy');

        Route::resource('cohorts', CohortController::class)->except('show')
            ->middleware(['permission:cohorts.manage','role:administrator,super-administrator,super-admin']);

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.manage')->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.manage')->name('roles.store');
        Route::post('/permissions', [RoleController::class, 'storePermission'])
            ->middleware('permission:permissions.manage')->name('permissions.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:roles.manage')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage')->name('roles.update');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'staff'])
    ->group(function () {
        Route::get('/dashboard',[AdminDashboardController::class,'index'])->name('dashboard');

        Route::get('/participants',[UserController::class,'index'])
            ->middleware('permission:users.view')->name('participants.index');

        Route::delete('/participants/bulk-delete',[UserController::class,'bulkDestroy'])
            ->middleware('permission:users.delete')->name('participants.bulk-destroy');

        Route::delete('/users/bulk-delete',[UserController::class,'bulkDestroy'])
            ->middleware('permission:users.delete')->name('users.bulk-destroy');

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
            Route::get('/timetable', [\App\Http\Controllers\Admin\Elearning\TimetableController::class, 'index'])->name('timetable.index');
            Route::get('/timetable/{course}', [\App\Http\Controllers\Admin\Elearning\TimetableController::class, 'manage'])->name('timetable.manage');
            Route::post('/timetable/{course}', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'store'])->name('timetable.store');
            Route::put('/timetable/{course}/{slot}', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'update'])->name('timetable.update');
            Route::delete('/timetable/{course}/{slot}', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'destroy'])->name('timetable.destroy');
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
    Route::get('/learning/my-courses/{course}', [\App\Http\Controllers\Learning\CourseLearningController::class, 'show'])
        ->middleware(\App\Http\Middleware\EnsureParticipantUser::class)->name('learning.course.dashboard');

    Route::get(
        '/learning/lessons/{lesson}',
        [LearningLessonController::class, 'show']
    )->name('learning.lesson.show');

    Route::post(
        '/learning/lessons/{lesson}/reading-time',
        [LearningLessonController::class, 'recordReadingTime']
    )->name('learning.lesson.reading-time');

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

        Route::delete(
            '/enrolments/bulk-delete',
            [EnrolmentAdminController::class, 'bulkDestroy']
        )
            ->middleware('permission:students.edit')
            ->name('enrolments.bulk-destroy');

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

        Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
            ->name('dashboard');

        Route::get(
            '/courses/{course}/attendance',
            [AttendanceController::class, 'create']
        )->name('attendance.create');

        Route::post(
            '/courses/{course}/attendance',
            [AttendanceController::class, 'store']
        )->name('attendance.store');

        Route::get(
            '/courses/{course}/attendance/daily',
            [DailyAttendanceController::class, 'index']
        )->name('attendance.daily');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/learning/files/{file}/download',[LearningFileController::class,'download'])
        ->name('learning.files.download');
    Route::get('/learning/submissions/files/{file}',[LearningFileController::class,'submissionFile'])
        ->name('learning.submissions.files.show');

    Route::get('/mentorship',[MentorshipDashboardController::class,'index'])->middleware('user_type:mentee,mentor')->name('mentorship.dashboard');
    Route::get('/mentorship/mentors',[ParticipantMentorController::class,'index'])->middleware('user_type:mentee')->name('mentorship.mentors.index');
    Route::post('/mentorship/mentors',[ParticipantMentorController::class,'store'])->middleware('user_type:mentee')->name('mentorship.mentors.store');
    Route::delete('/mentorship/matches/{match}',[ParticipantMentorController::class,'destroy'])->middleware('user_type:mentee')->name('mentorship.matches.destroy');
    Route::post('/mentorship/assistant',[MentorshipAssistantController::class,'message'])
        ->middleware('throttle:20,1')->middleware('user_type:mentee,mentor')->name('mentorship.assistant.message');
    Route::get('/mentorship/mentor-profile',[MentorProfileController::class,'edit'])->middleware('user_type:mentor')->name('mentorship.mentor-profile.edit');
    Route::put('/mentorship/mentor-profile',[MentorProfileController::class,'update'])->middleware('user_type:mentor')->name('mentorship.mentor-profile.update');
    Route::post('/mentorship/matches/{match}/sessions',[MentorshipSessionController::class,'store'])->middleware('user_type:mentee,mentor')->name('mentorship.sessions.store');
    Route::put('/mentorship/sessions/{session}/complete',[MentorshipSessionController::class,'complete'])->middleware('user_type:mentee,mentor')->name('mentorship.sessions.complete');
    Route::post('/mentorship/sessions/{session}/reports',[MentorshipSessionReportController::class,'store'])->middleware('user_type:mentee,mentor')->name('mentorship.sessions.reports.store');
    Route::post('/mentorship/goals/{goal}/review',[MentorshipParticipantGoalController::class,'review'])
        ->whereNumber('goal')->middleware('user_type:mentee,mentor')->name('mentorship.participant-goals.review');

    Route::post('/profile/goals',[ParticipantGoalController::class,'store'])->name('profile.goals.store');
    Route::put('/profile/goals/{goal}',[ParticipantGoalController::class,'update'])->name('profile.goals.update');
    Route::put('/profile/goals/{goal}/progress',[ParticipantGoalController::class,'progress'])->name('profile.goals.progress');
    Route::delete('/profile/goals/{goal}',[ParticipantGoalController::class,'destroy'])->name('profile.goals.destroy');
});

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth', 'staff'])->group(function () {
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
    Route::post('/bulk-enrolment/enroll-selected',[BulkEnrolmentController::class,'enrollSelected'])
        ->middleware('permission:students.edit')->name('bulk-enrolment.enroll-selected');

    Route::post('/courses/{course}/files',[LearningFileAdminController::class,'store'])
        ->middleware('permission:courses.edit')->name('files.store');

    Route::post('/certificates/{certificate}/generate',[CertificateAdminController::class,'generate'])
        ->middleware('permission:courses.edit')->name('certificates.generate');
});

Route::prefix('admin/mentorship')->name('admin.mentorship.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/mentors/create', [MentorAdminController::class, 'create'])->middleware('permission:mentors.manage')->name('mentors.create');
    Route::post('/mentors', [MentorAdminController::class, 'store'])->middleware('permission:mentors.manage')->name('mentors.store');
    Route::get('/mentors/{mentor}/edit', [MentorAdminController::class, 'edit'])->middleware('permission:mentors.manage')->name('mentors.edit');
    Route::put('/mentors/{mentor}', [MentorAdminController::class, 'update'])->middleware('permission:mentors.manage')->name('mentors.update');
    Route::get('/mentors',[MentorAdminController::class,'index'])
        ->middleware('permission:mentors.manage')->name('mentors.index');
    Route::post('/mentors/{mentor}/approve',[MentorAdminController::class,'approve'])
        ->middleware('permission:mentors.manage')->name('mentors.approve');
    Route::post('/mentors/{mentor}/reject',[MentorAdminController::class,'reject'])
        ->middleware('permission:mentors.manage')->name('mentors.reject');
    Route::delete('/mentors/{mentor}',[MentorAdminController::class,'destroy'])
        ->middleware('permission:mentors.manage')->name('mentors.destroy');

    Route::delete('/mentors/bulk-delete',[MentorAdminController::class,'bulkDestroy'])
        ->middleware('permission:mentors.manage')->name('mentors.bulk-destroy');

    Route::get('/matches',[MentorMatchController::class,'index'])
        ->middleware('permission:mentorship.match')->name('matches.index');
    Route::post('/matches',[MentorMatchController::class,'store'])
        ->middleware('permission:mentorship.match')->name('matches.store');
    Route::put('/matches/{match}',[MentorMatchController::class,'update'])
        ->middleware('permission:mentorship.match')->name('matches.update');
    Route::delete('/matches/{match}',[MentorMatchController::class,'destroy'])
        ->middleware('permission:mentorship.match')->name('matches.destroy');

    Route::delete('/matches/bulk-delete',[MentorMatchController::class,'bulkDestroy'])
        ->middleware('permission:mentorship.match')->name('matches.bulk-destroy');
});

Route::get('/jobs',[JobBrowseController::class,'index'])->name('jobs.index');

// Static job paths must be declared before /jobs/{job} so they are not
// interpreted as a job model key such as "saved" or "recommendations".
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/jobs/saved',[SavedJobController::class,'index'])->name('jobs.saved');
    Route::get('/jobs/recommendations',[JobRecommendationController::class,'index'])->name('jobs.recommendations');
});

Route::get('/jobs/{job}',[JobBrowseController::class,'show'])->name('jobs.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/jobs/{job}/apply',[JobApplicationController::class,'store'])->name('jobs.apply');
    Route::get('/my-job-applications',[JobApplicationController::class,'index'])->name('jobs.applications');
    Route::post('/job-applications/{application}/withdraw',[JobApplicationController::class,'withdraw'])->name('jobs.withdraw');

    Route::post('/mentorship/matches/{match}/goals',[MentorshipGoalController::class,'store'])->middleware('user_type:mentee,mentor')->name('mentorship.goals.store');
    Route::put('/mentorship/goals/{goal}',[MentorshipGoalController::class,'update'])->middleware('user_type:mentee,mentor')->name('mentorship.goals.update');

    Route::get('/employer/profile',[EmployerProfileController::class,'edit'])->middleware('user_type:employer')->name('employer.profile.edit');
    Route::put('/employer/profile',[EmployerProfileController::class,'update'])->middleware('user_type:employer')->name('employer.profile.update');
    Route::get('/employer/jobs',[EmployerJobController::class,'index'])->middleware('user_type:employer')->name('employer.jobs.index');
    Route::post('/employer/jobs',[EmployerJobController::class,'store'])->middleware('user_type:employer')->name('employer.jobs.store');
    Route::get('/employer/applicants',[ApplicantController::class,'index'])->middleware('user_type:employer')->name('employer.applicants.index');
    Route::put('/employer/applicants/{application}/status',[ApplicantController::class,'status'])->middleware('user_type:employer')->name('employer.applicants.status');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/mentorship/mentees/{mentee}/recommendations',[MentorRecommendationController::class,'show'])
        ->middleware('permission:mentorship.match')->name('mentorship.recommendations');

    Route::delete('/jobs/employers/bulk-delete',[EmployerAdminController::class,'bulkDestroy'])
        ->middleware('permission:employers.approve')->name('jobs.employers.bulk-destroy');

    Route::get('/jobs/employers',[EmployerAdminController::class,'index'])
        ->middleware('permission:employers.approve')->name('jobs.employers.index');
    Route::get('/jobs/employers/create', [EmployerAdminController::class, 'create'])->middleware('permission:employers.approve')->name('jobs.employers.create');
    Route::post('/jobs/employers', [EmployerAdminController::class, 'store'])->middleware('permission:employers.approve')->name('jobs.employers.store');
    Route::get('/jobs/employers/{employer}/edit', [EmployerAdminController::class, 'edit'])->middleware('permission:employers.approve')->name('jobs.employers.edit');
    Route::put('/jobs/employers/{employer}', [EmployerAdminController::class, 'update'])->middleware('permission:employers.approve')->name('jobs.employers.update');
    Route::post('/jobs/employers/{employer}/approve',[EmployerAdminController::class,'approve'])
        ->middleware('permission:employers.approve')->name('jobs.employers.approve');
    Route::post('/jobs/employers/{employer}/reject',[EmployerAdminController::class,'reject'])
        ->middleware('permission:employers.approve')->name('jobs.employers.reject');
    Route::delete('/jobs/employers/{employer}',[EmployerAdminController::class,'destroy'])
        ->middleware('permission:employers.approve')->name('jobs.employers.destroy');

    Route::get('/jobs',[JobAdminController::class,'index'])
        ->middleware('permission:jobs.manage')->name('jobs.index');

    Route::delete('/jobs/bulk-delete',[JobAdminController::class,'bulkDestroy'])
        ->middleware('permission:jobs.manage')->name('jobs.bulk-destroy');

    Route::post('/jobs',[JobAdminController::class,'store'])
        ->middleware('permission:jobs.manage')->name('jobs.store');
    Route::post('/jobs/import',[JobAdminController::class,'import'])
        ->middleware('permission:jobs.manage')->name('jobs.import');

    Route::get('/jobs/applications',[\App\Http\Controllers\Admin\Jobs\JobApplicationAdminController::class,'index'])
        ->middleware('permission:jobs.manage')->name('jobs.applications.index');

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

Route::get('/library',[LibraryController::class,'index'])->name('library.index');
Route::get('/library/{resource}',[LibraryController::class,'show'])->name('library.show');
Route::get('/library/{resource}/cover',[LibraryController::class,'cover'])->name('library.cover');
Route::get('/library/{resource}/download',[LibraryController::class,'download'])->name('library.download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/career/resumes',[ResumeController::class,'index'])->name('career.resume.index');
    Route::get('/career/resumes/create',[ResumeController::class,'create'])->name('career.resume.create');
    Route::post('/career/resumes',[ResumeController::class,'store'])->name('career.resume.store');
    Route::post('/career/resumes/{resume}/portfolio-files', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'store'])->name('career.portfolio.store');
    Route::post('/career/resumes/{resume}/extras/{section}', [\App\Http\Controllers\Career\ResumeExtrasController::class, 'store'])->where('section', 'projects|referees')->name('career.resume.extras.store');
    Route::put('/career/resumes/{resume}/extras/{section}/{id}', [\App\Http\Controllers\Career\ResumeExtrasController::class, 'update'])->where('section', 'projects|referees')->whereNumber('id')->name('career.resume.extras.update');
    Route::delete('/career/resumes/{resume}/extras/{section}/{id}', [\App\Http\Controllers\Career\ResumeExtrasController::class, 'destroy'])->where('section', 'projects|referees')->whereNumber('id')->name('career.resume.extras.destroy');
    Route::get('/career/resumes/{resume}/portfolio-files/{file}', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'download'])->name('career.portfolio.download');
    Route::delete('/career/resumes/{resume}/portfolio-files/{file}', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'destroy'])->name('career.portfolio.destroy');
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
    Route::get('/career/cover-letters/{letter}/download', [CoverLetterController::class, 'download'])->name('career.cover-letter.download');
    Route::put('/career/cover-letters/{letter}', [CoverLetterController::class, 'update'])->name('career.cover-letter.update');
    Route::delete('/career/cover-letters/{letter}', [CoverLetterController::class, 'destroy'])->name('career.cover-letter.destroy');
    Route::post('/career/documents/{type}/{id}/share', [\App\Http\Controllers\Career\DocumentShareController::class, 'share'])->where('type', 'resume|cover-letter')->whereNumber('id')->name('career.documents.share');
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

    Route::post('/jobs/{job}/save',[SavedJobController::class,'store'])->name('jobs.save');
    Route::delete('/jobs/{job}/save',[SavedJobController::class,'destroy'])->name('jobs.unsave');

    Route::post('/employer/applications/{application}/interviews',[InterviewController::class,'store'])->middleware('user_type:employer')->name('employer.interviews.store');
    Route::post('/employer/applications/{application}/offers',[OfferController::class,'store'])->middleware('user_type:employer')->name('employer.offers.store');
    Route::post('/library/{resource}/bookmark',[LibraryController::class,'bookmark'])->name('library.bookmark');
});

Route::prefix('admin/library')->name('admin.library.')->middleware(['auth','staff','permission:library.manage'])->group(function () {
    Route::get('/',[LibraryResourceController::class,'index'])->name('index');
    Route::delete('/bulk-delete',[LibraryResourceController::class,'bulkDestroy'])->name('bulk-destroy');
    Route::post('/',[LibraryResourceController::class,'store'])->name('store');
    Route::put('/{resource}',[LibraryResourceController::class,'update'])->name('update');
    Route::delete('/{resource}',[LibraryResourceController::class,'destroy'])->name('destroy');
});

Route::prefix('admin/career-ai')->name('admin.career-ai.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/',[CareerAiController::class,'index'])->name('index');
    Route::put('/',[CareerAiController::class,'update'])->name('update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/participant-goals',[AdminParticipantGoalController::class,'index'])
        ->middleware('permission:users.view')->name('participant-goals.index');

    Route::delete('/participant-goals/bulk-delete',[AdminParticipantGoalController::class,'bulkDestroy'])
        ->middleware('permission:users.delete')->name('participant-goals.bulk-destroy');

    Route::get('/workplans',[WorkplanController::class,'index'])->middleware('permission:workplans.view')->name('workplans.index');
    Route::post('/workplans',[WorkplanController::class,'store'])->middleware('permission:workplans.create')->name('workplans.store');
    Route::post('/workplans/{workplan}/submit',[WorkplanController::class,'submit'])->middleware('permission:workplans.edit')->name('workplans.submit');
    Route::post('/workplans/{workplan}/approve',[WorkplanController::class,'approve'])->middleware('permission:workplans.approve')->name('workplans.approve');
    Route::post('/workplans/{workplan}/return',[WorkplanController::class,'returnForRevision'])->middleware('permission:workplans.approve')->name('workplans.return');
    Route::post('/workplans/{workplan}/reject',[WorkplanController::class,'reject'])->middleware('permission:workplans.approve')->name('workplans.reject');
    Route::post('/workplans/{workplan}/start',[WorkplanController::class,'start'])->middleware('permission:workplans.edit')->name('workplans.start');
    Route::post('/workplans/{workplan}/complete',[WorkplanController::class,'complete'])->middleware('permission:workplans.edit')->name('workplans.complete');

    Route::post('/workplans/{workplan}/milestones',[MilestoneController::class,'store'])->middleware('permission:milestones.manage')->name('milestones.store');
    Route::put('/milestones/{milestone}',[MilestoneController::class,'update'])->middleware('permission:milestones.manage')->name('milestones.update');

    Route::post('/workplans/{workplan}/activities',[ActivityController::class,'store'])->middleware('permission:activities.manage')->name('activities.store');
    Route::put('/activities/{activity}/progress',[ActivityController::class,'updateProgress'])->middleware('permission:activities.manage')->name('activities.progress');

    Route::get('/indicators',[IndicatorController::class,'index'])->middleware('permission:indicators.view')->name('indicators.index');
    Route::delete('/indicators/bulk-delete',[IndicatorController::class,'bulkDestroy'])->middleware('permission:indicators.manage')->name('indicators.bulk-destroy');
    Route::post('/indicators',[IndicatorController::class,'store'])->middleware('permission:indicators.manage')->name('indicators.store');
    Route::post('/indicators/{indicator}/targets',[IndicatorController::class,'addTarget'])->middleware('permission:indicators.manage')->name('indicators.targets.store');
    Route::post('/indicators/{indicator}/calculate',[IndicatorController::class,'calculate'])->middleware('permission:indicators.manage')->name('indicators.calculate');
    Route::post('/indicator-results/{result}/verify',[IndicatorController::class,'verify'])->middleware('permission:indicators.verify')->name('indicator-results.verify');

    Route::get('/results-framework',[ResultsFrameworkController::class,'index'])->middleware('permission:meal.view')->name('results-framework.index');
    Route::delete('/results-framework/bulk-delete',[ResultsFrameworkController::class,'bulkDestroy'])->middleware('permission:meal.manage')->name('results-framework.bulk-destroy');
    Route::post('/results-framework',[ResultsFrameworkController::class,'store'])->middleware('permission:meal.manage')->name('results-framework.store');
    Route::post('/results-framework/{framework}/results',[ResultsFrameworkController::class,'addResult'])->middleware('permission:meal.manage')->name('results-framework.results.store');

    Route::get('/meal',[MEDashboardController::class,'index'])->middleware('permission:meal.view')->name('meal.dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/tasks',[TaskController::class,'index'])->middleware('permission:tasks.manage')->name('tasks.index');
    Route::delete('/tasks/bulk-delete',[TaskController::class,'bulkDestroy'])->middleware('permission:tasks.manage')->name('tasks.bulk-destroy');
    Route::post('/activities/{activity}/tasks',[TaskController::class,'store'])->middleware('permission:tasks.manage')->name('tasks.store');
    Route::put('/tasks/{task}',[TaskController::class,'update'])->middleware('permission:tasks.manage')->name('tasks.update');

    Route::get('/deliverables',[DeliverableController::class,'index'])->middleware('permission:tasks.manage')->name('deliverables.index');
    Route::delete('/deliverables/bulk-delete',[DeliverableController::class,'bulkDestroy'])->middleware('permission:tasks.manage')->name('deliverables.bulk-destroy');
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
        Route::delete('/employees/bulk-delete',[EmployeeController::class,'bulkDestroy'])->middleware('permission:hr.manage')->name('employees.bulk-destroy');
        Route::post('/employees',[EmployeeController::class,'store'])->middleware('permission:hr.manage')->name('employees.store');

        Route::post('/employees/{employee}/contracts',[ContractController::class,'store'])->middleware('permission:hr.manage')->name('contracts.store');

        // Supervisors (own team), HR and administrators; each action checks
        // App\Support\LeaveApprovalAccess in the controller.
        Route::get('/leave',[LeaveApprovalController::class,'index'])->name('leave.index');
        Route::delete('/leave/bulk-delete',[LeaveApprovalController::class,'bulkDestroy'])->middleware('role:hr,administrator,super-administrator,super-admin')->name('leave.bulk-destroy');
        Route::post('/leave/{leave}/supervisor-approve',[LeaveApprovalController::class,'supervisorApprove'])->name('leave.supervisor-approve');
        Route::post('/leave/{leave}/hr-approve',[LeaveApprovalController::class,'hrApprove'])->name('leave.hr-approve');
        Route::post('/leave/{leave}/reject',[LeaveApprovalController::class,'reject'])->name('leave.reject');
        Route::put('/leave/{leave}',[LeaveApprovalController::class,'update'])->name('leave.update');
        Route::post('/leave/{leave}/cancel',[LeaveApprovalController::class,'cancel'])->name('leave.cancel');

        Route::get('/kpi-templates',[KpiTemplateController::class,'index'])->middleware(['permission:appraisals.view','role:hr,administrator,super-administrator,super-admin'])->name('kpi-templates.index');
        Route::post('/kpi-templates',[KpiTemplateController::class,'store'])->middleware(['permission:appraisals.manage','role:hr,administrator,super-administrator,super-admin'])->name('kpi-templates.store');
        Route::put('/kpi-templates/{template}',[KpiTemplateController::class,'update'])->middleware(['permission:appraisals.manage','role:hr,administrator,super-administrator,super-admin'])->name('kpi-templates.update');
        Route::delete('/kpi-templates/{template}',[KpiTemplateController::class,'destroy'])->middleware(['permission:appraisals.manage','role:hr,administrator,super-administrator,super-admin'])->name('kpi-templates.destroy');

        Route::get('/appraisals',[AppraisalController::class,'index'])->middleware('permission:appraisals.view')->name('appraisals.index');
        Route::delete('/appraisals/bulk-delete',[AppraisalController::class,'bulkDestroy'])->middleware('permission:appraisals.manage')->name('appraisals.bulk-destroy');
        Route::get('/appraisals/{appraisal}/kpis',[AppraisalKpiController::class,'show'])->middleware('permission:appraisals.view')->name('appraisals.kpis');
        Route::post('/appraisals/{appraisal}/kpi-template',[AppraisalKpiController::class,'assignTemplate'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-template');
        Route::post('/appraisals/{appraisal}/kpi-score',[AppraisalKpiController::class,'score'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-score');
        Route::post('/appraisal-cycles',[AppraisalController::class,'createCycle'])->middleware('permission:appraisals.manage')->name('appraisal-cycles.store');
        Route::post('/appraisals/assign',[AppraisalController::class,'assign'])->middleware(['permission:appraisals.manage','role:hr,administrator,super-administrator,super-admin'])->name('appraisals.assign');
        Route::post('/appraisals/{appraisal}/objectives',[AppraisalController::class,'addObjective'])->middleware('permission:appraisals.manage')->name('appraisals.objectives.store');
        Route::post('/appraisals/{appraisal}/recalculate',[AppraisalController::class,'recalculate'])->middleware('permission:appraisals.manage')->name('appraisals.recalculate');
        Route::post('/appraisals/{appraisal}/finalise',[AppraisalWorkflowController::class,'finalise'])->middleware('permission:appraisals.manage')->name('appraisals.finalise');
        Route::post('/appraisals/{appraisal}/lock',[AppraisalWorkflowController::class,'lock'])->middleware('permission:appraisals.manage')->name('appraisals.lock');
        Route::post('/appraisals/{appraisal}/reopen',[AppraisalWorkflowController::class,'reopen'])->middleware('permission:appraisals.manage')->name('appraisals.reopen');

        Route::get('/exits',[StaffExitController::class,'index'])->middleware('permission:staff_exit.manage')->name('exits.index');
        Route::post('/exits',[StaffExitController::class,'store'])->middleware('permission:staff_exit.manage')->name('exits.store');
        Route::post('/exits/{exit}/clear',[StaffExitController::class,'clear'])->middleware('permission:staff_exit.manage')->name('exits.clear');
        Route::post('/exits/{exit}/complete',[StaffExitController::class,'complete'])->middleware('permission:staff_exit.manage')->name('exits.complete');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/suppliers',[SupplierController::class,'index'])->middleware('permission:procurement.view')->name('suppliers.index');
        Route::delete('/suppliers/bulk-delete',[SupplierController::class,'bulkDestroy'])->middleware('permission:procurement.create')->name('suppliers.bulk-destroy');
        Route::post('/suppliers',[SupplierController::class,'store'])->middleware('permission:procurement.create')->name('suppliers.store');
        Route::post('/suppliers/{supplier}/approve',[SupplierController::class,'approve'])->middleware('permission:procurement.approve')->name('suppliers.approve');

        // Procurement Admin: procurement officers, finance, management and admins
        // (procurement.view). Other staff use My Purchase Requests.
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

    // Managed pick-lists. Access is checked in the controllers
    // (App\Support\MasterListAccess): HR for departments, procurement /
    // finance for funding sources, administrators for both.
    foreach (['departments' => [MasterListDepartmentController::class, 'department'], 'funding-sources' => [MasterListFundingSourceController::class, 'fundingSource']] as $listPath => [$listController, $listParam]) {
        Route::get("/{$listPath}",[$listController,'index'])->name("{$listPath}.index");
        Route::post("/{$listPath}",[$listController,'store'])->name("{$listPath}.store");
        Route::delete("/{$listPath}/bulk-delete",[$listController,'bulkDestroy'])->name("{$listPath}.bulk-destroy");
        Route::put("/{$listPath}/{{$listParam}}",[$listController,'update'])->name("{$listPath}.update");
        Route::patch("/{$listPath}/{{$listParam}}/toggle",[$listController,'toggle'])->name("{$listPath}.toggle");
        Route::delete("/{$listPath}/{{$listParam}}",[$listController,'destroy'])->name("{$listPath}.destroy");
    }

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/',[AssetController::class,'index'])->middleware('permission:assets.view')->name('index');
        Route::delete('/bulk-delete',[AssetController::class,'bulkDestroy'])->middleware('permission:assets.manage')->name('bulk-destroy');
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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/executive-dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('executive-dashboard');

    Route::get('/learning-reports', [LearningReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('learning-reports.index');
    Route::get('/learning-reports.csv', [LearningReportController::class, 'csv'])
        ->middleware(['permission:reports.view', 'throttle:10,1'])->name('learning-reports.csv');
    Route::get('/learning-reports.pdf', [LearningReportController::class, 'pdf'])
        ->middleware(['permission:reports.view', 'throttle:10,1'])->name('learning-reports.pdf');

    Route::get('/search',GlobalSearchController::class)
        ->middleware('permission:reports.view')->name('search');

    Route::get('/settings',[SettingsController::class,'index'])
        ->middleware('permission:settings.manage')->name('settings.index');
    Route::post('/settings',[SettingsController::class,'store'])
        ->middleware('permission:settings.manage')->name('settings.store');
    Route::put('/settings/{setting}',[SettingsController::class,'update'])
        ->middleware('permission:settings.manage')->name('settings.update');
    Route::delete('/settings/bulk-delete',[SettingsController::class,'bulkDestroy'])
        ->middleware('permission:settings.manage')->name('settings.bulk-destroy');
    Route::delete('/settings/{setting}',[SettingsController::class,'destroy'])
        ->middleware('permission:settings.manage')->name('settings.destroy');
    Route::get('/audit-logs',[AuditLogController::class,'index'])
        ->middleware('permission:reports.view')->name('audit-logs.index');

    Route::get('/notifications',[NotificationAdminController::class,'index'])
        ->middleware('permission:reports.view')->name('notifications.index');
});

Route::get('/events',[EventPortalController::class,'index'])->name('events.index');
Route::get('/events/{event}',[EventPortalController::class,'show'])->name('events.show');
Route::middleware(['auth', 'verified'])->group(function(){Route::post('/events/{event}/register',[EventPortalController::class,'register'])->name('events.register');});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function(){
 Route::get('/events',[EventController::class,'index'])->name('events.index');
 Route::delete('/events/bulk-delete',[EventController::class,'bulkDestroy'])->name('events.bulk-destroy');
 Route::post('/events',[EventController::class,'store'])->name('events.store');
 Route::put('/events/{event}',[EventController::class,'update'])->name('events.update');
 Route::delete('/events/{event}',[EventController::class,'destroy'])->name('events.destroy');
 Route::get('/events/{event}/attendance',[EventController::class,'attendance'])->name('events.attendance');
 Route::post('/events/{event}/attendance',[EventController::class,'saveAttendance'])->name('events.attendance.save');
});

Route::get('/events/{event}/calendar.ics',[EventCheckinController::class,'calendar'])->name('events.calendar');

Route::middleware(['auth', 'verified'])->group(function(){
    Route::get('/events/{event}/check-in/{token}',[EventCheckinController::class,'checkin'])->name('events.checkin');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function(){
    Route::get('/events/{event}/view',[EventOperationsController::class,'show'])->name('events.view');
    Route::post('/events/{event}/reminders',[EventOperationsController::class,'saveReminder'])->name('events.reminders.store');
    Route::delete('/events/{event}/reminders/{reminder}',[EventOperationsController::class,'deleteReminder'])->name('events.reminders.destroy');
    Route::get('/events/{event}/attendance.csv',[EventOperationsController::class,'csv'])->name('events.attendance.csv');

    Route::get('/attendance-analytics',[EventOperationsController::class,'analytics'])->name('attendance-analytics.index');
    Route::get('/attendance-analytics.csv',[EventOperationsController::class,'analyticsCsv'])->name('attendance-analytics.csv');
});

Route::get('/event-certificates/verify/{code}',[EventEngagementController::class,'verify'])->name('events.certificates.verify');

Route::middleware(['auth', 'verified'])->group(function(){
    Route::get('/events/{event}/feedback',[EventEngagementController::class,'feedback'])->name('events.feedback');
    Route::post('/events/{event}/feedback',[EventEngagementController::class,'saveFeedback'])->name('events.feedback.store');
    Route::get('/events/{event}/certificate',[EventEngagementController::class,'certificate'])->name('events.certificate');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function(){
    Route::post('/events/{event}/evaluation-settings',[EventEvaluationController::class,'settings'])->name('events.evaluation-settings');
    Route::get('/events/{event}/feedback',[EventEvaluationController::class,'feedback'])->name('events.feedback');
    Route::get('/events/{event}/reminder-logs',[EventEvaluationController::class,'reminderLogs'])->name('events.reminder-logs');

    Route::get('/events-calendar',[EventEvaluationController::class,'calendar'])->name('events.calendar');
    Route::get('/events-meal-report',[EventEvaluationController::class,'mealReport'])->name('events.meal-report');
    Route::get('/events-meal-report.csv',[EventEvaluationController::class,'mealCsv'])->name('events.meal-report.csv');
});

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/assignments',[CourseAssignmentController::class,'index'])
        ->middleware('permission:courses.edit')->name('assignments.index');

    Route::delete('/assignments/bulk-delete',[CourseAssignmentController::class,'bulkDestroy'])
        ->middleware('permission:courses.delete')->name('assignments.bulk-destroy');

    // IMPORTANT:
    // Do not call this route files.index because the application already has a
    // per-course route with that name: /admin/elearning/courses/{course}/files.
    Route::get('/learning-files',[LearningFileAdminController::class,'index'])
        ->middleware('permission:courses.edit')->name('learning-files.index');

    Route::delete('/learning-files/bulk-delete',[LearningFileAdminController::class,'bulkDestroy'])
        ->middleware('permission:courses.edit')->name('learning-files.bulk-destroy');

    Route::delete('/learning-files/{file}',[LearningFileAdminController::class,'destroy'])
        ->middleware('permission:courses.edit')->name('learning-files.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function(){
    Route::get('/course-attendance-report',[CourseAttendanceReportController::class,'index'])
        ->name('course-attendance-report.index');

    Route::get('/course-attendance-report.csv',[CourseAttendanceReportController::class,'csv'])
        ->name('course-attendance-report.csv');

    Route::get('/participant-attendance-summary',[CourseAttendanceReportController::class,'participant'])
        ->name('participant-attendance-summary.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function(){
    Route::get('/profile',[AdminProfileController::class,'edit'])->name('profile.edit');
    Route::put('/profile',[AdminProfileController::class,'update'])->name('profile.update');
    Route::put('/profile/password',[AdminProfileController::class,'password'])->name('profile.password');
});

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

        Route::delete('/certificates/bulk-delete', [CertificateIndexController::class, 'bulkDestroy'])
            ->name('certificates.bulk-destroy');
    });

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

Route::middleware(['auth', 'staff'])->prefix('it-support/tickets')->name('it-support.tickets.')->group(function () {
    Route::get('/', [SupportTicketQueueController::class, 'index'])->name('index');
    Route::get('/{ticket}', [SupportTicketQueueController::class, 'show'])->name('show');
    Route::patch('/{ticket}/status', [SupportTicketQueueController::class, 'status'])->name('status');
    Route::patch('/{ticket}/assignee', [SupportTicketQueueController::class, 'assignee'])->name('assignee');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureParticipantUser::class])->group(function () {
    Route::get('/support/tickets', [SupportTicketController::class, 'index'])
        ->name('participant.support-tickets.index');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])
        ->name('participant.support-tickets.show');
});

Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:course_calls.view')->group(function () {
        Route::get('/course-calls',[CourseCallController::class,'index'])->name('course-calls.index');
        Route::get('/course-calls/{courseCall}/applications',[CourseCallController::class,'applications'])->name('course-calls.applications');
    });

    Route::middleware('permission:course_calls.manage')->group(function () {
        Route::post('/course-calls',[CourseCallController::class,'store'])->name('course-calls.store');
        Route::put('/course-calls/{courseCall}',[CourseCallController::class,'update'])->name('course-calls.update');
        Route::delete('/course-calls/{courseCall}',[CourseCallController::class,'destroy'])->name('course-calls.destroy');
        Route::delete('/course-calls/bulk-delete',[CourseCallController::class,'bulkDestroy'])->name('course-calls.bulk-destroy');
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
        Route::delete('/surveys/bulk-delete',[SurveyController::class,'bulkDestroy'])->name('surveys.bulk-destroy');
        Route::get('/surveys/{survey}/builder',[SurveyController::class,'builder'])->name('surveys.builder');
        Route::post('/surveys/{survey}/sections',[SurveyController::class,'addSection'])->name('surveys.sections.store');
        Route::post('/surveys/{survey}/questions',[SurveyController::class,'addQuestion'])->name('surveys.questions.store');
        Route::delete('/surveys/{survey}/questions/{question}',[SurveyController::class,'destroyQuestion'])->name('surveys.questions.destroy');
        Route::put('/surveys/{survey}/scoring',[SurveyController::class,'updateScoring'])->name('surveys.scoring');
    });

    /*
     * IMPORTANT:
     * Use the controller index, not Route::view().
     * The hardened settings page requires $settings and $backups.
     */
    Route::get('/platform-settings',[PlatformSettingsController::class,'index'])
        ->middleware('permission:settings.manage')
        ->name('platform-settings.index');
    Route::put('/platform-settings/ai', [PlatformSettingsController::class, 'updateAi'])->middleware('permission:settings.manage')->name('platform-settings.ai');
    Route::post('/platform-settings/ai-test', [PlatformSettingsController::class, 'testAi'])->middleware('permission:settings.manage')->name('platform-settings.ai-test');

    Route::put('/platform-settings/branding',[PlatformSettingsController::class,'updateBranding'])
        ->middleware('permission:settings.branding')
        ->name('platform-settings.branding');

    Route::put('/platform-settings/maintenance',[PlatformSettingsController::class,'updateMaintenance'])
        ->middleware('permission:settings.maintenance')
        ->name('platform-settings.maintenance');

    Route::post('/platform-settings/backup-now',[PlatformSettingsController::class,'backupNow'])
        ->middleware('permission:settings.backups')
        ->name('platform-settings.backup-now');

    Route::put('/platform-settings/admissions',[PlatformSettingsController::class,'updateAdmissions'])
        ->middleware('permission:settings.manage')
        ->name('platform-settings.admissions');
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

Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
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

/* Course-call detail/edit routes */
Route::middleware(['auth', 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/course-calls/{courseCall}', [CourseCallController::class, 'show'])
            ->middleware('permission:course_calls.view')
            ->name('course-calls.show');

        Route::get('/course-calls/{courseCall}/edit', [CourseCallController::class, 'edit'])
            ->middleware('permission:course_calls.manage')
            ->name('course-calls.edit');
    });

Route::middleware(['auth', 'staff'])
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

/* Participant Help & Support */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/participant/help', [\App\Http\Controllers\Participant\HelpController::class, 'index'])->name('participant.help');
});
Route::prefix('admin')->name('admin.')->middleware(['auth','staff','permission:settings.manage','role:administrator,super-administrator,super-admin'])->group(function () {
    Route::get('/support-settings', [\App\Http\Controllers\Admin\SupportSettingsController::class, 'edit'])->name('support-settings.edit');
    Route::put('/support-settings', [\App\Http\Controllers\Admin\SupportSettingsController::class, 'update'])->name('support-settings.update');
});
Route::prefix('admin/elearning/certificates/templates')
    ->name('admin.elearning.certificates.templates.')
    ->middleware([
        'auth',
        'staff',
        'permission:courses.edit',
        'role:administrator,super-administrator,super-admin',
    ])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'templates'])
            ->name('index');

        Route::post('/', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'storeTemplate'])
            ->name('store');

        Route::get('/{template}/design', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'designTemplate'])
            ->name('design');

        Route::put('/{template}/design', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'updateTemplateLayout'])
            ->name('design.update');

        Route::get('/{template}/preview', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'previewTemplate'])
            ->name('preview');

        Route::patch('/{template}/toggle', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'toggleTemplate'])
            ->name('toggle');

        Route::delete('/{template}', [\App\Http\Controllers\Admin\Elearning\CertificateAdminController::class,'destroyTemplate'])
            ->name('destroy');
    });
Route::middleware(['auth','staff','role:instructor,trainer,administrator,super-administrator,super-admin'])
    ->prefix('instructor/courses')->name('instructor.courses.')
    ->group(function () {
        Route::get('/{course}/manage', [\App\Http\Controllers\Instructor\CourseManagementController::class,'show'])->name('manage');
        Route::post('/{course}/timetable', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'store'])->name('timetable.store');
        Route::put('/{course}/timetable/{slot}', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'update'])->name('timetable.update');
        Route::delete('/{course}/timetable/{slot}', [\App\Http\Controllers\Instructor\CourseTimetableController::class, 'destroy'])->name('timetable.destroy');
        Route::post('/{course}/modules', [\App\Http\Controllers\Instructor\CourseManagementController::class,'storeModule'])->name('modules.store');
        Route::delete('/{course}/modules/{module}', [\App\Http\Controllers\Instructor\CourseManagementController::class,'destroyModule'])->name('modules.destroy');
        Route::post('/{course}/modules/{module}/lessons', [\App\Http\Controllers\Instructor\CourseManagementController::class,'storeLesson'])->name('lessons.store');
        Route::delete('/{course}/modules/{module}/lessons/{lesson}', [\App\Http\Controllers\Instructor\CourseManagementController::class,'destroyLesson'])->name('lessons.destroy');
        Route::post('/{course}/assessments', [\App\Http\Controllers\Instructor\CourseManagementController::class,'storeAssessment'])->name('assessments.store');
        Route::post('/{course}/assessments/{assessment}/questions', [\App\Http\Controllers\Instructor\CourseManagementController::class,'addQuestion'])->name('assessments.questions.store');
        Route::delete('/{course}/assessments/{assessment}/questions/{question}', [\App\Http\Controllers\Instructor\CourseManagementController::class,'destroyQuestion'])->name('assessments.questions.destroy');
        Route::put('/{course}/participants/{enrolment}', [\App\Http\Controllers\Instructor\CourseManagementController::class,'updateParticipant'])->name('participants.update');
    });
/*
|--------------------------------------------------------------------------
| Instructor / Trainer My Courses
|--------------------------------------------------------------------------
| Staff enter through /admin/dashboard. Legacy /instructor/dashboard
| redirects to the shared dashboard.
*/
Route::get('/admin/my-courses', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'myCourses'])
    ->middleware(['auth','staff','role:instructor,trainer,administrator,super-administrator,super-admin'])
    ->name('admin.my-courses');

/*
|--------------------------------------------------------------------------
| Instructor appointments
|--------------------------------------------------------------------------
| Participants request a time with an instructor of one of their courses
| (participant side: /appointments); the instructor approves, declines or
| proposes another time here. Rules live in App\Services\AppointmentService.
*/
Route::middleware(['auth','staff','role:instructor,trainer,administrator,super-administrator,super-admin'])
    ->prefix('admin/appointments')
    ->name('instructor.appointments.')
    ->controller(\App\Http\Controllers\Instructor\AppointmentController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{appointment}/approve', 'approve')->whereNumber('appointment')->name('approve');
        Route::post('/{appointment}/decline', 'decline')->whereNumber('appointment')->name('decline');
        Route::post('/{appointment}/propose', 'propose')->whereNumber('appointment')->name('propose');
        Route::post('/{appointment}/cancel', 'cancel')->whereNumber('appointment')->name('cancel');
        Route::post('/{appointment}/complete', 'complete')->whereNumber('appointment')->name('complete');
    });

Route::middleware(['auth','verified',\App\Http\Middleware\EnsureParticipantUser::class])
    ->prefix('appointments')
    ->name('appointments.')
    ->controller(\App\Http\Controllers\Participant\AppointmentController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:20,1')->name('store');
        Route::post('/{appointment}/accept', 'accept')->whereNumber('appointment')->name('accept');
        Route::post('/{appointment}/decline-proposal', 'declineProposal')->whereNumber('appointment')->name('decline-proposal');
        Route::post('/{appointment}/cancel', 'cancel')->whereNumber('appointment')->name('cancel');
    });
Route::middleware(['auth','staff','role:instructor,trainer,administrator,super-administrator,super-admin'])
    ->group(function () {
        Route::get(
            '/instructor/courses/{course}/modules/{module}/lessons/{lesson}/file',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'downloadLessonFile']
        )->name('instructor.courses.lessons.file');

        Route::get(
            '/instructor/courses/{course}/assessments/{assessment}/file',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'downloadAssessmentFile']
        )->name('instructor.courses.assessments.file');

        Route::delete(
            '/instructor/courses/{course}/files/{file}',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'destroyFile']
        )->name('instructor.courses.files.destroy');
    });
Route::middleware(['auth','staff','role:instructor,trainer,administrator,super-administrator,super-admin'])
    ->prefix('instructor/courses')
    ->group(function () {
        Route::put(
            '/{course}',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'updateCourse']
        )->name('instructor.courses.update');

        Route::post(
            '/{course}/announcements',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'storeAnnouncement']
        )->name('instructor.courses.announcements.store');

        Route::delete(
            '/{course}/announcements/{announcement}',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'destroyAnnouncement']
        )->name('instructor.courses.announcements.destroy');

        Route::put(
            '/{course}/submissions/{attempt}/review',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'reviewSubmission']
        )->name('instructor.courses.submissions.review');

        Route::get(
            '/{course}/submissions/{attempt}/file',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'downloadSubmissionFile']
        )->name('instructor.courses.submissions.file');

        Route::post(
            '/{course}/extension-requests/{extensionRequest}/approve',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'approveExtension']
        )->name('instructor.courses.extension-requests.approve');

        Route::post(
            '/{course}/extension-requests/{extensionRequest}/reject',
            [\App\Http\Controllers\Instructor\CourseManagementController::class,'rejectExtension']
        )->name('instructor.courses.extension-requests.reject');
    });

/*
|--------------------------------------------------------------------------
| Instructor / Trainer Course Management Extensions
|--------------------------------------------------------------------------
*/
Route::middleware([
        'auth',
        'staff',
        'role:instructor,trainer,administrator,super-administrator,super-admin',
    ])
    ->prefix('instructor/courses')
    ->name('instructor.courses.')
    ->group(function () {
        Route::put('/{course}/modules/{module}', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'updateModule',
        ])->name('modules.update');

        Route::put('/{course}/modules/{module}/lessons/{lesson}', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'updateLesson',
        ])->name('lessons.update');

        Route::put('/{course}/assessments/{assessment}', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'updateAssessment',
        ])->name('assessments.update');

        Route::delete('/{course}/assessments/{assessment}', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'destroyAssessment',
        ])->name('assessments.destroy');

        Route::get('/{course}/participants/{enrolment}/progress', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'participantProgress',
        ])->name('participants.progress');

        Route::get('/{course}/progress.csv', [
            \App\Http\Controllers\Instructor\CourseManagementController::class,
            'exportProgress',
        ])->name('progress.export');
    });

if (! \Illuminate\Support\Facades\Route::has('offline')) {
    Route::view('/offline', 'offline')->name('offline');
}

require __DIR__ . '/workspaces.php';

/*
|--------------------------------------------------------------------------
| Certificate recommendations
|--------------------------------------------------------------------------
|
| Instructors, trainers and programme staff recommend participants for course
| or event certificates; administrators approve (issuing them) or reject.
| Finer access rules live in CertificateRecommendationService.
|
*/

Route::middleware(['auth', 'staff', 'role:'.implode(',', array_merge(
        \App\Services\CertificateRecommendationService::RECOMMENDER_ROLES,
        \App\Services\CertificateRecommendationService::APPROVER_ROLES,
    ))])
    ->prefix('certificates/recommendations')
    ->name('certificates.recommendations.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Certificates\CertificateRecommendationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Certificates\CertificateRecommendationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Certificates\CertificateRecommendationController::class, 'store'])->name('store');
        Route::delete('/bulk-delete', [\App\Http\Controllers\Certificates\CertificateRecommendationController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/review', [\App\Http\Controllers\Certificates\CertificateRecommendationController::class, 'review'])->name('review');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('/my-certificates', [CertificateController::class, 'index'])->name('certificates.mine');
    Route::get('/my-certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::prefix('my-certificates/files')->where(['type'=>'course|event','id'=>'[0-9]+'])->group(function () {
        Route::get('/{type}/{id}/preview', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'preview'])->name('certificates.file.preview');
        Route::get('/{type}/{id}/download', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'download'])->name('certificates.file.download');
        Route::post('/{type}/{id}/share', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'share'])->name('certificates.file.share');
    });
});

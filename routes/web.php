<?php

use App\Http\Controllers\Admin\ActivityLinkController;
use App\Http\Controllers\Admin\ClassroomGameController as AdminClassroomGameController;
use App\Http\Controllers\Admin\AgeGroupController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategoryLandingPageController;
use App\Http\Controllers\Admin\ClassRecordingController;
use App\Http\Controllers\Admin\CommunityContentController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\PopupBannerController;
use App\Http\Controllers\Admin\CourseApprovalController;
use App\Http\Controllers\Admin\EmailAutomationController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\CourseReviewController as AdminCourseReviewController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\LoginHistoryController;
use App\Http\Controllers\Admin\MeetContactAlertController;
use App\Http\Controllers\Admin\OpsNpsController;
use App\Http\Controllers\Admin\OpsRosterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\TutorPipelineController;
use App\Http\Controllers\Admin\StudentKanbanController;
use App\Http\Controllers\Admin\TutorTrainingAdminController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CampBookingController;
use App\Http\Controllers\CategoryLandingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseCatalogController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomEnrollmentController;
use App\Http\Controllers\EnrollmentCertificateController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MeetContactAlertWebhookController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Parent\ReferralController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\SecureAssetController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\CheckoutController;
use App\Http\Controllers\Student\CoursePlayerController;
use App\Http\Controllers\Student\PaymentCallbackController;
use App\Http\Controllers\Student\RecordingController;
use App\Http\Controllers\Student\ScheduleWizardController;
use App\Http\Controllers\Student\SessionBookingController;
use App\Http\Controllers\Student\SkillTreeController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TrialClassController;
use App\Http\Controllers\Tutor\AnnouncementController;
use App\Http\Controllers\Tutor\AvailabilityController;
use App\Http\Controllers\Tutor\BrandStorefrontController;
use App\Http\Controllers\Tutor\ClassroomController;
use App\Http\Controllers\Tutor\CoPilotController;
use App\Http\Controllers\Tutor\CourseController;
use App\Http\Controllers\Tutor\DirectMessageController;
use App\Http\Controllers\Tutor\LiveSessionController;
use App\Http\Controllers\Tutor\OnboardingPolicyController;
use App\Http\Controllers\Tutor\QaController;
use App\Http\Controllers\Tutor\ResourceLibraryController;
use App\Http\Controllers\Tutor\RewardController;
use App\Http\Controllers\Tutor\TutorController;
use App\Http\Middleware\EnsureEnrolled;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Unslop design previews only — do not affect live home/admin.
Route::view('/test', 'previews.test-home')->name('previews.home');
Route::view('/testdashboard', 'previews.test-dashboard')->name('previews.dashboard');

// Fresh CSRF token for edge-cached public pages (see CachePublicPage middleware).
Route::get('/csrf-token', function (Request $request) {
    return response()
        ->json(['token' => csrf_token()])
        ->header('Cache-Control', 'no-store, private');
})->name('csrf.token');

// Playwright-only OTP bridge (disabled unless E2E_SECRET is set).
Route::get('/e2e/admin-otp', function (Request $request) {
    $secret = (string) config('app.e2e_secret');
    abort_unless($secret !== '' && hash_equals($secret, (string) $request->header('X-E2E-Secret')), 404);

    $email = strtolower(trim((string) $request->query('email', '')));
    abort_unless($email !== '', 404);

    $otp = \Illuminate\Support\Facades\Cache::get('e2e.admin_otp.email.'.$email);
    abort_unless(is_string($otp) && $otp !== '', 404);

    return response()
        ->json(['otp' => $otp])
        ->header('Cache-Control', 'no-store, private');
})->middleware('throttle:20,1')->name('e2e.admin-otp');

// Secure Asset Delivery Route
Route::get('/secure-asset/{path}', [SecureAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('secure.asset');

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.stripe');

Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.paypal');

Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.razorpay');

Route::post('/webhooks/meet/contact-alert', MeetContactAlertWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.meet.contact-alert');

// Currency / locale
Route::post('/currency/switch', [CurrencyController::class, 'switch'])->name('currency.switch');
Route::post('/locale/update', [CurrencyController::class, 'updateLocale'])->name('locale.update');

Route::get('/api/courses/suggest', [CourseCatalogController::class, 'suggest'])->name('courses.suggest');
Route::post('/booking/razorpay/{booking}', [CampBookingController::class, 'createRazorpayOrder'])->name('booking.razorpay.create');
Route::post('/booking/razorpay/{booking}/verify', [CampBookingController::class, 'verifyRazorpay'])->name('booking.razorpay.verify');
Route::get('/book-free-session', [TrialClassController::class, 'show'])->name('book-free-session');

// Camp Booking
Route::get('/api/consultation-slots', [CampBookingController::class, 'getAvailableSlots'])->name('booking.consultation.slots');
Route::post('/book-consultation', [CampBookingController::class, 'storeConsultation'])->middleware('throttle:15,1')->name('booking.consultation.store');
Route::post('/book-class', [CampBookingController::class, 'store'])->name('book-class.store');

// Camp booking checkout/pay are guest flows (parent books without an account);
// booking ID is the access token, same model as the public booking.success page.
Route::get('/booking/checkout/{booking}', [CampBookingController::class, 'checkout'])->name('booking.checkout');
Route::post('/booking/pay/{booking}', [CampBookingController::class, 'pay'])->name('booking.pay');
Route::post('/booking/paypal/{booking}', [CampBookingController::class, 'createPaypalOrder'])->name('booking.paypal.create');
Route::get('/booking/paypal/{booking}/return', [CampBookingController::class, 'paypalReturn'])->name('booking.paypal.return');

Route::middleware(['auth:web'])->group(function () {
    // Web Push Subscriptions
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('push.unsubscribe');
});

Route::get('/booking/success/{booking}', [CampBookingController::class, 'success'])->name('booking.success');

// Parent “can't join” note from class/trial emails → support@younimatiq.com
Route::get('/session-note', [\App\Http\Controllers\SessionNoteController::class, 'show'])->name('session-note.show');
Route::post('/session-note', [\App\Http\Controllers\SessionNoteController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('session-note.store');

// Chatbot API
Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

// Sitemap & robots
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap', [SitemapController::class, 'html'])->name('sitemap.html');
Route::get('/robots.txt', [SitemapController::class, 'robots']);

Route::get('/geo/country', [GeoController::class, 'country'])->name('geo.country');

// Password Reset
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Admin Auth
Route::get('/unsubscribe', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('/unsubscribe', [UnsubscribeController::class, 'store'])->name('unsubscribe.store')->middleware('throttle:20,1');

Route::get('/login/admin', [LoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::post('/login/admin', [LoginController::class, 'adminLogin'])->middleware('throttle:10,1');
Route::get('/login/admin/otp', [LoginController::class, 'showAdminOtpForm'])->name('login.admin.otp');
Route::post('/login/admin/otp', [LoginController::class, 'verifyAdminOtp'])->name('login.admin.otp.verify')->middleware('throttle:10,1');
Route::post('/login/admin/otp/resend', [LoginController::class, 'resendAdminOtp'])->name('login.admin.otp.resend')->middleware('throttle:5,1');
Route::post('/logout/admin', [LoginController::class, 'adminLogout'])->name('logout.admin');
Route::get('/admin', [LoginController::class, 'adminIndex']);
Route::get('/admin/', [LoginController::class, 'adminIndex']);

// Tutor Auth
Route::get('/login/tutor', [LoginController::class, 'showTutorLoginForm'])->name('login.tutor');
Route::post('/login/tutor', [LoginController::class, 'tutorLogin']);
Route::post('/logout/tutor', [LoginController::class, 'tutorLogout'])->name('logout.tutor');

// Student Auth
Route::get('/login/student', [LoginController::class, 'showStudentLoginForm'])->name('login.student');
Route::post('/login/student', [LoginController::class, 'studentLogin']);
Route::post('/logout/student', [LoginController::class, 'studentLogout'])->name('logout.student');

// Parent Auth
Route::get('/login/parent', [LoginController::class, 'showParentLoginForm'])->name('login.parent');
Route::post('/login/parent', [LoginController::class, 'parentLogin']);
Route::post('/logout/parent', [LoginController::class, 'parentLogout'])->name('logout.parent');

// Registration Routes
Route::get('/register', [RegisterController::class, 'redirectToStudent'])->name('register');
Route::get('/register/student', [RegisterController::class, 'showStudentRegisterForm'])->name('register.student');
Route::post('/register/student', [RegisterController::class, 'registerStudent']);
Route::get('/register/tutor', [RegisterController::class, 'showTutorRegisterForm'])->name('register.tutor');
Route::post('/register/tutor', [RegisterController::class, 'registerTutor'])->middleware('throttle:10,1');
// Pending approval page lives on the public Vite shell (same as /register/tutor)
// so Inertia can redirect without a cross-bundle hard navigation hang.
Route::get('/register/tutor/pending', fn () => \App\Support\InertiaPage::react('Auth/TutorPending'))
    ->middleware('auth:tutor')->name('tutor.pending');
Route::redirect('/tutor/pending', '/register/tutor/pending');
Route::get('/register/parent', [RegisterController::class, 'showParentRegisterForm'])->name('register.parent');
Route::post('/register/parent', [RegisterController::class, 'registerParent'])->middleware('throttle:10,1');
Route::redirect('/apply', '/register/tutor');

// Email Verification (students)
Route::middleware(['auth:web'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// Notifications (all authenticated roles)
Route::middleware(['auth:web,admin,tutor'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
});

// Secure Tip Streaming (accessible by authenticated admins and students)
Route::middleware(['auth:web,admin,tutor'])->get('/tips/{tip}/stream', [TipController::class, 'stream'])->name('tips.stream');

// Protected Admin Routes
Route::middleware(['auth:admin', 'active:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login-history', [LoginHistoryController::class, 'index'])->name('login-history.index');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/feedback/parent/{parentFeedback}', [FeedbackController::class, 'showParent'])->name('feedback.parent.show');
    Route::put('/feedback/parent/{parentFeedback}', [FeedbackController::class, 'updateParent'])->name('feedback.parent.update');
    Route::get('/feedback/student/{studentFeedback}', [FeedbackController::class, 'showStudent'])->name('feedback.student.show');
    Route::put('/feedback/student/{studentFeedback}', [FeedbackController::class, 'updateStudent'])->name('feedback.student.update');
    Route::get('/feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');
    Route::put('/feedback/{feedback}', [FeedbackController::class, 'update'])->name('feedback.update');
    Route::post('/feedback/{feedback}/send-to-parent', [FeedbackController::class, 'sendToParent'])->name('feedback.send-to-parent');
    Route::get('/reviews', [AdminCourseReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminCourseReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/ops/roster', [OpsRosterController::class, 'index'])->name('ops.roster');
    Route::get('/ops/nps', [OpsNpsController::class, 'index'])->name('ops.nps');
    Route::get('/recordings', [ClassRecordingController::class, 'index'])->name('recordings.index');
    Route::post('/recordings/assign', [ClassRecordingController::class, 'assign'])->name('recordings.assign');
    Route::post('/recordings/requests/{recordingRequest}/reject', [ClassRecordingController::class, 'rejectRequest'])->name('recordings.requests.reject');
    Route::get('/meet-alerts', [MeetContactAlertController::class, 'index'])->name('meet-alerts.index');
    Route::post('/meet-alerts/{alert}/reviewed', [MeetContactAlertController::class, 'markReviewed'])->name('meet-alerts.reviewed');
    Route::get('/students', [StudentKanbanController::class, 'index'])->name('students');
    Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
    Route::post('/students/{user}/status', [StudentKanbanController::class, 'updateStatus'])->name('students.status');
    Route::get('/tutors', [TutorPipelineController::class, 'index'])->name('tutors');
    Route::post('/tutors', [AdminController::class, 'storeTutor'])->name('tutors.store');
    Route::get('/tutors/{user}/json', [TutorPipelineController::class, 'show'])->name('tutors.json');
    Route::post('/tutors/{user}/pipeline', [TutorPipelineController::class, 'updateStatus'])->name('tutors.pipeline');
    Route::post('/tutors/{user}/assign-training', [TutorPipelineController::class, 'assignTraining'])->name('tutors.assign-training');
    Route::post('/tutors/{user}/assign-skills', [TutorPipelineController::class, 'assignSkills'])->name('tutors.assign-skills');

    Route::get('/tutor-training', [TutorTrainingAdminController::class, 'index'])->name('tutor-training.index');
    Route::post('/tutor-training/modules', [TutorTrainingAdminController::class, 'storeModule'])->name('tutor-training.modules.store');
    Route::delete('/tutor-training/modules/{module}', [TutorTrainingAdminController::class, 'destroyModule'])->name('tutor-training.modules.destroy');
    Route::post('/tutor-training/modules/{module}/items', [TutorTrainingAdminController::class, 'storeItem'])->name('tutor-training.items.store');
    Route::post('/tutor-training/modules/{module}/assign', [TutorTrainingAdminController::class, 'assignModule'])->name('tutor-training.modules.assign');
    Route::get('/tutor-training/reviews', [\App\Http\Controllers\Admin\TutorTrainingReviewController::class, 'index'])->name('tutor-training.reviews');
    Route::post('/tutor-training/reviews/{progress}/approve', [\App\Http\Controllers\Admin\TutorTrainingReviewController::class, 'approve'])->name('tutor-training.reviews.approve');
    Route::post('/tutor-training/reviews/{progress}/reject', [\App\Http\Controllers\Admin\TutorTrainingReviewController::class, 'reject'])->name('tutor-training.reviews.reject');
    Route::post('/tutor-training/reviews/{progress}/revision', [\App\Http\Controllers\Admin\TutorTrainingReviewController::class, 'requestRevision'])->name('tutor-training.reviews.revision');

    Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
    Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
    Route::put('/skills/{skill}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');
    Route::post('/skills/courses/{course}/sync', [SkillController::class, 'syncCourse'])->name('skills.courses.sync');
    Route::post('/skills/categories/{category}/sync', [SkillController::class, 'syncCategory'])->name('skills.categories.sync');
    Route::post('/skills/match-settings', [SkillController::class, 'updateMatchSettings'])->name('skills.match-settings');
    Route::get('/match-suggestions', [\App\Http\Controllers\Admin\MatchSuggestionController::class, 'index'])->name('match-suggestions.index');
    Route::post('/match-suggestions/{suggestion}/assign', [\App\Http\Controllers\Admin\MatchSuggestionController::class, 'assign'])->name('match-suggestions.assign');
    Route::post('/match-suggestions/{suggestion}/dismiss', [\App\Http\Controllers\Admin\MatchSuggestionController::class, 'dismiss'])->name('match-suggestions.dismiss');
    Route::get('/bundles', [\App\Http\Controllers\Admin\CourseBundleController::class, 'index'])->name('bundles.index');
    Route::post('/bundles', [\App\Http\Controllers\Admin\CourseBundleController::class, 'store'])->name('bundles.store');
    Route::put('/bundles/{bundle}', [\App\Http\Controllers\Admin\CourseBundleController::class, 'update'])->name('bundles.update');
    Route::delete('/bundles/{bundle}', [\App\Http\Controllers\Admin\CourseBundleController::class, 'destroy'])->name('bundles.destroy');
    Route::post('/parents/link', [\App\Http\Controllers\Admin\ParentLinkController::class, 'store'])->name('parents.link');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/json', [CategoryController::class, 'json'])->name('categories.json');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/age-groups', [AgeGroupController::class, 'index'])->name('age-groups');
    Route::post('/age-groups', [AgeGroupController::class, 'store'])->name('age-groups.store');
    Route::put('/age-groups/{ageGroup}', [AgeGroupController::class, 'update'])->name('age-groups.update');
    Route::delete('/age-groups/{ageGroup}', [AgeGroupController::class, 'destroy'])->name('age-groups.destroy');

    Route::get('/platform-settings', [SettingController::class, 'index'])->name('platform-settings');
    Route::post('/platform-settings', [SettingController::class, 'store'])->name('platform-settings.store');

    // Tips & Tricks
    Route::get('/tips', [TipController::class, 'index'])->name('tips');
    Route::post('/tips/folder', [TipController::class, 'storeFolder'])->name('tips.folder.store');
    Route::delete('/tips/folder/{folder}', [TipController::class, 'destroyFolder'])->name('tips.folder.destroy');
    Route::post('/tips/item', [TipController::class, 'storeItem'])->name('tips.item.store');
    Route::delete('/tips/item/{tip}', [TipController::class, 'destroyItem'])->name('tips.item.destroy');
    Route::post('/tips/assign', [TipController::class, 'assign'])->name('tips.assign');
    Route::post('/tips/multi-assign', [TipController::class, 'assign'])->name('tips.multi-assign');
    Route::post('/tips/bulk-delete', [TipController::class, 'bulkDelete'])->name('tips.bulk-delete');
    Route::post('/tips/bulk-move', [TipController::class, 'bulkMove'])->name('tips.bulk-move');

    // Old bookmark: GET /admin/settings → platform settings.
    // Must be GET-only — Route::redirect() uses ANY and would steal POSTs from settings.update
    // (API/gateway/email/SEO/newsletter/pixel saves), discarding the form and redirecting away.
    Route::get('/settings', function () {
        return redirect()->route('admin.platform-settings');
    });

    Route::get('/courses/sample-csv', [CourseApprovalController::class, 'downloadSampleCsv'])->name('courses.sample-csv');
    Route::get('/courses/bulk-import/warnings', [CourseApprovalController::class, 'downloadImportWarnings'])->name('courses.bulk-import.warnings');
    Route::post('/courses/bulk-import/preview', [CourseApprovalController::class, 'previewBulkImport'])->name('courses.bulk-preview');
    Route::post('/courses/bulk-import', [CourseApprovalController::class, 'bulkImport'])->name('courses.bulk-import');
    Route::get('/courses/create', [CourseApprovalController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseApprovalController::class, 'store'])->name('courses.store');
    Route::get('/courses', [CourseApprovalController::class, 'index'])->name('courses');
    Route::get('/courses/{course}/edit', [CourseApprovalController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseApprovalController::class, 'update'])->name('courses.update');
    Route::post('/courses/{course}/import-syllabus', [CourseApprovalController::class, 'importSyllabus'])->name('courses.import-syllabus');
    Route::patch('/courses/{course}/status', [CourseApprovalController::class, 'updateStatus'])->name('courses.status');
    Route::patch('/courses/{course}/toggle-active', [CourseApprovalController::class, 'toggleActive'])->name('courses.toggle-active');
    Route::delete('/courses/{course}', [CourseApprovalController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/units', [CourseApprovalController::class, 'storeUnit'])->name('courses.units.store');
    Route::post('/courses/{course}/custom-link', [CustomEnrollmentController::class, 'generateLink'])->name('courses.custom-link');
    Route::put('/units/{unit}', [CourseApprovalController::class, 'updateUnit'])->name('courses.units.update');
    Route::delete('/units/{unit}', [CourseApprovalController::class, 'destroyUnit'])->name('courses.units.destroy');

    Route::post('/courses/{course}/modules', [CourseApprovalController::class, 'storeModule'])->name('courses.modules.store');
    Route::put('/modules/{module}', [CourseApprovalController::class, 'updateModule'])->name('courses.modules.update');
    Route::delete('/modules/{module}', [CourseApprovalController::class, 'destroyModule'])->name('courses.modules.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::post('/orders/{payment}/refund', [OrderController::class, 'refund'])->name('orders.refund');

    // Assignments
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/{submission}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');

    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::post('/bookings/{booking}/assign-tutor', [EnrollmentController::class, 'assignTutor'])->name('bookings.assign-tutor');
    Route::post('/bookings/{booking}/auto-assign-tutor', [EnrollmentController::class, 'autoAssignTutor'])->name('bookings.auto-assign-tutor');
    Route::post('/bookings/{booking}/update-schedule', [EnrollmentController::class, 'updateSchedule'])->name('bookings.update-schedule');
    Route::post('/camp-bookings/{campBooking}/assign-tutor', [EnrollmentController::class, 'assignCampTutor'])->name('camp-bookings.assign-tutor');
    Route::post('/camp-bookings/{campBooking}/update-schedule', [EnrollmentController::class, 'updateCampSchedule'])->name('camp-bookings.update-schedule');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
    Route::post('/enrollments/{enrollment}/send-email', [EnrollmentController::class, 'sendEnrollmentEmail'])->name('enrollments.send_email');
    Route::post('/enrollments/{enrollment}/certificate', [EnrollmentCertificateController::class, 'upload'])->name('enrollments.certificate.upload');

    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations');
    Route::post('/integrations/test', [IntegrationController::class, 'test'])->name('integrations.test');

    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons');
    Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

    Route::get('/popup-banners', [PopupBannerController::class, 'index'])->name('popup-banners.index');
    Route::post('/popup-banners', [PopupBannerController::class, 'store'])->name('popup-banners.store');
    Route::put('/popup-banners/{popupBanner}', [PopupBannerController::class, 'update'])->name('popup-banners.update');
    Route::delete('/popup-banners/{popupBanner}', [PopupBannerController::class, 'destroy'])->name('popup-banners.destroy');

    Route::resource('quizzes', QuizController::class)->except('show');
    Route::resource('blogs', App\Http\Controllers\Admin\BlogController::class)->except('show');
    Route::resource('pages', App\Http\Controllers\Admin\PageController::class)->except('show');
    Route::get('categories/{category}/landing-page', [CategoryLandingPageController::class, 'edit'])->name('categories.landing-page');
    Route::put('categories/{category}/landing-page', [CategoryLandingPageController::class, 'update'])->name('categories.landing-page.update');

    Route::get('/activity-links', [ActivityLinkController::class, 'index'])->name('activity-links.index');
    Route::post('/activity-links/games/{game}/assign', [AdminClassroomGameController::class, 'assign'])->name('activity-links.assign');
    Route::post('/activity-links/{activityLink}/assign-tutors', [ActivityLinkController::class, 'assignTutors'])->name('activity-links.assign-tutors');
    Route::get('/activity-links/games/{game}/preview', [AdminClassroomGameController::class, 'preview'])->name('activity-links.preview');
    Route::post('/activity-links/game-points', [ActivityLinkController::class, 'updateGamePoints'])->name('activity-links.game-points');
    Route::post('/activity-links', [ActivityLinkController::class, 'store'])->name('activity-links.store');
    Route::put('/activity-links/{activityLink}', [ActivityLinkController::class, 'update'])->name('activity-links.update');
    Route::delete('/activity-links/{activityLink}', [ActivityLinkController::class, 'destroy'])->name('activity-links.destroy');

    Route::get('/faqs', [FaqController::class, 'index'])->name('faqs');
    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

    Route::get('/community/projects', [CommunityContentController::class, 'projects'])->name('community.projects');
    Route::post('/community/projects', [CommunityContentController::class, 'storeProject'])->name('community.projects.store');
    Route::put('/community/projects/{project}', [CommunityContentController::class, 'updateProject'])->name('community.projects.update');
    Route::delete('/community/projects/{project}', [CommunityContentController::class, 'destroyProject'])->name('community.projects.destroy');

    Route::get('/community/testimonials', [CommunityContentController::class, 'testimonials'])->name('community.testimonials');
    Route::post('/community/testimonials', [CommunityContentController::class, 'storeTestimonial'])->name('community.testimonials.store');
    Route::put('/community/testimonials/{testimonial}', [CommunityContentController::class, 'updateTestimonial'])->name('community.testimonials.update');
    Route::delete('/community/testimonials/{testimonial}', [CommunityContentController::class, 'destroyTestimonial'])->name('community.testimonials.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

    // Impersonation
    Route::get('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate');

    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages');

    Route::get('/email', [EmailController::class, 'index'])->name('email.index');
    Route::get('/email/{template}/edit', [EmailController::class, 'edit'])->name('email.edit');
    Route::put('/email/{template}', [EmailController::class, 'update'])->name('email.update');
    Route::post('/email/{template}/test', [EmailController::class, 'testSend'])->name('email.test');

    Route::get('/email-automations', [EmailAutomationController::class, 'index'])->name('email-automations.index');
    Route::get('/email-automations/create', [EmailAutomationController::class, 'create'])->name('email-automations.create');
    Route::post('/email-automations', [EmailAutomationController::class, 'store'])->name('email-automations.store');
    Route::post('/email-automations/suppressions', [EmailAutomationController::class, 'addSuppression'])->name('email-automations.suppressions.store');
    Route::delete('/email-automations/suppressions/{suppression}', [EmailAutomationController::class, 'removeSuppression'])->name('email-automations.suppressions.destroy');
    Route::get('/email-automations/courses/{course}/preview', [EmailAutomationController::class, 'previewCourseTrigger'])->name('email-automations.course.preview');
    Route::post('/email-automations/courses/{course}/send', [EmailAutomationController::class, 'sendCourseTrigger'])->name('email-automations.course.send');
    Route::get('/email-automations/{emailAutomation}/edit', [EmailAutomationController::class, 'edit'])->name('email-automations.edit');
    Route::put('/email-automations/{emailAutomation}', [EmailAutomationController::class, 'update'])->name('email-automations.update');
    Route::delete('/email-automations/{emailAutomation}', [EmailAutomationController::class, 'destroy'])->name('email-automations.destroy');
    Route::get('/email-automations/{emailAutomation}/preview', [EmailAutomationController::class, 'preview'])->name('email-automations.preview');
    Route::post('/email-automations/{emailAutomation}/test', [EmailAutomationController::class, 'sendTest'])->name('email-automations.test');
    Route::post('/email-automations/{emailAutomation}/send-now', [EmailAutomationController::class, 'sendNow'])->name('email-automations.send-now');
    Route::get('/email-automations/{emailAutomation}/custom/preview', [EmailAutomationController::class, 'previewCustom'])->name('email-automations.custom.preview');
    Route::post('/email-automations/{emailAutomation}/custom/send', [EmailAutomationController::class, 'sendCustom'])->name('email-automations.custom.send');

    Route::patch('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/extend-trial', [UserManagementController::class, 'extendTrial'])->name('users.extend-trial');
    Route::post('/users/{user}/send-welcome', [UserManagementController::class, 'sendWelcomeEmail'])->name('users.send-welcome');

    Route::resource('chatbot', App\Http\Controllers\Admin\ChatbotController::class)->except('show');

    Route::get('/settings/gateways', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/pixel', [SettingsController::class, 'pixel'])->name('settings.pixel');
    Route::get('/settings/seo', [SettingsController::class, 'seo'])->name('settings.seo');
    Route::get('/settings/newsletter', [SettingsController::class, 'newsletter'])->name('settings.newsletter');
    Route::get('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->name('settings.email.test');
});

// Leave Impersonation (Available to web and tutor guards)
Route::middleware(['web'])->get('/impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');

// Tutor Portal Routes
Route::prefix('tutor')->name('tutor.')->middleware(['auth:tutor', 'active:tutor'])->group(function () {
    Route::get('/dashboard', [TutorController::class, 'dashboard'])->name('dashboard');
    Route::get('/feedback/create', [App\Http\Controllers\Tutor\FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [App\Http\Controllers\Tutor\FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/profile', [TutorController::class, 'profile'])->name('profile');
    Route::post('/profile', [TutorController::class, 'updateProfile'])->name('profile.update');
    Route::get('/assigned-classes', [CourseController::class, 'index'])->name('manage-courses');
    // Legacy/mistyped path used in older UI links
    Route::redirect('/manage-courses', '/tutor/assigned-classes', 301);

    // Tutor Course Creation
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/submit', [CourseController::class, 'submitForReview'])->name('courses.submit');

    // Tutor course content (units/modules) - delegate to Admin controller (already implemented)
    Route::post('/courses/{course}/units', [CourseApprovalController::class, 'storeUnit'])->name('courses.units.store');
    Route::post('/courses/{course}/modules', [CourseApprovalController::class, 'storeModule'])->name('courses.modules.store');

    Route::post('/availability', [AvailabilityController::class, 'store'])->name('availability.store');

    // Tutor Messages
    Route::get('/messages', [DirectMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [DirectMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/start', [DirectMessageController::class, 'start'])->name('messages.start');
    Route::post('/messages/{conversation}', [DirectMessageController::class, 'store'])->name('messages.store');

    // Tutor Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{announcement}/duplicate', [AnnouncementController::class, 'duplicate'])->name('announcements.duplicate');

    // Tutor Resource Library
    Route::get('/resources', [ResourceLibraryController::class, 'index'])->name('resources');

    // Tutor Policy Training
    Route::get('/policy-training', [OnboardingPolicyController::class, 'index'])->name('policy-training');
    Route::post('/policy-training/complete', [OnboardingPolicyController::class, 'completeModule'])->name('policy-training.complete');

    // Self-paced skill training (admin-assigned)
    Route::get('/training', [\App\Http\Controllers\Tutor\TrainingController::class, 'index'])->name('training.index');
    Route::post('/training/items/{item}/video', [\App\Http\Controllers\Tutor\TrainingController::class, 'completeVideo'])->name('training.video');
    Route::post('/training/items/{item}/document', [\App\Http\Controllers\Tutor\TrainingController::class, 'completeDocument'])->name('training.document');
    Route::post('/training/items/{item}/assignment', [\App\Http\Controllers\Tutor\TrainingController::class, 'submitAssignment'])->name('training.assignment');
    Route::post('/training/items/{item}/quiz', [\App\Http\Controllers\Tutor\TrainingController::class, 'submitQuiz'])->name('training.quiz');

    // Tutor Availability (GET + destroy)
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability');
    Route::delete('/availability/{availability}', [AvailabilityController::class, 'destroy'])->name('availability.destroy');

    Route::get('/classroom/{course}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::get('/classroom/{course}/copilot', [CoPilotController::class, 'show'])->name('copilot.show');
    Route::post('/classroom/{course}/copilot', [CoPilotController::class, 'updateStickingPoint'])->name('copilot.update');
    Route::post('/classroom/{course}/discussion', [ClassroomController::class, 'storeDiscussion'])->name('classroom.discussion.store');
    Route::post('/classroom/{course}/recording', [App\Http\Controllers\Tutor\ClassRecordingController::class, 'store'])->name('classroom.recording.store');
    Route::delete('/classroom/recording/{recording}', [App\Http\Controllers\Tutor\ClassRecordingController::class, 'destroy'])->name('classroom.recording.destroy');

    Route::get('/classes', [TutorController::class, 'classes'])->name('classes');
    Route::post('/class-prep/complete', [TutorController::class, 'completeClassPrep'])->name('class-prep.complete');
    Route::get('/bookings/{booking}/launch', [TutorController::class, 'launchBooking'])->name('bookings.launch');
    Route::get('/camp-bookings/{campBooking}/launch', [TutorController::class, 'launchCampBooking'])->name('camp-bookings.launch');
    Route::get('/sessions/{session}/launch', [TutorController::class, 'launchSession'])->name('sessions.launch');
    Route::get('/sessions', [LiveSessionController::class, 'index'])->name('sessions');
    Route::post('/sessions', [LiveSessionController::class, 'store'])->name('sessions.store');
    Route::get('/students', [TutorController::class, 'students'])->name('students');
    Route::get('/earnings', [TutorController::class, 'earnings'])->name('earnings');
    Route::get('/analytics', [\App\Http\Controllers\Tutor\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/payouts', [\App\Http\Controllers\Tutor\AnalyticsController::class, 'payouts'])->name('payouts');
    Route::post('/enrollments/{enrollment}/certificate', [EnrollmentCertificateController::class, 'uploadAsTutor'])->name('enrollments.certificate.upload');

    Route::get('/qa', [QaController::class, 'index'])->name('qa');
    Route::post('/qa/{question}/answer', [QaController::class, 'answer'])->name('qa.answer');

    // Class rewards: XP compliments, level-up tips and badges during class
    Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
    Route::post('/rewards/badges', [RewardController::class, 'storeBadge'])->name('rewards.badges.store');
    Route::get('/assignments', [App\Http\Controllers\Tutor\AssignmentController::class, 'index'])->name('assignments');
    Route::post('/assignments', [App\Http\Controllers\Tutor\AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/submissions/{submission}/grade', [App\Http\Controllers\Tutor\AssignmentController::class, 'grade'])->name('assignments.grade');
    Route::get('/quizzes', [App\Http\Controllers\Tutor\QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/create', [App\Http\Controllers\Tutor\QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [App\Http\Controllers\Tutor\QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}/edit', [App\Http\Controllers\Tutor\QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/quizzes/{quiz}', [App\Http\Controllers\Tutor\QuizController::class, 'update'])->name('quizzes.update');
    Route::get('/games', [App\Http\Controllers\Tutor\GameController::class, 'index'])->name('games.index');
    Route::post('/games', [App\Http\Controllers\Tutor\GameController::class, 'store'])->name('games.store');
    Route::post('/games/{game}/assign', [App\Http\Controllers\Tutor\GameController::class, 'assign'])->name('games.assign');
    Route::get('/games/{game}/preview', [App\Http\Controllers\Tutor\GameController::class, 'preview'])->name('games.preview');
    Route::delete('/games/uploads/{activityLink}', [App\Http\Controllers\Tutor\GameController::class, 'destroy'])->name('games.destroy');

    // AI teaching assistant + growth
    Route::get('/ai-assistant', [\App\Http\Controllers\Tutor\AiAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('/ai-assistant/parent-note', [\App\Http\Controllers\Tutor\AiAssistantController::class, 'draftParentNote'])->name('ai-assistant.parent-note');
    Route::post('/ai-assistant/recaps/{recap}', [\App\Http\Controllers\Tutor\AiAssistantController::class, 'reviewRecap'])->name('ai-assistant.recap');
    Route::post('/ai-assistant/project-review', [\App\Http\Controllers\Tutor\AiAssistantController::class, 'reviewProject'])->name('ai-assistant.project-review');
    Route::get('/growth', [\App\Http\Controllers\Tutor\GrowthController::class, 'index'])->name('growth.index');
    Route::post('/growth/leads/{lead}/nudge', [\App\Http\Controllers\Tutor\GrowthController::class, 'sendNudge'])->name('growth.nudge');
});

// Protected Parent Routes
Route::middleware(['auth:parent', 'active:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Parent\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/children/{child}/ai-consent', [\App\Http\Controllers\Parent\DashboardController::class, 'updateAiConsent'])->name('children.ai-consent');
    Route::get('/feedback', [\App\Http\Controllers\Parent\FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [\App\Http\Controllers\Parent\FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/reviews', [\App\Http\Controllers\Parent\ReviewController::class, 'index'])->name('reviews');
    Route::post('/reviews', [\App\Http\Controllers\Parent\ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/referrals', [\App\Http\Controllers\Parent\ReferralController::class, 'index'])->name('referrals');
});

// Protected Student Routes
Route::middleware(['auth:web', 'active:web', 'trial_access'])->prefix('student')->name('student.')->group(function () {
    Route::get('/trial-expired', fn () => \App\Support\InertiaPage::react('Student/TrialExpired'))->name('trial_expired');
    Route::get('/dashboard', [StudentDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/activities', [\App\Http\Controllers\Student\ActivitiesController::class, 'index'])->name('activities');
    Route::get('/python', [\App\Http\Controllers\Student\PythonLabController::class, 'show'])->name('python');
    Route::post('/python/run', [\App\Http\Controllers\Student\PythonLabController::class, 'run'])->middleware('throttle:10,1')->name('python.run');
    Route::get('/games/play/{token}', [\App\Http\Controllers\Student\ClassroomGameController::class, 'play'])
        ->middleware('throttle:60,1')
        ->name('games.play');
    Route::post('/games/{game}/play-token', [\App\Http\Controllers\Student\ClassroomGameController::class, 'issuePlayToken'])
        ->middleware('throttle:30,1')
        ->name('games.play-token');
    Route::post('/games/{game}/complete', [\App\Http\Controllers\Student\ClassroomGameController::class, 'complete'])
        ->middleware('throttle:30,1')
        ->name('games.complete');
    Route::get('/games/{game}', [\App\Http\Controllers\Student\ClassroomGameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/xp', [\App\Http\Controllers\Student\ClassroomGameController::class, 'claim'])->name('games.xp');
    Route::get('/rewards', [\App\Http\Controllers\Student\RewardsGalleryController::class, 'index'])->name('rewards');
    Route::get('/career', [\App\Http\Controllers\Student\CareerExplorerController::class, 'index'])->name('career');
    Route::get('/profile', [StudentProfileController::class, 'profile'])->name('profile');
    Route::post('/profile', [StudentProfileController::class, 'updateProfile'])->name('profile.update');
    Route::get('/courses', [App\Http\Controllers\Student\CourseCatalogController::class, 'index'])->name('courses');
    Route::get('/courses/{course}/player', [CoursePlayerController::class, 'show'])
        ->middleware(EnsureEnrolled::class)->name('courses.player');
    Route::get('/courses/{course}/schedule-wizard', [ScheduleWizardController::class, 'show'])
        ->middleware(EnsureEnrolled::class)->name('courses.schedule-wizard');
    Route::post('/courses/{course}/schedule-wizard', [ScheduleWizardController::class, 'store'])
        ->middleware(EnsureEnrolled::class)->name('courses.schedule-wizard.store');
    Route::get('/courses/{course}/skill-tree', [SkillTreeController::class, 'show'])->name('courses.skill-tree');
    Route::post('/courses/{course}/portfolio', [SkillTreeController::class, 'storePortfolio'])->name('courses.portfolio.store');
    Route::post('/courses/{course}/portfolio/{portfolio}/share', [SkillTreeController::class, 'sharePortfolio'])->name('courses.portfolio.share');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals');
    Route::get('/courses/{course}/certificate', [CertificateController::class, 'show'])
        ->middleware(EnsureEnrolled::class)->name('courses.certificate');
    Route::get('/courses/{course}/certificate/download', [EnrollmentCertificateController::class, 'download'])
        ->middleware(EnsureEnrolled::class)->name('courses.certificate.download');
    Route::post('/courses/{course}/modules/{moduleId}/complete', [CoursePlayerController::class, 'completeModule'])
        ->middleware(EnsureEnrolled::class)->name('courses.module.complete');
    Route::get('/assignments', [App\Http\Controllers\Student\AssignmentController::class, 'index'])->name('assignments');
    Route::get('/assignments/{assignment}/take', [App\Http\Controllers\Student\AssignmentController::class, 'take'])->name('assignments.take');
    Route::post('/assignments/{assignment}/submit', [App\Http\Controllers\Student\AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::get('/quizzes', [App\Http\Controllers\Student\QuizController::class, 'index'])->name('quizzes');
    Route::get('/quizzes/{quiz}/take', [App\Http\Controllers\Student\QuizController::class, 'take'])->name('quizzes.take');
    Route::get('/quizzes/{quiz}/results', [App\Http\Controllers\Student\QuizController::class, 'results'])->name('quizzes.results');
    Route::post('/quizzes/{quiz}/submit', [App\Http\Controllers\Student\QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('/bookings', [StudentDashboardController::class, 'bookings'])->name('bookings');
    Route::post('/bookings', [SessionBookingController::class, 'store'])->name('bookings.store');
    Route::get('/recordings', [RecordingController::class, 'index'])->name('recordings');
    Route::post('/recordings/{recording}/request', [RecordingController::class, 'requestAccess'])->name('recordings.request');
    Route::get('/recordings/{recording}/stream', [RecordingController::class, 'stream'])->name('recordings.stream');
    Route::redirect('/community', '/community')->name('community');
    Route::get('/wishlist', [StudentDashboardController::class, 'wishlist'])->name('wishlist');
    Route::post('/wishlist/{courseId}', [StudentProfileController::class, 'addWishlist'])->name('wishlist.add');
    Route::delete('/wishlist/{courseId}', [StudentProfileController::class, 'removeWishlist'])->name('wishlist.remove');
    Route::get('/payment-history', [StudentDashboardController::class, 'paymentHistory'])->name('payment-history');
    Route::get('/feedback', [\App\Http\Controllers\Student\FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [\App\Http\Controllers\Student\FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/reviews', [StudentDashboardController::class, 'reviews'])->name('reviews');
    Route::post('/reviews', [StudentProfileController::class, 'storeReview'])->name('reviews.store');
    Route::get('/settings', [StudentProfileController::class, 'settings'])->name('settings');
    Route::post('/settings', [StudentProfileController::class, 'updateSettings'])->name('settings.update');
    Route::get('/qa', [StudentDashboardController::class, 'qa'])->name('qa');
    Route::post('/qa', [StudentProfileController::class, 'storeQuestion'])->name('qa.store');
});

// Guest-accessible Checkout Routes
Route::name('student.')->group(function () {
    Route::get('/checkout/{course}', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout/{course}', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::post('/checkout/{course}/razorpay', [CheckoutController::class, 'createRazorpayOrder'])->name('checkout.razorpay.create');
    Route::post('/checkout/{course}/paypal', [CheckoutController::class, 'createPaypalOrder'])->name('checkout.paypal.create');
    Route::get('/payment/success', [PaymentCallbackController::class, 'success'])->name('checkout.success');
    Route::get('/payment/cancel', [PaymentCallbackController::class, 'cancel'])->name('checkout.cancel');
});

// Public Pages
Route::get('/about', fn () => \App\Support\InertiaPage::react('Public/About'))->name('about');
Route::get('/community', [CommunityController::class, 'index'])->name('community');
Route::post('/community/projects/{id}/view', [CommunityController::class, 'incrementView'])->name('community.project.view');
Route::post('/community/projects/{id}/like', [CommunityController::class, 'incrementLike'])->name('community.project.like');
Route::post('/community/projects/{id}/comment', [CommunityController::class, 'storeComment'])->name('community.project.comment');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::get('/courses', [CourseCatalogController::class, 'index'])->name('courses.index');
Route::get('/courses/category/{slug}', [CourseCatalogController::class, 'category'])->name('courses.category');
Route::post('/courses/category/{category}/waitlist', [CourseCatalogController::class, 'waitlist'])->name('courses.waitlist');
Route::get('/courses/grades/{grade}', [CourseCatalogController::class, 'gradeGroup'])->name('grade-group');
Route::get('/courses/age/{group}', [CourseCatalogController::class, 'ageGroup'])->name('age-group');
Route::get('/back-to-school', [CourseCatalogController::class, 'backToSchool'])->name('back-to-school');
Route::get('/faq', [CourseCatalogController::class, 'faq'])->name('faq');

$redirects = [
    'grade-5-math-full-year-common-core-course-a-full-year-twice-weekly-grade-5-math-course-covering-decimals-fraction-operations-expressions-volume-and-the-coordinate-plane-fully-aligned-to-common-core-standards' => 'grade-5-math-common-core',
    'grade-7-math-full-year-common-core-course-a-full-year-twice-weekly-grade-7-math-course-covering-ratios-rational-numbers-equations-geometry-and-probability-fully-aligned-to-common-core-standards' => 'grade-7-math-common-core',
    'minecraft-math-quest-build-solve-explore-grades-3-6' => 'minecraft-math-quest-grades-3-4',
    'minecraft-math-quest-build-solve-explore-grade-5-6' => 'minecraft-math-quest-grades-5-6',
    'minecraft-math-fun-geometry-fractions-number-adventures-grades-1-4' => 'minecraft-math-fun-grades-1-2',
    'grade-4-math-full-year-common-core-course' => 'grade-4-math-common-core',
    'grade-6-math-full-year-common-core-course' => 'grade-6-math-common-core',
    'grade-8-math-full-year-common-core-course' => 'grade-8-math-common-core',
    'grade-9-math-algebra-i-full-year-common-core-course' => 'grade-9-math-algebra-i',
    'roblox-game-design-summer-camp-build-your-own-3d-world-with-zero-coding' => 'roblox-summer-camp-code-design-play',
];
foreach ($redirects as $old => $new) {
    Route::redirect("/courses/{$old}", "/courses/{$new}", 301);
}

Route::get('/courses/{course}', [CourseCatalogController::class, 'show'])->name('courses.show');

// Public course review routes (no login required)
Route::get('/courses/{course}/review', [CourseReviewController::class, 'create'])->name('courses.review.create');
Route::post('/courses/{course}/review', [CourseReviewController::class, 'store'])->name('courses.review.store');
Route::get('/custom-enroll/{course}', [CustomEnrollmentController::class, 'show'])->middleware('signed')->name('custom.enrollment.checkout');
Route::post('/custom-enroll/{course}', [CustomEnrollmentController::class, 'process'])->middleware('signed')->name('custom.enrollment.process');
Route::post('/custom-enroll/{course}/razorpay', [CustomEnrollmentController::class, 'createRazorpayOrder'])->middleware('signed')->name('custom.enrollment.razorpay.create');
Route::post('/custom-enroll/{course}/paypal', [CustomEnrollmentController::class, 'createPaypalOrder'])->middleware('signed')->name('custom.enrollment.paypal.create');
Route::post('/enroll', [CustomEnrollmentController::class, 'publicGenerateLink'])->name('enroll.submit');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::redirect('/privacy', '/page/privacy-policy', 301);
Route::redirect('/terms', '/page/terms-of-service', 301);
Route::get('/become-a-tutor', fn () => \App\Support\InertiaPage::react('Public/BecomeTutor'))->name('become-tutor');
Route::get('/tutors/{tutor}', [BrandStorefrontController::class, 'show'])->name('tutors.storefront');
Route::redirect('/landingpage', '/', 301)->name('landingpage');
Route::get('/ai-robotics', [LandingController::class, 'robotics'])->name('robotics.landing');

Route::get('/minecraft', [LandingController::class, 'minecraft'])->name('minecraft.landing');

Route::get('/math', [LandingController::class, 'math'])->name('math.landing');

Route::get('/childrens-day', [LandingController::class, 'childrensDay'])->name('childrens-day.landing');
Route::redirect('/childrensday', '/childrens-day', 301);
Route::redirect('/children-day', '/childrens-day', 301);
Route::get('/pricing', function () {
    $categories = \App\Models\Category::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'slug']);
    $faqs = \App\Models\Faq::query()
        ->where('is_active', true)
        ->where('category', 'Pricing')
        ->orderBy('sort_order')
        ->get(['id', 'question', 'answer']);

    return \App\Support\InertiaPage::react('Public/Pricing', [
        'categories' => $categories,
        'faqs' => $faqs,
    ]);
})->name('pricing');

// Dynamic Category Landing Pages (Catch-All)
Route::post('/play/{slug}/token', [\App\Http\Controllers\ActivityPlayController::class, 'issueToken'])
    ->middleware('throttle:30,1')
    ->name('activity-play.token');
Route::get('/play/session/{token}', [\App\Http\Controllers\ActivityPlayController::class, 'session'])
    ->middleware('throttle:60,1')
    ->name('activity-play.session');

Route::get('/{slug}', [CategoryLandingController::class, 'show'])->name('category.landing');

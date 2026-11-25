<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Guest\CampBookingCheckoutController;
use App\Http\Controllers\Api\V1\Guest\TrialBookingController;
use App\Http\Controllers\Api\V1\Student\ActivitiesController;
use App\Http\Controllers\Api\V1\Student\AnnouncementController;
use App\Http\Controllers\Api\V1\Student\AssignmentController;
use App\Http\Controllers\Api\V1\Student\BookingController;
use App\Http\Controllers\Api\V1\Student\CatalogController;
use App\Http\Controllers\Api\V1\Student\CareerController;
use App\Http\Controllers\Api\V1\Student\ChatbotController;
use App\Http\Controllers\Api\V1\Student\CheckoutController;
use App\Http\Controllers\Api\V1\Student\CommunityController;
use App\Http\Controllers\Api\V1\Student\CourseController;
use App\Http\Controllers\Api\V1\Student\CourseExtrasController;
use App\Http\Controllers\Api\V1\Student\CustomCheckoutController;
use App\Http\Controllers\Api\V1\Student\DashboardController;
use App\Http\Controllers\Api\V1\Student\DeviceTokenController;
use App\Http\Controllers\Api\V1\Student\NotificationController;
use App\Http\Controllers\Api\V1\Student\PaymentController;
use App\Http\Controllers\Api\V1\Student\ProfileController;
use App\Http\Controllers\Api\V1\Student\QuizController;
use App\Http\Controllers\Api\V1\Student\TipController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/email/status', [AuthController::class, 'emailStatus']);
            Route::post('/email/resend', [AuthController::class, 'emailResend'])->middleware('throttle:6,1');
        });
    });

    // Stripe / PayPal Checkout returns here without Bearer token, then deep-links into the app.
    Route::get('/student/payments/stripe-return', [PaymentController::class, 'stripeReturn']);
    Route::get('/student/payments/paypal-return', [PaymentController::class, 'paypalReturn']);

    // Community page is accessible even without login
    Route::get('/student/community', [CommunityController::class, 'index']);

    // Guest trial / consultation / camp checkout funnel (no Bearer required)
    Route::prefix('guest')->name('guest.')->middleware('throttle:30,1')->group(function () {
        Route::get('/consultation-slots', [TrialBookingController::class, 'slots']);
        Route::post('/book-trial', [TrialBookingController::class, 'bookTrial'])->middleware('throttle:15,1');
        Route::post('/book-class', [CampBookingCheckoutController::class, 'bookClass'])->middleware('throttle:15,1');

        Route::prefix('booking/{booking}')->group(function () {
            Route::get('/checkout', [CampBookingCheckoutController::class, 'checkout']);
            Route::get('/', [CampBookingCheckoutController::class, 'show']);
            Route::post('/razorpay', [CampBookingCheckoutController::class, 'createRazorpayOrder']);
            Route::post('/verify', [CampBookingCheckoutController::class, 'verifyRazorpay']);
            Route::post('/stripe', [CampBookingCheckoutController::class, 'createStripeSession']);
            Route::get('/stripe-return', [CampBookingCheckoutController::class, 'stripeReturn']);
            Route::post('/paypal', [CampBookingCheckoutController::class, 'payPaypal']);
            Route::get('/paypal-return', [CampBookingCheckoutController::class, 'paypalReturn']);
        });
    });

    Route::middleware(['auth:sanctum', 'api.student.active', 'api.student.trial'])
        ->prefix('student')
        ->name('student.')
        ->group(function () {

            Route::get('/trial-status', [DashboardController::class, 'trialStatus'])->name('trial-status');
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/activities', [ActivitiesController::class, 'index']);
            Route::get('/career', [CareerController::class, 'index']);
            Route::get('/announcements', [AnnouncementController::class, 'index']);
            Route::post('/announcements/{id}/read', [AnnouncementController::class, 'markRead']);
            Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->middleware('throttle:30,1');

            Route::get('/catalog/filters', [CatalogController::class, 'filters']);
            Route::get('/catalog', [CatalogController::class, 'index']);
            Route::get('/catalog/{course}', [CatalogController::class, 'show']);

            Route::get('/courses', [CourseController::class, 'index']);
            Route::get('/courses/{course}', [CourseController::class, 'show']);
            Route::middleware('api.student.enrolled')->group(function () {
                Route::get('/courses/{course}/player', [CourseController::class, 'player']);
                Route::post('/courses/{course}/modules/{moduleId}/complete', [CourseController::class, 'completeModule']);
                Route::get('/courses/{course}/schedule-wizard', [CourseExtrasController::class, 'scheduleWizardShow']);
                Route::post('/courses/{course}/schedule-wizard', [CourseExtrasController::class, 'scheduleWizardStore']);
                Route::get('/courses/{course}/skill-tree', [CourseExtrasController::class, 'skillTree']);
                Route::get('/courses/{course}/portfolio', [CourseExtrasController::class, 'portfolioIndex']);
                Route::post('/courses/{course}/portfolio', [CourseExtrasController::class, 'storePortfolio']);
                Route::patch('/courses/{course}/portfolio/{portfolio}', [CourseExtrasController::class, 'sharePortfolio']);
                Route::get('/courses/{course}/certificate', [CourseExtrasController::class, 'certificate']);
                Route::get('/courses/{course}/certificate/download', [CourseExtrasController::class, 'downloadCertificate']);
            });

            Route::get('/assignments', [AssignmentController::class, 'index']);
            Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
            Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit']);

            Route::get('/quizzes', [QuizController::class, 'index']);
            Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
            Route::get('/quizzes/{quiz}/results', [QuizController::class, 'results']);
            Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit']);

            Route::get('/bookings', [BookingController::class, 'index']);
            Route::get('/bookings/{booking}', [BookingController::class, 'show']);
            Route::post('/bookings', [BookingController::class, 'store']);
            Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
            Route::get('/bookings/{booking}/available-slots', [BookingController::class, 'availableSlots']);
            Route::post('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);

            Route::get('/recordings', [\App\Http\Controllers\Api\V1\Student\RecordingController::class, 'index']);
            Route::post('/recordings/{recording}/request', [\App\Http\Controllers\Api\V1\Student\RecordingController::class, 'requestAccess']);
            Route::get('/recordings/{recording}/stream', [\App\Http\Controllers\Api\V1\Student\RecordingController::class, 'stream']);

            Route::get('/checkout/{course}', [CheckoutController::class, 'show']);
            Route::post('/checkout/{course}/razorpay', [CheckoutController::class, 'createRazorpayOrder']);
            Route::post('/checkout/{course}/stripe', [CheckoutController::class, 'createStripeSession']);
            Route::post('/checkout/{course}/paypal', [CheckoutController::class, 'createPaypalOrder']);

            Route::get('/custom-checkout/{course}', [CustomCheckoutController::class, 'show']);
            Route::post('/custom-checkout/{course}/razorpay', [CustomCheckoutController::class, 'createRazorpayOrder']);
            Route::post('/custom-checkout/{course}/stripe', [CustomCheckoutController::class, 'createStripeSession']);
            Route::post('/custom-checkout/{course}/paypal', [CustomCheckoutController::class, 'createPaypalOrder']);

            Route::post('/payments/verify', [PaymentController::class, 'verify']);
            Route::post('/payments/cancel', [PaymentController::class, 'cancel']);

            Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
            Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

            Route::get('/tips', [TipController::class, 'index']);
            Route::get('/tips/{tip}/stream', [TipController::class, 'stream']);

            Route::get('/rewards', [\App\Http\Controllers\Api\V1\Student\RewardController::class, 'index']);

            Route::get('/community', [CommunityController::class, 'index']);
            Route::post('/community/projects/{id}/view', [CommunityController::class, 'view']);
            Route::post('/community/projects/{id}/like', [CommunityController::class, 'like']);
            Route::post('/community/projects/{id}/comment', [CommunityController::class, 'storeComment']);

            Route::get('/payment-history', [ProfileController::class, 'paymentHistory']);

            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/profile', [ProfileController::class, 'update']); // multipart avatar

            Route::get('/settings', [ProfileController::class, 'settings']);
            Route::put('/settings', [ProfileController::class, 'updateSettings']);

            Route::get('/reviews', [ProfileController::class, 'reviews']);
            Route::post('/reviews', [ProfileController::class, 'storeReview']);

            Route::get('/qa', [ProfileController::class, 'qa']);
            Route::post('/qa', [ProfileController::class, 'storeQuestion']);

            Route::get('/wishlist', [ProfileController::class, 'wishlist']);
            Route::post('/wishlist/{courseId}', [ProfileController::class, 'addWishlist']);
            Route::delete('/wishlist/{courseId}', [ProfileController::class, 'removeWishlist']);

            Route::get('/referrals', [ProfileController::class, 'referrals']);

            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
            Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        });
});

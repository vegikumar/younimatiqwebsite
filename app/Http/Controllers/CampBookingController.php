<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCampBooking;
use App\Mail\TemplateMail;
use App\Models\CampBooking;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingWindowService;
use App\Services\CurrencyService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\TimezoneService;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\PaymentVerificationService;
use Razorpay\Api\Api as RazorpayApi;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CampBookingController extends Controller
{
    public function store(Request $request)
    {
        if ($request->has('name_c1') || $request->has('grade_c1')) {
            return $this->storeTrialBooking($request);
        }

        $validated = $request->validate([
            'parent_name' => 'required|string|max:255',
            'child_name' => 'nullable|string|max:255',
            'camp_name' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'age' => 'required|string|max:50',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|string',
            'custom_scheduled_time' => 'nullable|string',
            'custom_slot' => 'nullable',
            'timezone' => 'nullable|string|max:100',
        ]);

        $currency = session('display_currency', 'USD');
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $amountUsd = (float) ($settings['demo_class_price'] ?? 0);
        $amountLocal = CurrencyService::convert($amountUsd, $currency);

        $parentTz = TimezoneService::sanitizeTimezone($request->timezone ?: session('timezone', 'UTC'));
        try {
            $scheduledAt = $this->resolveBookingTime(
                $request,
                $parentTz,
                'default',
                BookingWindowService::FREE_CLASS_MIN_HOURS
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $booking = CampBooking::create(array_merge($validated, [
            'payment_status' => $amountUsd <= 0 ? 'paid' : 'pending',
            'amount' => $amountLocal,
            'currency' => $currency,
            'country' => $validated['country'] ?? session('country', 'US'),
            'state' => $validated['state'] ?? session('state', 'N/A'),
            'timezone' => $parentTz,
            'parent_timezone' => $parentTz,
            'scheduled_at' => $scheduledAt,
        ]));

        ProcessCampBooking::dispatch($booking, 'all');

        if ($request->expectsJson() || $request->is('api/*')) {
            $needsPayment = $amountUsd > 0 && $booking->payment_status !== 'paid';

            return response()->json([
                'success' => true,
                'message' => $needsPayment
                    ? 'Booking created. Continue to checkout.'
                    : 'Booking confirmed successfully!',
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_status' => $booking->payment_status,
                    'amount' => (float) $booking->amount,
                    'currency' => $booking->currency,
                    'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                    'needs_payment' => $needsPayment,
                    'checkout_path' => $needsPayment
                        ? '/guest/booking/'.$booking->id.'/checkout'
                        : null,
                ],
            ], 201);
        }

        if ($amountUsd <= 0) {
            return redirect()->route('booking.success', $booking)->with('success', 'Booking confirmed successfully!');
        }

        return redirect()->route('booking.checkout', $booking);
    }

    public function getAvailableSlots(Request $request, BookingWindowService $windows)
    {
        $timezone = TimezoneService::sanitizeTimezone(
            $request->input('timezone') ?: session('timezone', 'UTC')
        );
        $minHours = $windows->minHoursForBookingType($request->input('booking_type'));

        if ($request->boolean('group_by_weekday')) {
            $weekOf = $request->input('week_of') ?: $request->input('date') ?: now($timezone)->toDateString();

            return response()->json([
                'timezone' => $timezone,
                'timezone_label' => $windows->timezoneShortLabel($timezone),
                'windows' => $windows->windowsLabel(),
                'local_windows' => $windows->localWindowsDescription($timezone, $weekOf),
                'by_weekday' => $windows->localTimesByWeekday($timezone, $weekOf),
                'min_hours_ahead' => $minHours,
            ]);
        }

        $date = $request->input('date');
        if (! $date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        $slots = $windows->slotsForLocalDate($date, $timezone, true, $minHours);

        return response()->json([
            'timezone' => $timezone,
            'timezone_label' => $windows->timezoneShortLabel($timezone),
            'windows' => $windows->windowsLabel(),
            'local_windows' => $windows->localWindowsDescription($timezone, $date),
            'slots' => $slots,
            'min_hours_ahead' => $minHours,
        ]);
    }

    public function storeConsultation(Request $request)
    {
        $validated = $request->validateWithBag('consultation', [
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'name_c1' => 'required|string|max:255',
            'age_c1' => 'required|numeric',
            'grade_c1' => 'required|string|max:50',
            'board_c1' => 'required|string|max:100',
            'courses_c1' => 'required|not_in:Select course',
            'name_c2' => 'nullable|string|max:255',
            'age_c2' => 'nullable|numeric',
            'grade_c2' => 'nullable|string|max:50',
            'board_c2' => 'nullable|string|max:100',
            'courses_c2' => 'nullable|not_in:Select course',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|string',
            'custom_scheduled_time' => 'nullable|string',
            'custom_slot' => 'nullable',
            'timezone' => 'nullable|string|max:100',
        ]);

        $courses1 = is_array($request->courses_c1) ? implode(', ', $request->courses_c1) : ($request->courses_c1 ?? 'General');
        $childName = $request->name_c1;
        if ($request->filled('name_c2')) {
            $childName .= ' & '.$request->name_c2;
        }

        $ageString = 'Child 1: Age '.$request->age_c1.' (Grade '.$request->grade_c1.', '.$request->board_c1.')';
        if ($request->filled('name_c2')) {
            $ageString .= ' | Child 2: Age '.$request->age_c2.' (Grade '.$request->grade_c2.', '.$request->board_c2.')';
        }

        $bookingType = $request->input('booking_type', 'consultation');
        $isConsultation = ($bookingType === 'consultation');

        // Consultations are meeting-only leads. Free trial / enroll flows create class access.
        $campNamePrefix = $isConsultation ? 'Consultation - ' : 'Free Trial - ';
        $campName = $campNamePrefix.($courses1 ?: 'General');

        $parentTz = TimezoneService::sanitizeTimezone($request->timezone ?: session('timezone', 'UTC'));

        try {
            $scheduledAt = $this->resolveBookingTime(
                $request,
                $parentTz,
                'consultation',
                $isConsultation
                    ? BookingWindowService::CONSULTATION_MIN_HOURS
                    : BookingWindowService::FREE_CLASS_MIN_HOURS
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors(), 'consultation')->withInput();
        }

        if ($scheduledAt && CampBooking::where('scheduled_at', $scheduledAt)->exists()) {
            return back()->withErrors(['scheduled_time' => 'This slot is already booked. Please select another slot.'], 'consultation')->withInput();
        }

        $booking = CampBooking::create([
            'parent_name' => $request->name_c1,
            'child_name' => $childName,
            'camp_name' => $campName,
            'email' => $request->email,
            'phone' => $request->phone ?: ($request->whatsapp_number ?: ''),
            'whatsapp_number' => $request->whatsapp_number ?: ($request->phone ?: ''),
            'age' => $ageString,
            'payment_status' => 'paid',
            'payment_method' => $isConsultation ? 'consultation' : 'free_trial',
            'amount' => 0,
            'currency' => session('display_currency', 'USD'),
            'country' => session('country', 'US'),
            'state' => session('state', 'N/A'),
            'timezone' => $parentTz,
            'parent_timezone' => $parentTz,
            'scheduled_at' => $scheduledAt,
        ]);

        $booking = $this->attachZoomMeeting($booking);

        // Free trial only: create student account / trial access. Consultation never enrolls a class.
        $tempPassword = null;
        if (! $isConsultation) {
            $tempPassword = $this->createStudentAccountIfNeeded($booking);
        }

        $displaySlot = $this->formatSlotForEmail($scheduledAt, $parentTz);
        $zoomLink = $booking->zoom_join_url;
        $hostLink = $booking->zoom_start_url;
        $sessionLabel = $isConsultation ? 'Free Consultation' : 'Free Trial Class';

        // Send Email to Admin
        $adminEmails = app(EmailService::class)->getAdminEmails();
        $subject = ($isConsultation ? 'New Free Consultation Booked: ' : 'New Free Trial Class Booked: ').$childName;
        $body = '<h3>'.($isConsultation ? 'New Free Consultation Booking' : 'New Free Trial Class Booking').'</h3>'
            .'<p><strong>Program / Course:</strong> '.e($campName).'</p>'
            .'<p><strong>Child Name:</strong> '.e($childName).'</p>'
            .'<p><strong>Parent / Contact:</strong> '.e($request->name_c1).'</p>'
            .'<p><strong>Parent Email:</strong> '.e($request->email).'</p>'
            .'<p><strong>Phone Number:</strong> '.e($request->phone).'</p>'
            .'<p><strong>WhatsApp Number:</strong> '.e($request->whatsapp_number).'</p>'
            .'<p><strong>Age & Details:</strong> '.e($ageString).'</p>'
            .'<hr>'
            .'<p><strong>Scheduled Slot:</strong> '.e($displaySlot).'</p>'
            .($hostLink ? $this->zoomHostButtonHtml($hostLink, $booking) : '')
            .($zoomLink ? $this->zoomJoinButtonHtml($zoomLink, $booking) : '<p><strong>Join link:</strong> Pending generation</p>');

        foreach ($adminEmails as $aEmail) {
            try {
                Mail::to($aEmail)->queue(new TemplateMail($subject, $body));
            } catch (\Throwable $e) {
                Log::error('Failed sending booking email to '.$aEmail.': '.$e->getMessage());
            }
        }

        try {
            $admins = User::where('role', 'admin')->where('status', 'active')->get();
            foreach ($admins as $admin) {
                app(NotificationService::class)->notify(
                    $admin,
                    ($isConsultation ? 'New Free Consultation: ' : 'New Free Trial: ').$childName,
                    $campName.' | '.$displaySlot.' | '.$request->email,
                    $isConsultation ? route('admin.consultations') : route('admin.enrollments', ['tab' => 'demos']),
                    'booking'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Booking admin in-app notification failed: '.$e->getMessage());
        }

        // Send confirmation email to parent (includes temp password when account was created)
        if (! $isConsultation) {
            $parentName = $request->input('parent_name', $request->name_c1);
            $scheduledTimeRow = $displaySlot ? '<li style="margin-bottom: 6px;">● <strong>Scheduled Time:</strong> '.e($displaySlot).'</li>' : '';
            $meetingButton = $zoomLink ? $this->zoomJoinButtonHtml($zoomLink, $booking) : '<p>Your '.$this->meetingProviderLabel($booking).' join link will be shared shortly.</p>';
            $loginCredentialsHtml = $tempPassword
                ? '<div style="background:#f4f6f9; padding:10px 14px; border-radius:8px; margin:6px 0;"><strong>Portal:</strong> <a href="'.route('login.student').'">'.route('login.student').'</a><br><strong>Email:</strong> '.e($request->email).'<br><strong>Temporary Password:</strong> '.e($tempPassword).'</div>'
                : 'Portal: <a href="'.route('login.student').'">'.route('login.student').'</a> (Use your registered email & password)';

            // Only when a real Meet/Zoom link is present (not enrollment / “link later” mail).
            $cantAttendButton = \App\Http\Controllers\SessionNoteController::emailButtonHtml($zoomLink, [
                'kind' => 'trial',
                'ref' => 'camp:'.$booking->id,
                'session' => $campNamePrefix.$courses1,
                'when' => $displaySlot,
                'name' => $parentName,
                'email' => $request->email,
            ]);

            app(\App\Services\EmailService::class)->sendTemplate('camp_booking_confirmation', $request->email, [
                'parent_name' => $parentName,
                'child_name' => $childName,
                'course_name' => $courses1,
                'camp_name' => $courses1,
                'scheduled_at' => $displaySlot,
                'scheduled_time_row' => $scheduledTimeRow,
                'meeting_button' => $meetingButton,
                'cant_attend_button' => $cantAttendButton,
                'join_url' => $zoomLink ?: '',
                'login_credentials' => $loginCredentialsHtml,
                'temp_password' => $tempPassword ?: '',
            ]);
        } else {
            $parentSubject = 'Free Consultation Confirmed - Younimatiq';
            $parentBody = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">'
                .'<h3 style="color: #0057bf;">Your Free Consultation is Booked!</h3>'
                .'<p>Hi '.e(explode(' ', $request->name_c1)[0]).',</p>'
                .'<p>Thank you for booking a free consultation for <strong>'.e($courses1).'</strong> with Younimatiq.</p>'
                .'<p>Here are your '.$sessionLabel.' details:</p>'
                .'<ul style="list-style: none; padding: 0;">'
                .'<li style="margin-bottom: 8px;"><strong>Course:</strong> '.e($courses1).'</li>'
                .'<li style="margin-bottom: 8px;"><strong>Student:</strong> '.e($childName).'</li>'
                .'<li style="margin-bottom: 8px;"><strong>Date & Time:</strong> '.e($displaySlot).'</li>'
                .'</ul>'
                .($zoomLink ? $this->zoomJoinButtonHtml($zoomLink, $booking) : '<p>Your '.$this->meetingProviderLabel($booking).' join link will be shared shortly.</p>');

            if ($tempPassword) {
                $parentBody .= '<div style="background:#f4f6f9; padding:15px; border-radius:8px; margin:20px 0;">'
                    .'<p style="margin:0 0 8px;"><strong>Student Account Login</strong></p>'
                    .'<p style="margin:0 0 4px;"><strong>Email:</strong> '.e($request->email).'</p>'
                    .'<p style="margin:0;"><strong>Temporary Password:</strong> '.e($tempPassword).'</p>'
                    .'</div>'
                    .'<p>After your free consultation, you will have <strong>9 days of free dashboard access</strong> to explore the platform. We recommend logging in and changing your password soon.</p>';
            }

            $parentBody .= \App\Http\Controllers\SessionNoteController::emailButtonHtml($zoomLink, [
                'kind' => 'trial',
                'ref' => 'camp:'.$booking->id,
                'session' => $campNamePrefix.$courses1,
                'when' => $displaySlot,
                'name' => $request->name_c1,
                'email' => $request->email,
            ])
                .'<p>We look forward to meeting you!</p>'
                .'<p>Warm regards,<br>The Younimatiq Team</p>'
                .'</div>';

            try {
                Mail::to($request->email)
                    ->bcc('younimatiq.com+0ae348f29f@invite.trustpilot.com')
                    ->queue(new TemplateMail($parentSubject, $parentBody));
            } catch (\Throwable $e) {
                Log::error('Failed sending booking confirmation email to parent: '.$e->getMessage());
            }
        }

        session()->flash('booking_emails_sent', true);
        if ($tempPassword) {
            session()->flash('temp_password', $tempPassword);
        }

        $successMessage = $isConsultation
            ? 'Your free consultation has been booked successfully!'
            : 'Your free trial class has been booked successfully!';

        return redirect()->route('booking.success', $booking)->with('success', $successMessage);
    }

    public function storeTrialBooking(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'name_c1' => 'required|string|max:255',
            'age_c1' => 'required|numeric|min:3|max:20',
            'grade_c1' => 'required|string|max:50',
            'board_c1' => 'required|string|max:100',
            'courses_c1' => 'required|not_in:Select course',
            'name_c2' => 'nullable|string|max:255',
            'age_c2' => 'nullable|numeric|min:3|max:20',
            'grade_c2' => 'nullable|string|max:50',
            'board_c2' => 'nullable|string|max:100',
            'courses_c2' => 'nullable|not_in:Select course',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|string',
            'custom_scheduled_time' => 'nullable|string',
            'custom_slot' => 'nullable',
            'timezone' => 'nullable|string|max:100',
        ]);

        $courses1 = is_array($request->courses_c1) ? implode(', ', $request->courses_c1) : ($request->courses_c1 ?? 'General');
        $childName = $request->name_c1;
        if ($request->filled('name_c2')) {
            $childName .= ' & '.$request->name_c2;
        }

        $ageString = 'Child 1: Age '.$request->age_c1.' (Grade '.$request->grade_c1.', '.$request->board_c1.')';
        if ($request->filled('name_c2')) {
            $ageString .= ' | Child 2: Age '.$request->age_c2.' (Grade '.$request->grade_c2.', '.$request->board_c2.')';
        }

        $campName = 'Free Trial - '.($courses1 ?: 'General');

        $parentTz = TimezoneService::sanitizeTimezone($request->timezone ?: session('timezone', 'UTC'));
        try {
            $scheduledAt = $request->scheduled_date
                ? $this->resolveBookingTime(
                    $request,
                    $parentTz,
                    'default',
                    BookingWindowService::FREE_CLASS_MIN_HOURS
                )
                : null;
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $booking = CampBooking::create([
            'parent_name' => $request->name_c1,
            'child_name' => $childName,
            'camp_name' => $campName,
            'email' => $request->email,
            'phone' => $request->phone ?: ($request->whatsapp_number ?: ''),
            'whatsapp_number' => $request->whatsapp_number ?: ($request->phone ?: ''),
            'age' => $ageString,
            'payment_status' => 'paid',
            'payment_method' => 'free_trial',
            'amount' => 0,
            'currency' => session('display_currency', 'USD'),
            'country' => session('country', 'US'),
            'state' => session('state', 'N/A'),
            'timezone' => $parentTz,
            'parent_timezone' => $parentTz,
            'scheduled_at' => $scheduledAt,
        ]);

        if ($scheduledAt) {
            $booking = $this->attachZoomMeeting($booking);
        }

        $tempPassword = $this->createStudentAccountIfNeeded($booking);
        if ($tempPassword) {
            session()->flash('temp_password', $tempPassword);
        }

        $displaySlot = $this->formatSlotForEmail($scheduledAt, $parentTz);
        $zoomLink = $booking->zoom_join_url;
        $hostLink = $booking->zoom_start_url;

        $adminEmails = app(EmailService::class)->getAdminEmails();
        $subject = 'New Free Trial Class Booked: '.$childName;
        $body = '<h3>New Free Trial Class Details</h3>'
            .'<p><strong>Parent Name:</strong> '.e($request->name_c1).'</p>'
            .'<p><strong>Email:</strong> '.e($request->email).'</p>'
            .'<p><strong>Phone:</strong> '.e($request->phone).'</p>'
            .'<p><strong>Child Details:</strong> '.e($ageString).'</p>'
            .'<p><strong>Program Interest:</strong> '.e($campName).'</p>'
            .'<p><strong>Scheduled Slot:</strong> '.e($displaySlot).'</p>'
            .($hostLink ? $this->zoomHostButtonHtml($hostLink, $booking) : '')
            .($zoomLink ? $this->zoomJoinButtonHtml($zoomLink, $booking) : '');

        foreach ($adminEmails as $aEmail) {
            try {
                Mail::to($aEmail)->queue(new TemplateMail($subject, $body));
            } catch (\Throwable $e) {
                Log::error('Failed sending trial booking email to admin: '.$e->getMessage());
            }
        }

        if ($scheduledAt) {
            $parentName = $request->input('parent_name', $request->name_c1);
            $scheduledTimeRow = $displaySlot ? '<li style="margin-bottom: 6px;">● <strong>Scheduled Time:</strong> '.e($displaySlot).'</li>' : '';
            $meetingButton = $zoomLink ? $this->zoomJoinButtonHtml($zoomLink, $booking) : '<p>Your '.$this->meetingProviderLabel($booking).' join link will be shared shortly.</p>';
            $cantAttendButton = \App\Http\Controllers\SessionNoteController::emailButtonHtml($zoomLink, [
                'kind' => 'trial',
                'ref' => 'camp:'.$booking->id,
                'session' => 'Free Trial - '.$courses1,
                'when' => $displaySlot,
                'name' => $parentName,
                'email' => $request->email,
            ]);

            app(\App\Services\EmailService::class)->sendTemplate('camp_booking_confirmation', $request->email, [
                'parent_name' => $parentName,
                'child_name' => $childName,
                'course_name' => $courses1,
                'camp_name' => $courses1,
                'scheduled_at' => $displaySlot,
                'scheduled_time_row' => $scheduledTimeRow,
                'meeting_button' => $meetingButton,
                'cant_attend_button' => $cantAttendButton,
                'join_url' => $zoomLink ?: '',
            ]);
            session()->flash('booking_emails_sent', true);
        }

        $message = $scheduledAt
            ? 'Your free trial class has been booked successfully!'
            : 'Your free trial details have been submitted successfully! We will contact you soon.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'booking_id' => $booking->id,
                    'camp_name' => $booking->camp_name,
                    'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                    'zoom_join_url' => $booking->zoom_join_url,
                    'timezone' => $booking->timezone,
                ],
            ], 201);
        }

        return redirect()->route('booking.success', $booking)->with('success', $message);
    }

    public function checkout(CampBooking $booking)
    {
        $razorpayKey = config('services.razorpay.key');
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        $demoPrice = (float) ($settings['demo_class_price'] ?? 0);
        $displayAmount = CurrencyService::format($demoPrice, $booking->currency ?: session('display_currency', 'USD'));

        $enableStripe = (bool) ($settings['enable_stripe'] ?? 1);
        $enablePaypal = (bool) ($settings['enable_paypal'] ?? 1);
        $enableRazorpay = (bool) ($settings['enable_razorpay'] ?? 1);
        $paypalClientId = config('services.paypal.client_id');
        $paypalSupported = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        $bookingCurrency = strtoupper($booking->currency ?: session('display_currency', 'USD'));
        $paypalCurrency = in_array($bookingCurrency, $paypalSupported, true) ? $bookingCurrency : 'USD';

        return \App\Support\InertiaPage::react('Public/Booking/Checkout', [
            'booking' => [
                'id' => $booking->id,
                'camp_name' => $booking->camp_name,
                'parent_name' => $booking->parent_name,
            ],
            'razorpayKey' => $razorpayKey,
            'paypalClientId' => $paypalClientId,
            'paypalCurrency' => $paypalCurrency,
            'displayAmount' => $displayAmount,
            'enableStripe' => $enableStripe,
            'enablePaypal' => $enablePaypal,
            'enableRazorpay' => $enableRazorpay,
        ]);
    }

    private function createStudentAccountIfNeeded(CampBooking $booking): ?string
    {
        $user = User::where('email', $booking->email)->first();
        if ($user) {
            // Keep trial window aligned with the latest demo class when still on trial
            if ($booking->scheduled_at && ! $user->trial_ends_at) {
                $user->update([
                    'trial_ends_at' => Carbon::parse($booking->scheduled_at)->addDays(9),
                ]);
            }

            return null;
        }

        $studentNameStr = strtolower(explode(' ', trim($booking->child_name))[0]);
        if (empty($studentNameStr)) {
            $studentNameStr = strtolower(explode(' ', trim($booking->parent_name))[0]);
        }
        if (empty($studentNameStr)) {
            $studentNameStr = 'student';
        }
        $password = $studentNameStr.'@123';

        $trialEndsAt = $booking->scheduled_at
            ? Carbon::parse($booking->scheduled_at)->addDays(9)
            : now()->addDays(9);

        $user = User::create([
            'name' => $booking->child_name,
            'email' => $booking->email,
            'password' => $password,
            'role' => 'student',
            'timezone' => $booking->timezone,
            'status' => 'active',
            'is_auto_created' => true,
            'trial_ends_at' => $trialEndsAt,
        ]);

        try {
            app(EmailService::class)->sendTemplate('welcome_student', $user->email, [
                'student_name' => $user->name,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed sending welcome_student email to '.$user->email.': '.$e->getMessage());
        }

        return $password;
    }

    public function pay(Request $request, CampBooking $booking, EmailService $emailService)
    {
        $request->validate(['payment_method' => 'required|in:paypal,stripe,razorpay']);

        $booking->update(['payment_method' => $request->payment_method]);

        if ($request->payment_method === 'razorpay') {
            return back()->with('info', 'Use the Razorpay button to complete payment.');
        }

        if ($request->payment_method === 'paypal') {
            $order = $this->buildPaypalOrder($booking);
            if (isset($order['error'])) {
                return back()->with('error', $order['error']);
            }
            if (! empty($order['free'])) {
                return redirect()->to($order['approval_url']);
            }

            return redirect()->away($order['approval_url']);
        }

        if ($request->payment_method === 'stripe') {
            if (! config('services.stripe.secret')) {
                return back()->with('error', 'Stripe is not configured. Add keys in Admin → Gateway.');
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            $settings = Cache::rememberForever('global_settings', function () {
                return Setting::pluck('value', 'key')->toArray();
            });
            $demoPrice = (float) ($settings['demo_class_price'] ?? 0);
            $currencyCode = strtoupper($booking->currency ?: session('display_currency', 'USD'));
            $amountMajor = (float) ($booking->amount ?: ($demoPrice > 0 ? CurrencyService::convert($demoPrice, $currencyCode) : 0));

            $stripeSupported = ['USD', 'INR', 'EUR', 'GBP', 'AED', 'SGD', 'AUD', 'CAD'];
            if (! in_array($currencyCode, $stripeSupported, true)) {
                $rates = CurrencyService::getRates();
                $rate = $rates[$currencyCode] ?? 1.0;
                if ($rate > 0) {
                    $amountMajor = round($amountMajor / $rate, 2);
                }
                $currencyCode = 'USD';
            }

            if ($amountMajor <= 0) {
                $booking->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'stripe',
                    'amount' => 0,
                    'currency' => $currencyCode,
                ]);
                ProcessCampBooking::dispatch($booking, 'parent');

                return redirect()->route('booking.success', $booking)->with('success', 'No payment required.');
            }

            $unitAmount = (int) max(50, round($amountMajor * 100));

            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currencyCode),
                        'unit_amount' => $unitAmount,
                        'product_data' => ['name' => $booking->camp_name.' - Demo Trial'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('booking.success', $booking).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('booking.checkout', $booking),
                'customer_email' => $booking->email,
            ]);

            $booking->update([
                'amount' => $amountMajor,
                'currency' => $currencyCode,
                'payment_method' => 'stripe',
            ]);

            return redirect($session->url);
        }

        return back()->with('error', 'Unsupported payment method.');
    }

    public function createPaypalOrder(CampBooking $booking)
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
        if (! (bool) ($settings['enable_paypal'] ?? 1)) {
            return response()->json(['error' => 'PayPal is currently disabled.'], 400);
        }

        $order = $this->buildPaypalOrder($booking);
        if (isset($order['error'])) {
            return response()->json(['error' => $order['error']], $order['status'] ?? 500);
        }

        return response()->json($order);
    }

    public function paypalReturn(Request $request, CampBooking $booking, PaymentVerificationService $paymentVerification)
    {
        if ($request->query('status') === 'cancel') {
            return redirect()->route('booking.checkout', $booking)->with('error', 'PayPal payment cancelled.');
        }

        if ($booking->payment_status === 'paid') {
            return redirect()->route('booking.success', $booking)->with('success', 'Payment already completed.');
        }

        $orderId = $request->query('token')
            ?? $request->query('paypal_order_id')
            ?? $booking->paypal_order_id;

        if (! $orderId || ! $paymentVerification->verifyPayPal($orderId)) {
            return redirect()->route('booking.checkout', $booking)->with('error', 'PayPal payment verification failed.');
        }

        $booking->update([
            'payment_status' => 'paid',
            'payment_method' => 'paypal',
            'paypal_order_id' => $orderId,
        ]);
        ProcessCampBooking::dispatch($booking, 'parent');

        return redirect()->route('booking.success', $booking)->with('success', 'Payment successful via PayPal!');
    }

    /**
     * Create a PayPal order for a camp/demo booking and return approval URL + ids.
     *
     * @return array{approval_url: string, paypal_order_id: string, amount: string, currency: string}|array{error: string, status?: int}
     */
    public function buildPaypalOrder(CampBooking $booking, ?string $returnUrl = null, ?string $cancelUrl = null): array
    {
        if (! config('services.paypal.client_id') || ! config('services.paypal.secret')) {
            return ['error' => 'PayPal is not configured. Add Client ID and Secret in Admin → Gateway.', 'status' => 422];
        }

        $currency = strtoupper($booking->currency ?: session('display_currency', 'USD'));
        $paypalSupported = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'NZD', 'CHF', 'HKD'];
        $price = (float) (Setting::where('key', 'demo_class_price')->value('value') ?? 0);

        if ($price <= 0 && (float) ($booking->amount ?? 0) <= 0) {
            $booking->update([
                'payment_status' => 'paid',
                'payment_method' => 'paypal',
                'amount' => 0,
                'currency' => $currency,
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');

            return [
                'approval_url' => $returnUrl ?: route('booking.success', $booking),
                'paypal_order_id' => null,
                'amount' => '0.00',
                'currency' => $currency,
                'free' => true,
            ];
        }

        $amount = (float) ($booking->amount ?: CurrencyService::convert($price > 0 ? $price : 1, $currency));
        if (! in_array($currency, $paypalSupported, true)) {
            $rates = CurrencyService::getRates();
            $rate = $rates[$currency] ?? 1.0;
            if ($rate > 0) {
                $amount = round($amount / $rate, 2);
            }
            $currency = 'USD';
        }
        $formattedAmount = number_format(max(0.01, $amount), 2, '.', '');

        try {
            $provider = new PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $description = substr(preg_replace('/[^a-zA-Z0-9_ \-.,]/', '', $booking->camp_name ?: 'Demo Class'), 0, 120) ?: 'Demo Class';

            $order = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'brand_name' => 'Younimatiq',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl ?: route('booking.paypal.return', ['booking' => $booking->id, 'status' => 'success']),
                    'cancel_url' => $cancelUrl ?: route('booking.paypal.return', ['booking' => $booking->id, 'status' => 'cancel']),
                ],
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $formattedAmount,
                    ],
                    'description' => $description,
                    'custom_id' => (string) $booking->id,
                ]],
            ]);

            if (empty($order['id'])) {
                $errorMsg = $order['message'] ?? ($order['error_description'] ?? 'Failed to create PayPal order');
                Log::error('Camp PayPal order error: '.json_encode($order));

                return ['error' => $errorMsg, 'status' => 500];
            }

            $approvalUrl = null;
            foreach ($order['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approvalUrl = $link['href'] ?? null;
                    break;
                }
            }

            if (! $approvalUrl) {
                return ['error' => 'PayPal approval URL missing.', 'status' => 500];
            }

            $booking->update([
                'payment_method' => 'paypal',
                'paypal_order_id' => $order['id'],
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'approval_url' => $approvalUrl,
                'checkout_url' => $approvalUrl,
                'paypal_order_id' => $order['id'],
                'booking_id' => $booking->id,
                'amount' => $formattedAmount,
                'currency' => $currency,
            ];
        } catch (\Throwable $e) {
            Log::error('Camp PayPal order exception: '.$e->getMessage());

            return ['error' => 'PayPal error: '.$e->getMessage(), 'status' => 500];
        }
    }

    public function createRazorpayOrder(CampBooking $booking)
    {
        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if (! $key || ! $secret || str_contains((string) $secret, 'replace_me')) {
            return response()->json([
                'error' => 'Razorpay test keys are not fully configured. Set real test keys in Admin → Settings, or use Test Gateway.',
            ], 422);
        }

        // Demo price is stored as USD; Razorpay charges INR only.
        $currency = \App\Support\PaymentGatewayCurrency::forRazorpay();
        $price = (float) (Setting::where('key', 'demo_class_price')->value('value') ?? 0);

        if ($price <= 0) {
            $booking->update([
                'amount' => 0,
                'currency' => $currency,
                'status' => 'paid',
            ]);

            return response()->json([
                'status' => 'success',
                'booking_id' => $booking->id,
            ]);
        }

        $amount = CurrencyService::convert($price, $currency);

        try {
            $api = new RazorpayApi($key, $secret);
            $order = $api->order->create([
                'receipt' => 'demo_'.$booking->id.'_'.time(),
                'amount' => (int) max(100, round($amount * 100)),
                'currency' => $currency,
                'notes' => [
                    'booking_id' => $booking->id,
                    'camp_name' => $booking->camp_name,
                ],
            ]);

            $booking->update([
                'razorpay_order_id' => $order->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'amount' => (int) max(100, round($amount * 100)),
                'currency' => $currency,
                'key' => $key,
                'name' => config('app.name', 'Younimatiq'),
                'description' => $booking->camp_name.' — Demo Class',
                'prefill' => [
                    'name' => $booking->parent_name,
                    'email' => $booking->email,
                    'contact' => $booking->phone,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyRazorpay(Request $request, CampBooking $booking, EmailService $emailService)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $api = new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $booking->update([
                'payment_status' => 'paid',
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
            ]);
            ProcessCampBooking::dispatch($booking, 'parent');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful!',
                    'data' => [
                        'booking_id' => $booking->id,
                        'payment_status' => 'paid',
                        'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                        'zoom_join_url' => $booking->zoom_join_url,
                    ],
                ]);
            }

            return redirect()->route('booking.success', $booking)->with('success', 'Payment successful!');
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->route('booking.checkout', $booking)->with('error', 'Payment verification failed: '.$e->getMessage());
        }
    }

    public function success(Request $request, CampBooking $booking, EmailService $emailService)
    {
        if ($request->query('session_id') && config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($request->query('session_id'));
            if ($session->payment_status !== 'paid') {
                return redirect()->route('booking.checkout', $booking)->with('error', 'Payment not completed.');
            }
        }

        $booking->update(['payment_status' => 'paid']);

        // Skip re-sending when the booking form already queued confirmation emails
        if (! session('booking_emails_sent') && ! str_starts_with($booking->camp_name, 'Consultation')) {
            ProcessCampBooking::dispatch($booking, 'parent');
        }

        $displayTz = TimezoneService::resolveForDisplay(
            $booking->parent_timezone,
            $booking->timezone,
        );

        return \App\Support\InertiaPage::react('Public/Booking/Success', [
            'booking' => [
                'id' => $booking->id,
                'camp_name' => $booking->camp_name,
                'scheduled_at_label' => $booking->scheduled_at
                    ? TimezoneService::formatForUser($booking->scheduled_at, $displayTz, 'M j, Y g:i A')
                    : null,
                'timezone_label' => TimezoneService::shortLabel($displayTz),
            ],
        ]);
    }

    private function resolveBookingTime(
        Request $request,
        string $timezone,
        ?string $errorBag = null,
        int $minHoursAhead = 0
    ): Carbon {
        $windows = app(BookingWindowService::class);
        $custom = $request->boolean('custom_slot') || $request->input('scheduled_time') === 'custom';

        try {
            $scheduledAt = $windows->resolveFromRequest(
                $request->input('scheduled_date'),
                $request->input('scheduled_time'),
                $timezone,
                $custom,
                $request->input('custom_scheduled_time')
            );
        } catch (\Throwable $e) {
            $scheduledAt = null;
        }

        if (! $scheduledAt) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'Please choose a preferred time slot, or enter a custom time.',
            ])->errorBag($errorBag ?: 'default');
        }

        if (! $windows->isWithinWindow($scheduledAt)) {
            throw ValidationException::withMessages([
                'scheduled_time' => $windows->windowValidationMessage($timezone),
            ])->errorBag($errorBag ?: 'default');
        }

        if ($minHoursAhead > 0 && $scheduledAt->lt(now('UTC')->addHours($minHoursAhead))) {
            throw ValidationException::withMessages([
                'scheduled_time' => "Please choose a slot at least {$minHoursAhead} hours from now so we can confirm the schedule.",
            ])->errorBag($errorBag ?: 'default');
        }

        return $scheduledAt;
    }

    private function formatSlotForEmail(?Carbon $scheduledAt, string $timezone): string
    {
        if (! $scheduledAt) {
            return 'To be confirmed';
        }

        return TimezoneService::formatForEmail($scheduledAt, $timezone);
    }

    private function attachZoomMeeting(CampBooking $booking): CampBooking
    {
        try {
            $zoomService = app(ZoomService::class);
            $startTime = $booking->scheduled_at
                ? Carbon::parse($booking->scheduled_at)->utc()->format('Y-m-d H:i:s')
                : now()->utc()->format('Y-m-d H:i:s');
            $timezone = $booking->timezone ?: 'UTC';
            $fields = $zoomService->ensureMeetingFields(
                $booking,
                $booking->camp_name ?: 'Younimatiq Session',
                $startTime,
                45,
                $timezone
            );
            $booking->update($fields);
            $booking->refresh();
        } catch (\Throwable $e) {
            Log::warning('Meeting creation failed for camp booking '.$booking->id.': '.$e->getMessage());
        }

        return $booking;
    }

    private function meetingProviderLabel(?CampBooking $booking = null): string
    {
        $provider = $booking?->meeting_provider ?: app(ZoomService::class)->activeProvider();

        return app(ZoomService::class)->providerLabel($provider);
    }

    private function zoomJoinButtonHtml(string $url, ?CampBooking $booking = null): string
    {
        $label = $this->meetingProviderLabel($booking);

        return '<p style="margin:24px 0;text-align:center;">'
            .'<a href="'.e($url).'" style="background:#2D8CFF;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;font-size:16px;">Join '.$label.' Meeting</a>'
            .'</p>'
            .'<p style="font-size:13px;color:#666;text-align:center;">Or copy this link: <a href="'.e($url).'" style="color:#2D8CFF;">'.e($url).'</a></p>';
    }

    private function zoomHostButtonHtml(string $url, ?CampBooking $booking = null): string
    {
        $label = $this->meetingProviderLabel($booking);

        return '<p style="margin:16px 0;text-align:center;">'
            .'<a href="'.e($url).'" style="background:#0B5CFF;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;font-size:14px;">Start '.$label.' as Host</a>'
            .'</p>';
    }
}

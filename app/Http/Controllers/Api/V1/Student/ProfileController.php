<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Referral;
use App\Models\Review;
use App\Models\SiblingDiscount;
use App\Models\Wishlist;
use App\Services\ImageUploadService;
use App\Services\ReferralService;
use App\Services\StudentApiService;
use App\Services\TimezoneService;
use App\Support\ApiAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private StudentApiService $studentApi,
        private ImageUploadService $images,
    ) {}

    public function show(Request $request)
    {
        return $this->success($this->studentApi->formatUser($request->user()));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'timezone' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:2000',
            'grade' => 'nullable|integer|min:1|max:12',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $data = collect($request->only('name', 'email', 'timezone', 'bio', 'grade'))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();

        if ($request->exists('grade') && ($request->input('grade') === null || $request->input('grade') === '')) {
            $data['grade'] = null;
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->images->storePath($request->file('avatar'), 'avatars', 'public');
        }

        if ($data === []) {
            return $this->error('Nothing to update.', 422);
        }

        $user->update($data);

        return $this->success($this->studentApi->formatUser($user->fresh()), 'Profile updated.');
    }

    public function settings(Request $request)
    {
        return $this->success([
            'user' => $this->studentApi->formatUser($request->user()),
            'timezones' => TimezoneService::commonTimezones(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();

        if ($request->filled('password')) {
            $request->validate(['password' => 'required|string|min:8|confirmed']);
            $user->password = Hash::make($request->password);
        }

        $user->notification_preferences = [
            'email_reminders' => $request->boolean('email_reminders'),
            'email_marketing' => $request->boolean('email_marketing'),
        ];
        if ($request->filled('timezone')) {
            $user->timezone = $request->timezone;
        }
        $user->save();

        return $this->success($this->studentApi->formatUser($user->fresh()), 'Settings saved.');
    }

    public function paymentHistory(Request $request)
    {
        $payments = Payment::with('course')
            ->where('student_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'gateway' => $p->gateway,
                'course' => $p->course ? ['id' => $p->course->id, 'title' => $p->course->title] : null,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        $orders = Order::with('course')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'total' => (float) ($o->total ?? 0),
                'status' => $o->status,
                'course' => $o->course ? ['id' => $o->course->id, 'title' => $o->course->title] : null,
                'created_at' => $o->created_at?->toIso8601String(),
            ]);

        return $this->success(compact('payments', 'orders'));
    }

    public function reviews(Request $request)
    {
        $reviews = Review::with('course')->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->get();

        $reviewItems = $reviews->map(fn ($r) => [
            'id' => $r->id,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'course' => $r->course ? ['id' => $r->course->id, 'title' => $r->course->title] : null,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        $enrolledCourseIds = $request->user()->enrollments()->pluck('course_id');
        $reviewedCourseIds = $reviews->pluck('course_id');
        $unreviewedCourses = Course::whereIn('id', $enrolledCourseIds)
            ->whereNotIn('id', $reviewedCourseIds)
            ->get(['id', 'title']);

        return $this->success(['reviews' => $reviewItems, 'unreviewed_courses' => $unreviewedCourses]);
    }

    public function storeReview(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $data['course_id']],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        return $this->success(null, 'Review submitted.');
    }

    public function qa(Request $request)
    {
        $questions = Question::with(['course', 'answers.user'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'body' => $q->body,
                'course' => $q->course ? ['id' => $q->course->id, 'title' => $q->course->title] : null,
                'answers' => $q->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'body' => $a->body,
                    'user' => $a->user ? ['name' => $a->user->name] : null,
                    'created_at' => $a->created_at?->toIso8601String(),
                ]),
                'created_at' => $q->created_at?->toIso8601String(),
            ]);

        return $this->success(['questions' => $questions]);
    }

    public function storeQuestion(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
        ]);

        $question = Question::create([
            'user_id' => $request->user()->id,
            'course_id' => $data['course_id'],
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        return $this->success(['id' => $question->id], 'Question posted.', 201);
    }

    public function wishlist(Request $request)
    {
        $items = Wishlist::with('course')->where('user_id', $request->user()->id)->get()
            ->map(fn ($w) => [
                'course_id' => $w->course_id,
                'course' => $w->course ? array_merge(
                    ['id' => $w->course->id, 'title' => $w->course->title],
                    ['thumbnail_url' => ApiAssetUrl::fromPath($w->course->thumbnail_url ?? $w->course->thumbnail ?? $w->course->image ?? null)]
                ) : null,
            ]);

        return $this->success(['wishlist' => $items]);
    }

    public function addWishlist(Request $request, $courseId)
    {
        Wishlist::firstOrCreate(['user_id' => $request->user()->id, 'course_id' => $courseId]);
        try {
            app(\App\Services\ReEngagementService::class)->trackInterest((int) $request->user()->id, (int) $courseId);
        } catch (\Throwable $e) {
            // non-blocking
        }

        return $this->success(null, 'Added to wishlist.');
    }

    public function removeWishlist(Request $request, $courseId)
    {
        Wishlist::where('user_id', $request->user()->id)->where('course_id', $courseId)->delete();

        return $this->success(null, 'Removed from wishlist.');
    }

    public function referrals(Request $request, ReferralService $referralService)
    {
        $user = $request->user();
        $code = $referralService->getReferralCode($user);
        $referrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('referred_email')
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'referred_email' => $r->referred_email,
                'reward_amount' => (float) $r->reward_amount,
                'status' => $r->status,
                'completed_at' => $r->completed_at?->toIso8601String(),
                'created_at' => $r->created_at?->toIso8601String(),
            ]);
        $parentIds = $user->parents()->pluck('users.id');
        $siblingDiscounts = SiblingDiscount::query()
            ->where(function ($q) use ($user, $parentIds) {
                $q->where('sibling_student_id', $user->id)
                    ->orWhere('primary_student_id', $user->id);
                if ($parentIds->isNotEmpty()) {
                    $q->orWhereIn('parent_id', $parentIds);
                }
            })
            ->with(['primaryStudent', 'siblingStudent'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'discount_percent' => (float) ($s->discount_percent ?? 15),
                'primary_student' => $s->primaryStudent ? [
                    'id' => $s->primaryStudent->id,
                    'name' => $s->primaryStudent->name,
                ] : null,
                'sibling_student' => $s->siblingStudent ? [
                    'id' => $s->siblingStudent->id,
                    'name' => $s->siblingStudent->name,
                ] : null,
            ]);

        $shareUrl = url('/register/student?ref='.urlencode($code));
        $shareMessage = "Join me on Younimatiq! Use my referral code {$code}: {$shareUrl}";

        return $this->success([
            'referral_code' => $code,
            'share_url' => $shareUrl,
            'share_message' => $shareMessage,
            'mobile_share_url' => 'younimatiq://register?ref='.urlencode($code),
            'referrals' => $referrals,
            'sibling_discounts' => $siblingDiscounts,
            'total_earned' => collect($referrals)->where('status', 'completed')->sum('reward_amount'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Question;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\ImageUploadService;
use App\Services\ReEngagementService;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StudentProfileController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function profile()
    {
        $user = Auth::user();

        return \App\Support\InertiaPage::react('Student/Profile', [
            'user' => $this->serializeUser($user),
            'timezones' => TimezoneService::commonOptionsForSelect($user->timezone),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'timezone' => 'nullable|string|in:'.implode(',', TimezoneService::identifiers()),
            'avatar' => 'nullable|image|max:5120',
            'grade' => 'nullable|integer|min:1|max:12',
            'leaderboard_opt_out' => 'nullable|boolean',
        ]);

        $oldTimezone = $user->timezone;
        $data = $request->only('name', 'email', 'timezone', 'grade');
        $data['leaderboard_opt_out'] = $request->boolean('leaderboard_opt_out');
        if (array_key_exists('timezone', $data) && $data['timezone'] !== null) {
            $data['timezone'] = TimezoneService::sanitizeTimezone($data['timezone']);
        }

        if ($request->hasFile('avatar')) {
            $path = $this->images->storePath($request->file('avatar'), 'avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        if (array_key_exists('timezone', $data) && $data['timezone'] !== null) {
            $newTz = $data['timezone'];
            if ($newTz !== $oldTimezone && ! TimezoneService::isUnset($newTz)) {
                Booking::query()
                    ->where('student_id', $user->id)
                    ->where('scheduled_at', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'canceled', 'completed', 'no_show'])
                    ->update([
                        'timezone' => $newTz,
                        'parent_timezone' => $newTz,
                    ]);
            }
        }

        return back()->with('success', 'Profile updated.');
    }

    public function settings()
    {
        return \App\Support\InertiaPage::react('Student/Settings', [
            'user' => $this->serializeUser(Auth::user(), includePrefs: true),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->notification_preferences = [
            'email_reminders' => $request->boolean('email_reminders'),
            'email_marketing' => $request->boolean('email_marketing'),
        ];
        if ($request->filled('timezone')) {
            $oldTimezone = $user->timezone;
            $newTz = TimezoneService::sanitizeTimezone($request->input('timezone'));
            $user->timezone = $newTz;
            if ($newTz !== $oldTimezone && ! TimezoneService::isUnset($newTz)) {
                Booking::query()
                    ->where('student_id', $user->id)
                    ->where('scheduled_at', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'canceled', 'completed', 'no_show'])
                    ->update([
                        'timezone' => $newTz,
                        'parent_timezone' => $newTz,
                    ]);
            }
        }
        $user->save();

        return back()->with('success', 'Settings saved.');
    }

    public function addWishlist(Request $request, $courseId)
    {
        Wishlist::firstOrCreate(['user_id' => Auth::id(), 'course_id' => $courseId]);
        try {
            app(ReEngagementService::class)->trackInterest((int) Auth::id(), (int) $courseId);
        } catch (\Throwable $e) {
            Log::warning('wishlist trackInterest: '.$e->getMessage());
        }

        return back()->with('success', 'Added to wishlist.');
    }

    public function removeWishlist($courseId)
    {
        Wishlist::where('user_id', Auth::id())->where('course_id', $courseId)->delete();

        return back()->with('success', 'Removed from wishlist.');
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::updateOrCreate(
            ['user_id' => Auth::id(), 'course_id' => $request->course_id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return back()->with('success', 'Review submitted.');
    }

    public function storeQuestion(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
        ]);

        Question::create([
            'user_id' => Auth::id(),
            'course_id' => $request->course_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Question posted.');
    }

    private function serializeUser($user, bool $includePrefs = false): array
    {
        $avatarUrl = $user->avatar
            ? asset('storage/' . $user->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=046bd2&color=fff&size=128';

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => $user->timezone ?? 'UTC',
            'avatar_url' => $avatarUrl,
            'member_since' => $user->created_at?->format('M Y'),
            'grade' => $user->grade,
            'leaderboard_opt_out' => (bool) ($user->leaderboard_opt_out ?? false),
        ];

        if ($includePrefs) {
            $payload['notification_preferences'] = $user->notification_preferences ?? [];
        }

        return $payload;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class CourseReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['course:id,title', 'user:id,name,email,role'])
            ->orderByDesc('created_at');

        if ($request->filled('role') && in_array($request->query('role'), ['parent', 'student'], true)) {
            $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role')));
        }

        // Guest/legacy reviews without a linked user only appear under All.

        if ($request->filled('q')) {
            $q = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('comment', 'like', "%{$q}%")
                    ->orWhere('reviewer_name', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$q}%"));
            });
        }

        $paginator = $query->paginate(20)->withQueryString();

        return \App\Support\InertiaPage::react('Admin/Reviews/Index', [
            'filterRole' => $request->query('role'),
            'search' => $request->query('q'),
            'reviews' => [
                'data' => collect($paginator->items())->map(fn (Review $r) => [
                    'id' => $r->id,
                    'courseTitle' => $r->course?->title ?? 'Deleted course',
                    'rating' => (int) $r->rating,
                    'comment' => $r->comment,
                    'authorName' => $r->reviewer_display_name,
                    'authorEmail' => $r->user?->email ?? '',
                    'authorRole' => $this->authorRoleLabel($r->user),
                    'createdAtLabel' => optional($r->created_at)?->format('M j, Y g:i A'),
                    'destroyUrl' => route('admin.reviews.destroy', $r),
                ])->values()->all(),
                'links' => $paginator->linkCollection()->toArray(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Course review removed.');
    }

    private function authorRoleLabel(?User $user): string
    {
        if (! $user) {
            return 'Guest';
        }

        return match ($user->role) {
            'parent' => 'Parent',
            'student' => 'Student',
            'tutor' => 'Tutor',
            'admin' => 'Admin',
            default => ucfirst((string) $user->role),
        };
    }
}

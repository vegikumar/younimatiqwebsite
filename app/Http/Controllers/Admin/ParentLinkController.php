<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ParentLinkController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_email' => 'required|email|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_password' => 'nullable|string|min:6',
            'student_id' => 'nullable|string|max:255',
            'student_email' => 'nullable|string|max:255',
            'relationship' => 'nullable|string|in:parent,guardian',
            'send_email' => 'nullable|boolean',
        ]);

        $parentEmail = strtolower(trim($data['parent_email']));
        $identifier = trim((string) ($data['student_email'] ?? $data['student_id'] ?? ''));

        if ($identifier === '') {
            return back()->with('error', 'Please enter the student email address or user ID.');
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL) && strcasecmp($parentEmail, $identifier) === 0) {
            return back()->with('error', 'The parent email and student email cannot be the same email address.');
        }

        // 1. Resolve Student User
        $student = null;
        if (is_numeric($identifier) && ! str_contains($identifier, '@')) {
            $student = User::find((int) $identifier);
            if (! $student) {
                return back()->with('error', "No user found with ID #{$identifier}.");
            }
        } else {
            $student = User::where('email', $identifier)->first();
            if (! $student) {
                // Auto-create student if not found yet
                $studentName = ucfirst(explode('@', $identifier)[0]);
                $student = User::create([
                    'name' => $studentName,
                    'email' => $identifier,
                    'password' => Hash::make(str()->random(16)),
                    'role' => 'student',
                    'status' => 'active',
                    'timezone' => 'UTC',
                    'is_auto_created' => true,
                    'trial_ends_at' => now()->addDays(9),
                    'email_verified_at' => now(),
                ]);
            }
        }

        // Guard against staff accounts
        if (in_array($student->role, ['admin', 'tutor'], true)) {
            return back()->with('error', "The account '{$student->email}' is a {$student->role} staff account and cannot be linked as a student.");
        }

        // If the student user account was previously marked as 'parent', set it to 'student'
        if ($student->role !== 'student') {
            $student->role = 'student';
            $student->save();
        }

        // 2. Resolve Parent User
        if ($student->id && strcasecmp($parentEmail, $student->email) === 0) {
            return back()->with('error', 'The parent email and student email cannot be the same user account.');
        }

        $parent = User::where('email', $parentEmail)->first();

        if ($parent && $parent->id === $student->id) {
            return back()->with('error', 'The parent and student cannot be the same user account.');
        }

        if ($parent && in_array($parent->role, ['admin', 'tutor'], true)) {
            return back()->with('error', "The email '{$parentEmail}' belongs to an {$parent->role} staff account and cannot be linked as a parent.");
        }

        $parentPlainPassword = ! empty($data['parent_password']) ? $data['parent_password'] : null;

        if ($parent) {
            if ($parent->role !== 'parent') {
                $parent->role = 'parent';
            }
            if (! empty($data['parent_name'])) {
                $parent->name = $data['parent_name'];
            }
            if ($parentPlainPassword) {
                $parent->password = Hash::make($parentPlainPassword);
            }
            $parent->save();
        } else {
            $parentPlainPassword = $parentPlainPassword ?: str()->random(10);
            $parent = User::create([
                'name' => ($data['parent_name'] ?? null) ?: 'Parent of ' . $student->name,
                'email' => $parentEmail,
                'password' => Hash::make($parentPlainPassword),
                'role' => 'parent',
                'status' => 'active',
                'is_auto_created' => true,
                'email_verified_at' => now(),
            ]);
        }

        // 3. Link them in parent_student table
        ParentStudent::updateOrCreate(
            ['parent_id' => $parent->id, 'student_id' => $student->id],
            [
                'relationship' => $data['relationship'] ?? 'parent',
                'consent_at' => now(),
                'linked_by' => Auth::guard('admin')->id(),
            ]
        );

        $sentEmailNotice = '';
        if ($request->boolean('send_email') && $parentPlainPassword) {
            try {
                $welcomeReq = new Request([
                    'parent_password' => $parentPlainPassword,
                    'send_to_parent' => true,
                    'send_to_student' => false,
                ]);
                app(\App\Http\Controllers\Admin\UserManagementController::class)->sendWelcomeEmail($welcomeReq, $student);
                $sentEmailNotice = ' Welcome email with login credentials sent to parent.';
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed sending welcome email in ParentLinkController: ' . $e->getMessage());
            }
        }

        Cache::forget('admin:dashboard:v2:all:all');

        return back()->with('success', "Successfully linked parent {$parent->name} ({$parent->email}) to student {$student->name} ({$student->email}).{$sentEmailNotice}");
    }
}


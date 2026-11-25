<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'student');

        if ($role === 'tutor') {
            return redirect()->route('admin.tutors');
        }

        return redirect()->route('admin.students');
    }

    public function show(User $user)
    {
        if ($user->role === 'tutor') {
            return redirect()->route('admin.tutors', ['selected' => $user->id]);
        }

        return redirect()->route('admin.students', ['selected' => $user->id]);
    }

    public function suspend(User $user)
    {
        $user->update(['status' => 'suspended']);

        return back()->with('success', "User {$user->name} has been suspended.");
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        return back()->with('success', "User {$user->name} has been activated.");
    }
}

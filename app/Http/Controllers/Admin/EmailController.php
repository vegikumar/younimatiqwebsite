<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        $recentLogs = EmailLog::orderBy('created_at', 'desc')->take(50)->get();
        return \App\Support\InertiaPage::react('Admin/Email/Index', [
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'subject' => $t->subject,
                'slug' => $t->slug,
                'is_active' => (bool) $t->is_active,
                'editUrl' => route('admin.email.edit', $t),
            ])->values()->all(),
            'recentLogs' => $recentLogs->map(fn ($log) => [
                'to_email' => $log->to_email,
                'subject' => $log->subject,
                'subjectShort' => \Illuminate\Support\Str::limit($log->subject, 30),
                'status' => $log->status,
                'createdAtLabel' => $log->created_at?->format('M d, Y g:i A'),
            ])->values()->all(),
        ]);
    }

    public function edit(EmailTemplate $template)
    {
        return \App\Support\InertiaPage::react('Admin/Email/Form', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'subject' => $template->subject,
                'slug' => $template->slug,
                'body' => $template->body,
                'is_active' => (bool) $template->is_active,
                'updateUrl' => route('admin.email.update', $template),
                'testUrl' => route('admin.email.test', $template),
            ],
        ]);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);
        $template->update($data);
        return redirect()->route('admin.email.index')->with('success', 'Template updated.');
    }

    public function testSend(Request $request, EmailTemplate $template, EmailService $emailService)
    {
        $request->validate(['email' => 'required|email']);
        $emailService->sendTemplate($template->slug, $request->email, [
            'student_name' => 'Alex',
            'course_title' => 'Sample Course: Roblox Game Design',
            'course_name' => 'Sample Course: Roblox Game Design',
            'amount' => '$49.99',
            'parent_name' => 'Sarah Johnson',
            'child_name' => 'Alex Johnson',
            'camp_name' => 'Sample Course: Roblox Game Design',
            'login_credentials' => 'Portal: https://younimatiq.com/login/student | Email: alex@example.com',
            'scheduled_at' => now()->addDay()->format('M d, Y g:i A') . ' (UTC)',
            'scheduled_time_row' => '<li style="margin-bottom: 6px;">● <strong>Scheduled Time:</strong> ' . now()->addDay()->format('M d, Y g:i A') . ' (UTC)</li>',
            'meeting_button' => '<p><a href="https://younimatiq.com" style="display:inline-block;padding:10px 18px;background:#0057bf;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">Join Trial Class</a></p>',
            'email' => $request->email,
            'class_time' => now()->addDay()->format('M d, Y g:i A'),
            'timezone' => 'UTC',
            'reset_url' => url('/'),
            'name' => 'Sarah Johnson',
        ]);
        return back()->with('success', 'Test email sent.');
    }
}

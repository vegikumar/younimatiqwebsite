<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Services\AntiBypassSecurityService;
use App\Support\TutorInertiaProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectMessageController extends Controller
{
    public function index()
    {
        $tutorId = Auth::guard('tutor')->id();

        $conversations = DirectConversation::where('tutor_id', $tutorId)
            ->with(['student', 'course', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        $myCourses = Course::where('tutor_id', $tutorId)->with(['enrollments.user'])->get();

        return \App\Support\InertiaPage::react('Tutor/Messages/Index', [
            'conversations' => $conversations->map(fn ($c) => TutorInertiaProps::conversation($c))->values()->all(),
            'startStudentOptions' => TutorInertiaProps::messageStartOptions($myCourses),
        ]);
    }

    public function show(DirectConversation $conversation)
    {
        $tutorId = Auth::guard('tutor')->id();
        if ($conversation->tutor_id !== $tutorId) {
            abort(403);
        }

        $messages = $conversation->messages()->with('sender')->get();

        return \App\Support\InertiaPage::react('Tutor/Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'studentName' => $conversation->student?->name,
            ],
            'messages' => $messages->map(fn ($m) => TutorInertiaProps::directMessage($m, $tutorId))->values()->all(),
        ]);
    }

    public function start(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $conversation = DirectConversation::firstOrCreate(
            [
                'tutor_id' => Auth::guard('tutor')->id(),
                'student_id' => $request->student_id,
            ],
            [
                'course_id' => $request->course_id,
                'last_message_at' => now(),
            ]
        );

        return redirect()->route('tutor.messages.show', $conversation);
    }

    public function store(Request $request, DirectConversation $conversation, AntiBypassSecurityService $security)
    {
        if ($conversation->tutor_id !== Auth::guard('tutor')->id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'attachment' => 'nullable|file',
        ]);

        if (empty($request->message) && !$request->hasFile('attachment')) {
            return redirect()->back()->with('error', 'Please enter a message or attach a file.');
        }

        $body = $request->filled('message')
            ? $security->maskSensitiveContactInfo((string) $request->message)
            : null;

        $attachmentUrl = null;
        $attachmentName = null;
        $attachmentType = null;
        $fileSizeBytes = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = $file->getMimeType();
            $size = $file->getSize();

            // Validate attachment size: 1GB limit for videos, 10MB limit for other files
            $isVideo = str_starts_with($mime, 'video/');
            $maxAllowedBytes = $isVideo ? (1024 * 1024 * 1024) : (10 * 1024 * 1024);

            if ($size > $maxAllowedBytes) {
                $limitLabel = $isVideo ? '1GB for video uploads' : '10MB for file attachments';
                return redirect()->back()->with('error', "Attachment size exceeds the maximum limit of {$limitLabel}.");
            }

            $path = $file->store('direct_message_attachments', 'public');
            $attachmentUrl = route('secure.asset', ['path' => $path]);
            $attachmentName = $file->getClientOriginalName();
            $attachmentType = $isVideo ? 'video' : 'document';
            $fileSizeBytes = $size;
        }

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::guard('tutor')->id(),
            'message' => $body,
            'attachment_url' => $attachmentUrl,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'file_size_bytes' => $fileSizeBytes,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->back()->with('success', 'Message sent!');
    }
}

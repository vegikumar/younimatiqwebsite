<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $paginator = ContactMessage::orderBy('created_at', 'desc')->paginate(20);

        $contactMessages = [
            'data' => $paginator->getCollection()->map(fn (ContactMessage $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone ?? 'N/A',
                'location' => $message->location ?? 'Unknown',
                'subject' => $message->subject ?? 'N/A',
                'message' => $message->message,
                'message_preview' => \Illuminate\Support\Str::limit($message->message, 50),
                'created_at_label' => $message->created_at?->format('M d, Y g:i A'),
                'date' => $message->created_at?->format('M d, Y g:i A'),
            ])->values()->all(),
            'links' => $paginator->linkCollection()->toArray(),
        ];

        return \App\Support\InertiaPage::react('Admin/ContactMessages/Index', [
            'contactMessages' => $contactMessages,
        ]);
    }
}

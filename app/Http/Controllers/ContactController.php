<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function show()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()->all();

        return \App\Support\InertiaPage::react('Public/Contact', [
            'categories' => $categories,
            'detectedCountry' => strtolower((string) (session('country_code') ?: 'us')),
            'old' => [
                'name' => old('name'),
                'email' => old('email'),
                'phone' => old('phone'),
                'subject' => old('subject'),
                'message' => old('message'),
            ],
        ]);
    }

    public function store(Request $request, EmailService $emailService)
    {
        $categoryNames = Category::where('is_active', true)->pluck('name')->all();
        $subjects = array_values(array_unique(array_merge(['General Inquiry'], $categoryNames)));

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => ['required', 'string', Rule::in($subjects)],
            'message' => 'required|string|max:5000',
        ]);

        $data['location'] = $this->resolveLocation($request->ip());
        $data['status'] = 'new';

        ContactMessage::create($data);

        try {
            $emailService->sendTemplate('contact_received', $data['email'], [
                'name' => $data['name'],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Contact confirmation email failed: '.$e->getMessage());
        }

        $adminMessage = 'You have received a new contact form message.<br><br>'
            .'<strong>Name:</strong> '.e($data['name']).'<br>'
            .'<strong>Email:</strong> '.e($data['email']).'<br>'
            .'<strong>Phone:</strong> '.e($data['phone'] ?? 'N/A').'<br>'
            .'<strong>Location (IP Based):</strong> '.e($data['location']).'<br>'
            .'<strong>Subject:</strong> '.e($data['subject']).'<br><br>'
            .'<strong>Message:</strong><br>'.nl2br(e($data['message']));

        try {
            $emailService->notifyAdmins(
                'New Contact Form Submission: '.$data['subject'],
                $adminMessage
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Contact admin notify failed: '.$e->getMessage());
        }

        return back()->with('success', 'Thank you! Your message has been sent. Our team will get back to you soon.');
    }

    private function resolveLocation(?string $ip): string
    {
        if (!$ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return 'Unknown';
        }

        try {
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,city,regionName,country',
            ]);

            if ($response->successful()) {
                $geo = $response->json();
                if (($geo['status'] ?? null) === 'success') {
                    $parts = array_filter([
                        $geo['city'] ?? null,
                        $geo['regionName'] ?? null,
                        $geo['country'] ?? null,
                    ]);

                    return $parts ? implode(', ', $parts) : 'Unknown';
                }
            }
        } catch (\Throwable $e) {
            // Ignore geolocation failures
        }

        return 'Unknown';
    }
}

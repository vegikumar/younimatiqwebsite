<?php

namespace App\Http\Controllers;

use App\Models\EmailSuppression;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UnsubscribeController extends Controller
{
    public function show(Request $request)
    {
        $email = strtolower(trim((string) $request->query('email', '')));

        if ($email && $request->hasValidSignature()) {
            return \App\Support\InertiaPage::react('Public/Unsubscribe', [
                'email' => $email,
                'signed' => true,
                'already' => EmailSuppression::isSuppressed($email),
            ]);
        }

        return \App\Support\InertiaPage::react('Public/Unsubscribe', [
            'email' => $email,
            'signed' => false,
            'already' => $email !== '' && EmailSuppression::isSuppressed($email),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $email = strtolower(trim($data['email']));

        if ($request->filled('sig_email') && $request->boolean('signed')) {
            // Prefer the signed email if present to avoid spoofed form posts swapping addresses.
            $signedEmail = strtolower(trim((string) $request->input('sig_email')));
            if ($signedEmail !== '' && filter_var($signedEmail, FILTER_VALIDATE_EMAIL)) {
                $email = $signedEmail;
            }
        }

        EmailSuppression::suppress($email, 'unsubscribe', 'web_form');

        NewsletterSubscriber::where('email', $email)->delete();

        $user = User::where('email', $email)->first();
        if ($user) {
            $prefs = $user->notification_preferences ?? [];
            $prefs['email_marketing'] = false;
            $user->notification_preferences = $prefs;
            $user->save();
        }

        return redirect()
            ->route('unsubscribe.show', ['email' => $email])
            ->with('status', 'You have been unsubscribed from marketing emails.');
    }

    public static function signedUrl(string $email): string
    {
        return URL::temporarySignedRoute(
            'unsubscribe.show',
            now()->addDays(60),
            ['email' => strtolower(trim($email))]
        );
    }
}

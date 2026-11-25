@extends('layouts.dashboard')

@section('title', 'Email Templates')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Email Templates & Logs</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage transactional email templates and view delivery logs.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-2 gap-5 md:p-8 mb-10">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">mail</span> Templates
            </h2>
            <div class="space-y-3">
                @forelse($templates as $template)
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant hover:border-primary/30 transition-colors">
                        <div>
                            <h3 class="font-bold">{{ $template->name }}</h3>
                            <p class="text-sm text-on-surface-variant">{{ $template->subject }}</p>
                            <span class="text-xs font-mono text-on-surface-variant">{{ $template->slug }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $template->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $template->is_active ? 'Active' : 'Off' }}
                            </span>
                            <a href="{{ route('admin.email.edit', $template) }}" class="p-2 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-sm">edit</span></a>
                        </div>
                    </div>
                @empty
                    <p class="text-on-surface-variant text-center py-6">No email templates configured.</p>
                @endforelse
            </div>
        </div>

        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">history</span> Recent Email Logs
            </h2>
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-outline-variant">
                            <th class="py-2 px-2 font-bold">To</th>
                            <th class="py-2 px-2 font-bold">Subject</th>
                            <th class="py-2 px-2 font-bold">Status</th>
                            <th class="py-2 px-2 font-bold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr class="border-b border-outline-variant/50 hover:bg-surface-container-low">
                                <td class="py-2 px-2 text-xs">{{ $log->to_email }}</td>
                                <td class="py-2 px-2">{{ Str::limit($log->subject, 30) }}</td>
                                <td class="py-2 px-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $log->status === 'sent' ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 text-xs text-on-surface-variant"><x-local-time :date="$log->created_at" format="datetime-short" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-on-surface-variant">No emails sent yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

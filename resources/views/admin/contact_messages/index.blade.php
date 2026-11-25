@extends('layouts.dashboard')
@section('title', 'Contact Forms')
@section('content')
<div class="p-5 md:p-8 space-y-8" x-data="{ modalOpen: false, selectedMessage: null }">
    <div class="flex justify-between items-center">
        <h1 class="font-headline-lg text-2xl font-bold">Contact Forms Submissions</h1>
    </div>

    <div class="bg-white rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="p-4 font-bold">Name</th>
                        <th class="p-4 font-bold">Email</th>
                        <th class="p-4 font-bold">Subject</th>
                        <th class="p-4 font-bold max-w-sm">Message</th>
                        <th class="p-4 font-bold">Date Submitted</th>
                        <th class="p-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contactMessages as $message)
                    <tr class="border-t hover:bg-surface-container-low/60 transition-colors">
                        <td class="p-4 font-bold text-on-surface">{{ $message->name }}</td>
                        <td class="p-4 text-on-surface">{{ $message->email }}</td>
                        <td class="p-4 font-medium">{{ $message->subject ?? 'N/A' }}</td>
                        <td class="p-4 text-sm text-on-surface max-w-sm truncate" style="max-width: 250px;">
                            {{ Str::limit($message->message, 50) }}
                        </td>
                        <td class="p-4 text-xs text-on-surface-variant"><x-local-time :date="$message->created_at" format="datetime" /></td>
                        <td class="p-4 text-right">
                            <button 
                                x-on:click='selectedMessage = {{ json_encode([
                                    "name" => $message->name,
                                    "email" => $message->email,
                                    "phone" => $message->phone ?? "N/A",
                                    "location" => $message->location ?? "Unknown",
                                    "subject" => $message->subject ?? "N/A",
                                    "date" => $message->created_at->format("M d, Y g:i A"),
                                    "message" => $message->message
                                ]) }}; modalOpen = true'
                                class="btn-primary btn-sm px-3 py-1.5 rounded-lg text-xs"
                            >
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-5 md:p-8 text-center text-on-surface-variant font-medium">No contact messages found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contactMessages->hasPages())
        <div class="p-4 border-t">
            {{ $contactMessages->links() }}
        </div>
        @endif
    </div>

    <!-- Modal for Viewing Full Message -->
    <template x-teleport="body">
        <div x-show="modalOpen" style="display: none;" class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <!-- Background overlay -->
                <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 bg-on-surface-variant/75 backdrop-blur-sm transition-opacity" @click="modalOpen = false" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="modalOpen" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-outline-variant">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl leading-6 font-bold text-on-surface" id="modal-title">
                                        Contact Form Submission
                                    </h3>
                                    <button @click="modalOpen = false" class="text-outline hover:text-on-surface transition-colors">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>
                                
                                <template x-if="selectedMessage">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-surface-container-low p-4 rounded-xl border border-outline-variant">
                                            <div>
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Name</p>
                                                <p class="font-medium text-on-surface" x-text="selectedMessage.name"></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Email</p>
                                                <p class="font-medium text-on-surface"><a :href="'mailto:' + selectedMessage.email" class="text-primary hover:underline" x-text="selectedMessage.email"></a></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Phone</p>
                                                <p class="font-medium text-on-surface"><a :href="'tel:' + selectedMessage.phone" class="text-primary hover:underline" x-text="selectedMessage.phone"></a></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Location</p>
                                                <p class="font-medium text-on-surface" x-text="selectedMessage.location"></p>
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Subject</p>
                                                <p class="font-medium text-on-surface" x-text="selectedMessage.subject"></p>
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Date Submitted</p>
                                                <p class="font-medium text-on-surface" x-text="selectedMessage.date"></p>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Message</p>
                                            <div class="bg-white border border-outline-variant rounded-xl p-4 text-on-surface whitespace-pre-wrap text-sm leading-relaxed" x-text="selectedMessage.message">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="bg-surface-container-low px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-outline-variant">
                        <button type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-on-surface-variant text-base font-bold text-white hover:bg-on-surface-variant focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="modalOpen = false">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

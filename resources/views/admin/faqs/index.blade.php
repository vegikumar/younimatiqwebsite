@extends('layouts.dashboard')

@section('title', 'FAQs')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-tertiary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Frequently Asked Questions</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage FAQ entries shown on the public FAQ page.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-tertiary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary">help</span> Add FAQ
            </h2>
            <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Question</label>
                    <input type="text" name="question" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Answer</label>
                    <textarea id="add-faq-answer" name="answer" rows="4" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Category</label>
                        <input type="text" name="category" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="General">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Sort Order</label>
                        <input type="number" name="sort_order" value="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-block">Add FAQ</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-4">
            @forelse($faqs as $faq)
                <div class="clay-card p-6" x-data="{ editing: false }">
                    <div x-show="!editing">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                @if($faq->category)
                                    <span class="text-xs font-bold text-primary uppercase tracking-wider">{{ $faq->category }}</span>
                                @endif
                                <h3 class="font-bold text-lg mt-1">{{ $faq->question }}</h3>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="editing = true; setTimeout(() => initEditEditor({{ $faq->id }}), 50)" class="p-2 text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined text-sm">edit</span></button>
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-sm">delete</span></button>
                                </form>
                            </div>
                        </div>
                        <div class="text-on-surface-variant leading-relaxed prose prose-sm max-w-none">{!! $faq->answer !!}</div>
                        <div class="mt-3 flex gap-4 text-xs text-on-surface-variant">
                            <span>Order: {{ $faq->sort_order }}</span>
                            <span>{{ $faq->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>

                    <form x-show="editing" method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-bold mb-2">Question</label>
                            <input type="text" name="question" value="{{ $faq->question }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Answer</label>
                            <textarea id="edit-faq-answer-{{ $faq->id }}" name="answer" rows="3" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ $faq->answer }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="category" value="{{ $faq->category }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl" placeholder="Category">
                            <input type="number" name="sort_order" value="{{ $faq->sort_order }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl">
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" {{ $faq->is_active ? 'checked' : '' }} class="w-4 h-4">
                            <span class="text-sm font-bold">Active</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary btn-sm">Save</button>
                            <button type="button" @click="editing = false" class="px-4 py-2 bg-surface-container font-bold rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="clay-card p-6 md:p-12 text-center text-on-surface-variant">No FAQs yet. Add your first question above.</div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    const initTinyMCE = (selector) => {
        if (tinymce.get(selector.replace('#', ''))) {
            tinymce.get(selector.replace('#', '')).remove();
        }
        tinymce.init({
            selector: selector,
            menubar: false,
            plugins: 'lists link code',
            toolbar: 'undo redo | bold italic | bullist numlist | link code',
            height: 250,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    };

    document.addEventListener("DOMContentLoaded", function() {
        initTinyMCE('#add-faq-answer');
    });

    window.initEditEditor = function(id) {
        initTinyMCE('#edit-faq-answer-' + id);
    };
</script>
@endsection

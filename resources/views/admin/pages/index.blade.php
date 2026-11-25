@extends('layouts.dashboard')

@section('title', 'Pages')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden flex justify-between items-center">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Static Pages</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Manage custom content pages like Privacy Policy and Terms.</p>
        </div>
        <a href="{{ route('admin.pages.create') }}" class="btn-primary btn-sm flex items-center gap-2">
            <span class="material-symbols-outlined">add</span> New Page
        </a>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="clay-card p-5 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold">Title</th>
                        <th class="py-4 px-4 font-bold">Slug</th>
                        <th class="py-4 px-4 font-bold">Published</th>
                        <th class="py-4 px-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                            <td class="py-4 px-4 font-medium">{{ $page->title }}</td>
                            <td class="py-4 px-4 text-on-surface-variant text-sm">
                                <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="hover:text-primary">{{ $page->slug }}</a>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $page->is_published ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                    {{ $page->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="p-2 text-on-surface-variant hover:text-primary inline-block"><span class="material-symbols-outlined text-sm">edit</span></a>
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-sm">delete</span></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">No pages created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

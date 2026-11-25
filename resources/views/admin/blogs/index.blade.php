@extends('layouts.dashboard')

@section('title', 'Blog Posts')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden flex justify-between items-center">
        <div>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Blog Posts</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Manage articles and news for the public blog.</p>
        </div>
        <a href="{{ route('admin.blogs.create') }}" class="btn-primary btn-sm flex items-center gap-2">
            <span class="material-symbols-outlined">add</span> New Post
        </a>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="clay-card p-5 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold">Title</th>
                        <th class="py-4 px-4 font-bold">Author</th>
                        <th class="py-4 px-4 font-bold">Status</th>
                        <th class="py-4 px-4 font-bold">Published</th>
                        <th class="py-4 px-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blogs as $blog)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low">
                            <td class="py-4 px-4 font-medium">{{ $blog->title }}</td>
                            <td class="py-4 px-4 text-on-surface-variant">{{ $blog->author ?? 'Admin' }}</td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $blog->status === 'published' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                    {{ ucfirst($blog->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-sm text-on-surface-variant">@if($blog->published_at){{ $blog->published_at->format('M d, Y') }}@else—@endif</td>
                            <td class="py-4 px-4 text-right">
                                <a href="{{ route('admin.blogs.edit', $blog) }}" class="p-2 text-on-surface-variant hover:text-primary inline-block"><span class="material-symbols-outlined text-sm">edit</span></a>
                                <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" class="inline" onsubmit="return confirm('Delete this post?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-sm">delete</span></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-on-surface-variant">No blog posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

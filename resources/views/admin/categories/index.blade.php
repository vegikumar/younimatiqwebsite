@extends('layouts.dashboard')

@section('title', 'Categories')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Course Categories</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Organize courses into browsable categories.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_circle</span> Add Category
            </h2>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. Math & Science">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Slug (optional)</label>
                        <input type="text" name="slug" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. math-science">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Optional description"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Sort Order</label>
                        <input type="number" name="sort_order" value="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div class="pt-4 mt-4 border-t border-outline-variant">
                    <h3 class="font-bold text-sm mb-3">SEO</h3>
                    <div>
                        <label class="block text-sm font-bold mb-2">SEO Meta Title</label>
                        <input type="text" name="seo_title" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Optional SEO Title">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">SEO Meta Description</label>
                    <textarea name="seo_description" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Optional SEO Description"></textarea>
                </div>
                <button type="submit" class="btn-primary btn-block">Create Category</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-4 px-4 font-bold text-on-surface">Name</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Slug</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Order</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Status</th>
                            <th class="py-4 px-4 font-bold text-on-surface text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        @if($category->image)
                                            <img src="{{ route('secure.asset', ['path' => $category->image]) }}" alt="" class="w-8 h-8 rounded object-cover">
                                        @else
                                            <div class="w-8 h-8 rounded bg-surface-container flex items-center justify-center text-xs text-on-surface-variant">Img</div>
                                        @endif
                                        <span class="font-medium">{{ $category->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-on-surface-variant text-sm">{{ $category->slug }}</td>
                                <td class="py-4 px-4 text-on-surface-variant">{{ $category->sort_order }}</td>
                                <td class="py-4 px-4">
                                    <form method="POST" action="{{ route('admin.categories.toggle-active', $category) }}" class="inline-flex items-center gap-3">
                                        @csrf @method('PATCH')
                                        <label class="relative inline-flex items-center cursor-pointer" title="{{ $category->is_active ? 'Deactivate' : 'Activate' }}">
                                            <input type="checkbox" onchange="this.form.submit()" class="sr-only peer" {{ $category->is_active ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-success"></div>
                                        </label>
                                        <span class="text-xs font-bold {{ $category->is_active ? 'text-on-tertiary-container' : 'text-on-surface-variant' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </form>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.categories.landing-page', $category) }}" 
                                           class="p-2 text-on-surface-variant hover:text-secondary" 
                                           title="Manage Landing Page">
                                            <span class="material-symbols-outlined text-sm">web</span>
                                        </a>
                                        <button data-category="{{ json_encode($category) }}" 
                                                onclick="openEditCategory(this)"
                                                type="button" 
                                                class="p-2 text-on-surface-variant hover:text-primary" 
                                                title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="m-0 p-0 flex items-center" onsubmit="return confirm('Delete this category?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-sm">delete</span></button>
                                        </form>
                                    </div>
                                </td>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-on-surface-variant">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Global Edit Modal -->
<div x-data="{ show: false, cat: {} }" @open-edit.window="cat = $event.detail; show = true">
    <div x-show="show" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm text-left">
        <div class="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative" @click.away="show = false">
            <button @click="show = false" type="button" class="absolute top-4 right-4 p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-sm">close</span></button>
            <h3 class="font-bold text-xl mb-4">Edit Category</h3>
            <form method="POST" :action="'/admin/categories/' + cat.id" class="space-y-4" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" :value="cat.name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Slug</label>
                        <input type="text" name="slug" :value="cat.slug" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="3" x-text="cat.description" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Sort Order</label>
                        <input type="number" name="sort_order" :value="cat.sort_order" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none">
                    </div>
                </div>
                <div class="pt-4 mt-4 border-t border-outline-variant">
                    <h3 class="font-bold text-sm mb-3">SEO</h3>
                    <div>
                        <label class="block text-sm font-bold mb-2">SEO Meta Title</label>
                        <input type="text" name="seo_title" :value="cat.seo_metadata?.meta_title || ''" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" placeholder="Optional SEO Title">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">SEO Meta Description</label>
                    <textarea name="seo_description" rows="2" x-text="cat.seo_metadata?.meta_description || ''" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-[var(--radius)] focus:border-primary outline-none" placeholder="Optional SEO Description"></textarea>
                </div>
                <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-primary" :checked="!!cat.is_active">
                    <span class="text-sm font-bold">Active on website</span>
                </label>
                <button type="submit" class="btn-primary w-full">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCategory(btn) {
    try {
        var categoryData = JSON.parse(btn.dataset.category);
        window.dispatchEvent(new CustomEvent('open-edit', { detail: categoryData }));
    } catch (e) {
        console.error("Error parsing category data", e);
    }
}
</script>

@endsection

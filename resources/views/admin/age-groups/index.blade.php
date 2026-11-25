@extends('layouts.dashboard')

@section('title', 'Grade Bands')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Grade Bands</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage age bands shown in the header dropdown and course filters.</p>
    </div>

    

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6">Add Grade Band</h2>
            <form method="POST" action="{{ route('admin.age-groups.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Grade / Name</label>
                    <input type="text" name="name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. Grade 5">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Age Range</label>
                    <input type="text" name="age_range" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. 10-11">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Slug (optional)</label>
                    <input type="text" name="slug" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="auto from age range">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"></textarea>
                </div>
                <button type="submit" class="btn-primary btn-block">Create Grade Band</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-4 px-4 font-bold">Grade</th>
                            <th class="py-4 px-4 font-bold">Slug</th>

                            <th class="py-4 px-4 font-bold">Order</th>
                            <th class="py-4 px-4 font-bold">Status</th>
                            <th class="py-4 px-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ageGroups as $group)
                            <tr class="border-b border-outline-variant">
                                <td class="py-4 px-4 font-medium">{{ $group->name }} @if($group->age_range) <span class="text-xs text-on-surface-variant">({{ $group->age_range }})</span> @endif</td>
                                <td class="py-4 px-4 text-sm text-on-surface-variant">{{ $group->slug }}</td>

                                <td class="py-4 px-4">{{ $group->sort_order }}</td>
                                <td class="py-4 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $group->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                        {{ $group->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button data-agegroup="{{ json_encode($group) }}" 
                                                onclick="openEditAgeGroup(this)"
                                                type="button" 
                                                class="p-2 text-on-surface-variant hover:text-primary" 
                                                title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('admin.age-groups.update', $group) }}" class="m-0 p-0 flex items-center">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="name" value="{{ $group->name }}">
                                            <input type="hidden" name="age_range" value="{{ $group->age_range }}">
                                            <input type="hidden" name="sort_order" value="{{ $group->sort_order }}">
                                            <input type="hidden" name="slug" value="{{ $group->slug }}">
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="is_active" value="1" class="sr-only peer" onchange="this.form.submit()" {{ $group->is_active ? 'checked' : '' }}>
                                                <div class="w-11 h-6 bg-surface-container rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </form>
                                        <form method="POST" action="{{ route('admin.age-groups.destroy', $group) }}" class="m-0 p-0 flex items-center" onsubmit="return confirm('Delete this age group?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-on-surface-variant hover:text-error" title="Delete"><span class="material-symbols-outlined text-sm">delete</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-on-surface-variant">No age groups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Global Edit Modal -->
<div x-data="{ show: false, group: {} }" @open-edit.window="group = $event.detail; show = true">
    <div x-show="show" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-background/40 backdrop-blur-sm text-left">
        <div class="bg-white rounded-[var(--radius)] p-6 max-w-md w-full shadow-2xl relative" @click.away="show = false">
            <button @click="show = false" type="button" class="absolute top-4 right-4 p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-sm">close</span></button>
            <h3 class="font-bold text-xl mb-4">Edit Grade Band</h3>
            <form method="POST" :action="'/admin/age-groups/' + group.id" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-bold mb-2">Grade / Name</label>
                    <input type="text" name="name" :value="group.name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Age Range</label>
                    <input type="text" name="age_range" :value="group.age_range" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Slug (optional)</label>
                    <input type="text" name="slug" :value="group.slug" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Sort Order</label>
                    <input type="number" name="sort_order" :value="group.sort_order" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="2" x-text="group.description" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"></textarea>
                </div>
                <button type="submit" class="btn-primary w-full">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditAgeGroup(btn) {
    try {
        var data = JSON.parse(btn.dataset.agegroup);
        window.dispatchEvent(new CustomEvent('open-edit', { detail: data }));
    } catch (e) {
        console.error("Error parsing age group data", e);
    }
}
</script>
@endsection

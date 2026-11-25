@extends('layouts.dashboard')

@section('title', 'Community Testimonials')

@section('content')
<div x-data="testimonialManager()" class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Testimonials</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage the testimonials shown on the Community page.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_circle</span> Add Testimonial
            </h2>
            <form method="POST" action="{{ route('admin.community.testimonials.store') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Author Name</label>
                    <input type="text" name="author_name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. John Doe">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Testimonial Text</label>
                    <textarea name="text" required rows="4" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="What did they say?"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Rating</label>
                    <select name="rating" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Avatar (Optional)</label>
                    <input type="file" name="avatar_file" accept="image/*" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Video Testimonial (Optional)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <p class="text-xs text-on-surface-variant mt-1">Upload a short video review.</p>
                </div>
                <button type="submit" class="btn-primary w-full">Add Testimonial</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-4 px-4 font-bold text-on-surface">Author</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Review</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Rating</th>
                            <th class="py-4 px-4 font-bold text-on-surface text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($testimonials as $testimonial)
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        @if($testimonial->avatar_path)
                                            <img src="{{ Str::startsWith($testimonial->avatar_path, 'http') ? $testimonial->avatar_path : Storage::disk('public')->url($testimonial->avatar_path) }}" alt="" class="w-10 h-10 rounded-full object-cover shadow-sm">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">{{ substr($testimonial->author_name, 0, 1) }}</div>
                                        @endif
                                        <span class="font-bold whitespace-nowrap">{{ $testimonial->author_name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-sm max-w-xs truncate">{{ $testimonial->text }}</td>
                                <td class="py-4 px-4">
                                    <div class="flex text-warning">
                                        @for($i = 0; $i < $testimonial->rating; $i++)
                                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                        @endfor
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal({{ json_encode($testimonial) }})" class="p-2 text-on-surface-variant hover:text-primary" title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('admin.community.testimonials.destroy', $testimonial) }}" class="m-0 p-0 flex items-center justify-end" onsubmit="return confirm('Delete this testimonial?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-on-surface-variant hover:text-error" title="Delete"><span class="material-symbols-outlined text-sm">delete</span></button>
                                        </form>
                                    </div>
                                </td>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">No testimonials added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-on-surface-variant/50 backdrop-blur-sm p-4">
        <div @click.outside="isEditModalOpen = false" class="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto shadow-2xl">
            <h3 class="font-headline-md text-xl font-bold mb-6">Edit Testimonial</h3>
            <form :action="'/admin/community/testimonials/' + editingTestimonial?.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-bold mb-2">Author Name</label>
                    <input type="text" name="author_name" x-model="editingTestimonial.author_name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Testimonial Text</label>
                    <textarea name="text" x-model="editingTestimonial.text" required rows="4" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Rating</label>
                    <select name="rating" x-model="editingTestimonial.rating" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Avatar (Optional)</label>
                    <input type="file" name="avatar_file" accept="image/*" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <p class="text-xs text-on-surface-variant mt-1">Upload a new image to replace the existing one.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Video Testimonial (Optional)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <p class="text-xs text-on-surface-variant mt-1">Upload a new video to replace the existing one.</p>
                </div>
                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-outline-variant">
                    <button type="button" @click="isEditModalOpen = false" class="btn-outline flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testimonialManager() {
    return {
        isEditModalOpen: false,
        editingTestimonial: {},
        openEditModal(testimonial) {
            this.editingTestimonial = testimonial;
            this.isEditModalOpen = true;
        }
    }
}
</script>
@endsection

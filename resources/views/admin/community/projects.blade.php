@extends('layouts.dashboard')

@section('title', 'Community Projects')

@section('content')
<div x-data="projectManager()" class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Featured Projects</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage the projects featured on the Community page.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-primary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 md:grid-cols-3 gap-5 md:p-8">
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_circle</span> Add Project
            </h2>
            <form method="POST" action="{{ route('admin.community.projects.store') }}" class="space-y-4" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="block text-sm font-bold mb-2">Project Title</label>
                    <input type="text" name="title" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. My Cool Game">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Author Name</label>
                    <input type="text" name="author_name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="e.g. Alex B.">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Category Badge</label>
                    <select name="category" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="">None</option>
                        <option value="Block">Block</option>
                        <option value="Bedrock">Bedrock</option>
                        <option value="HTML/CSS">HTML/CSS</option>
                        <option value="Python">Python</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Project URL (Optional)</label>
                    <input type="url" name="project_url" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Link to project">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Initial Views</label>
                        <input type="number" name="views_count" value="0" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Initial Likes</label>
                        <input type="number" name="likes_count" value="0" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Thumbnail Video</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" required class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <p class="text-xs text-on-surface-variant mt-1">Upload a looping video, animation, or image.</p>
                </div>
                <button type="submit" class="btn-primary w-full">Add Project</button>
            </form>
        </div>

        <div class="lg:col-span-2 clay-card p-5 md:p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-outline-variant">
                            <th class="py-4 px-4 font-bold text-on-surface">Thumbnail</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Title & Author</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Category</th>
                            <th class="py-4 px-4 font-bold text-on-surface">Stats</th>
                            <th class="py-4 px-4 font-bold text-on-surface text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                <td class="py-4 px-4">
                                    @php $ext = strtolower(pathinfo($project->video_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                        <img src="{{ Str::startsWith($project->video_path, 'http') ? $project->video_path : Storage::disk('public')->url($project->video_path) }}" alt="{{ $project->title }}" class="w-24 h-16 object-cover rounded-xl shadow-sm">
                                    @else
                                        <video src="{{ Str::startsWith($project->video_path, 'http') ? $project->video_path : Storage::disk('public')->url($project->video_path) }}" autoplay loop muted playsinline class="w-24 h-16 object-cover rounded-xl shadow-sm"></video>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold">{{ $project->title }}</div>
                                    <div class="text-sm text-on-surface-variant">by {{ $project->author_name }}</div>
                                </td>
                                <td class="py-4 px-4 text-sm">{{ $project->category ?? '-' }}</td>
                                <td class="py-4 px-4 text-xs">
                                    <div class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">visibility</span> {{ $project->views_count }}</div>
                                    <div class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">favorite</span> {{ $project->likes_count }}</div>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal({{ json_encode($project) }})" class="p-2 text-on-surface-variant hover:text-primary" title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('admin.community.projects.destroy', $project) }}" class="m-0 p-0 flex items-center justify-end" onsubmit="return confirm('Delete this project?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-on-surface-variant hover:text-error" title="Delete"><span class="material-symbols-outlined text-sm">delete</span></button>
                                        </form>
                                    </div>
                                </td>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-on-surface-variant">No projects added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-on-surface-variant/50 backdrop-blur-sm p-4">
        <div @click.outside="isEditModalOpen = false" class="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto shadow-2xl">
            <h3 class="font-headline-md text-xl font-bold mb-6">Edit Project</h3>
            <form :action="'/admin/community/projects/' + editingProject?.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-bold mb-2">Project Title</label>
                    <input type="text" name="title" x-model="editingProject.title" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Author Name</label>
                    <input type="text" name="author_name" x-model="editingProject.author_name" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Category Badge</label>
                    <select name="category" x-model="editingProject.category" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="">None</option>
                        <option value="Block">Block</option>
                        <option value="Bedrock">Bedrock</option>
                        <option value="HTML/CSS">HTML/CSS</option>
                        <option value="Python">Python</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Project URL</label>
                    <input type="url" name="project_url" x-model="editingProject.project_url" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Views</label>
                        <input type="number" name="views_count" x-model="editingProject.views_count" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Likes</label>
                        <input type="number" name="likes_count" x-model="editingProject.likes_count" min="0" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Thumbnail Media (Optional)</label>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <p class="text-xs text-on-surface-variant mt-1">Upload a new video or image to replace the existing one.</p>
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
function projectManager() {
    return {
        isEditModalOpen: false,
        editingProject: {},
        openEditModal(project) {
            this.editingProject = project;
            this.isEditModalOpen = true;
        }
    }
}
</script>
@endsection

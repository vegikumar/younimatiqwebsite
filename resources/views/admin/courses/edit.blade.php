@extends('layouts.dashboard')

@section('title', 'Edit Course')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Edit Course: {{ $course->title }}</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Update the full details of this course.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="clay-card p-5 md:p-8">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Category</label>
                <select name="category_ids[]" multiple class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ in_array($category->id, old('category_ids', $course->categories->pluck('id')->toArray())) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title', $course->title) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Slug (optional)</label>
                <input type="text" name="slug" value="{{ old('slug', $course->slug) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="auto-generated from title if blank">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Description</label>
                <textarea name="description" rows="5" class="rich-text w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('description', $course->description) }}</textarea>
            </div>

            <!-- Syllabus Modules Section -->
            @php
                $existingModules = \App\Models\CourseModule::where('course_id', $course->id)->whereNull('unit_id')->orderBy('order')->get();
                $modulesData = $existingModules->map(function($m) {
                    return [
                        'id' => $m->id,
                        'class_name' => $m->title,
                        'description' => $m->description ?? $m->content_text ?? '',
                        'outcome' => $m->learning_outcome ?? '',
                        'practical_video_url' => $m->practical_video_url ?? ''
                    ];
                });
                // Ensure at least 8 rows if empty
                if ($modulesData->isEmpty()) {
                    for($i=0; $i<8; $i++) {
                        $modulesData->push(['id' => time() + $i, 'class_name' => '', 'description' => '', 'outcome' => '', 'practical_video_url' => '']);
                    }
                }
            @endphp
            <div class="mb-6 space-y-4" x-data="syllabusEditor({{ json_encode($modulesData) }})">
                <div class="flex flex-wrap items-center justify-between border-t border-outline-variant pt-6 gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <label class="block font-bold text-lg text-on-surface">Syllabus / Class Topics</label>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary" x-text="`${rows.length} Classes`"></span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-0.5">Upload a CSV to instantly populate 24 to 100+ classes, or manage them below.</p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- CSV File Upload Trigger -->
                        <label class="cursor-pointer text-secondary font-bold flex items-center gap-1 bg-secondary/10 hover:bg-secondary/20 px-3 py-1.5 rounded-lg transition-colors text-xs">
                            <span class="material-symbols-outlined text-sm">upload_file</span> Upload Syllabus CSV
                            <input type="file" accept=".csv,text/csv,text/plain" class="hidden" @change="handleCsvUpload($event)">
                        </label>

                        <a href="{{ route('admin.courses.sample-csv', ['type' => 'syllabus']) }}" class="text-on-surface-variant font-bold flex items-center gap-1 hover:text-primary bg-surface-container px-3 py-1.5 rounded-lg transition-colors text-xs" title="Download Syllabus CSV template">
                            <span class="material-symbols-outlined text-sm">download</span> Sample CSV
                        </a>

                        <button type="button" @click="rows.push({ id: Date.now(), class_name: '', description: '', outcome: '', practical_video_url: '' })" class="text-primary font-bold flex items-center gap-1 hover:underline bg-primary/10 px-3 py-1.5 rounded-lg transition-colors text-xs">
                            <span class="material-symbols-outlined text-sm">add</span> Add Class
                        </button>

                        <button type="button" @click="if(confirm('Clear all classes?')) rows = []" class="text-error font-bold flex items-center gap-1 hover:bg-error/10 px-2 py-1.5 rounded-lg transition-colors text-xs" title="Clear all classes">
                            <span class="material-symbols-outlined text-sm">delete_sweep</span>
                        </button>
                    </div>
                </div>

                <template x-if="csvUploadMsg">
                    <div class="p-3 bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary rounded-xl text-xs font-bold flex items-center justify-between">
                        <span x-text="csvUploadMsg"></span>
                        <button type="button" @click="csvUploadMsg = ''" class="text-on-tertiary-container hover:text-tertiary">&times;</button>
                    </div>
                </template>
                
                <div class="overflow-x-auto border border-outline-variant rounded-xl bg-white shadow-sm max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-container-low text-on-surface border-b border-outline-variant sticky top-0 z-10">
                            <tr>
                                <th class="p-3 font-bold text-center w-12">#</th>
                                <th class="p-3 font-bold w-1/4">Class Name</th>
                                <th class="p-3 font-bold w-1/3">Description</th>
                                <th class="p-3 font-bold w-1/3">Learning Outcome</th>
                                <th class="p-3 font-bold w-1/4">Practical Video</th>
                                <th class="p-3 font-bold text-center w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr class="border-b border-outline-variant last:border-0 hover:bg-surface-container-low transition-colors">
                                    <td class="p-3 font-medium text-on-surface-variant text-center" x-text="index + 1"></td>
                                    <td class="p-3">
                                        <input type="text" :name="`modules[${index}][title]`" x-model="row.class_name" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm" placeholder="e.g. Intro to AI">
                                    </td>
                                    <td class="p-3">
                                        <textarea :name="`modules[${index}][description]`" x-model="row.description" rows="2" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all resize-none text-sm" placeholder="Brief description of this class"></textarea>
                                    </td>
                                    <td class="p-3">
                                        <input type="text" :name="`modules[${index}][learning_outcome]`" x-model="row.outcome" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm" placeholder="What they will achieve">
                                    </td>
                                    <td class="p-3">
                                        <input type="text" :name="`modules[${index}][practical_video_url]`" x-model="row.practical_video_url" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm" placeholder="https://... (optional)">
                                    </td>
                                    <td class="p-3 text-center">
                                        <button type="button" @click="rows.splice(index, 1)" class="text-outline hover:text-error transition-colors p-1.5 rounded-lg hover:bg-error/10" title="Remove">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap justify-between items-center gap-3 pt-1 px-1">
                    <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                        <span>Showing <strong x-text="rows.length"></strong> classes in syllabus</span>
                        <span class="text-outline-variant">•</span>
                        <button type="button" @click="for(let i=0; i<16; i++) rows.push({ id: Date.now() + Math.random(), class_name: '', description: '', outcome: '', practical_video_url: '' })" class="text-primary font-bold hover:underline">
                            + Add 16 more rows
                        </button>
                    </div>

                    <!-- Add Class button at bottom right -->
                    <button type="button" @click="rows.push({ id: Date.now(), class_name: '', description: '', outcome: '', practical_video_url: '' })" class="text-primary font-bold flex items-center gap-1.5 hover:bg-primary/20 bg-primary/10 px-4 py-2 rounded-xl transition-all shadow-sm text-sm">
                        <span class="material-symbols-outlined text-base">add</span> Add Class
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Per Class ($) *</label>
                    <input type="number" step="0.01" name="price_usd" value="{{ old('price_usd', $course->price_usd ?? 18) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Per Class (AED) *</label>
                    <input type="number" step="0.01" name="price_aed" value="{{ old('price_aed', $course->price_aed ?? 30) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Per Class (INR) *</label>
                    <input type="number" step="0.01" name="price_inr" value="{{ old('price_inr', $course->price_inr ?? 600) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Course Type</label>
                    <select name="course_type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
                        <option value="live" {{ old('course_type', $course->course_type) === 'live' ? 'selected' : '' }}>Live</option>
                        <option value="self-paced" {{ old('course_type', $course->course_type) === 'self-paced' ? 'selected' : '' }}>Self-paced</option>
                    </select>
                    <label class="mt-2 flex items-center gap-2 cursor-pointer text-xs text-on-surface-variant">
                        <input type="checkbox" name="self_paced_enabled" value="1" {{ old('self_paced_enabled', $course->self_paced_enabled) ? 'checked' : '' }} class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary">
                        Offer self-paced option at checkout (buyer chooses Live or Self-paced)
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Listing Format</label>
                    <select name="format" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="1to1" {{ old('format', $course->format) === '1to1' ? 'selected' : '' }}>1 to 1 (1:1)</option>
                        <option value="group" {{ old('format', $course->format) === 'group' ? 'selected' : '' }}>Group (1:M)</option>
                        <option value="tutoring" {{ old('format', $course->format) === 'tutoring' ? 'selected' : '' }}>Tutoring</option>
                        <option value="club" {{ old('format', $course->format) === 'club' ? 'selected' : '' }}>Club</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Grade Band</label>
                <p class="text-xs text-on-surface-variant -mt-1 mb-2">Select every age range this course suits. You can pick more than one.</p>
                <input type="hidden" name="age_groups_present" value="1">
                <div class="flex flex-wrap gap-2">
                    @php $selectedAgeGroups = $selectedAgeGroups ?? collect(); @endphp
                    @forelse($ageGroups as $ag)
                        @php $checked = in_array((string) $ag->id, array_map('strval', (array) $selectedAgeGroups), true); @endphp
                        <label class="cursor-pointer">
                            <input type="checkbox" name="age_groups[]" value="{{ $ag->id }}" @checked($checked) class="peer sr-only">
                            <span class="inline-flex items-center px-4 py-2 rounded-full border border-outline-variant bg-surface-container-low text-sm font-medium text-on-surface-variant peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary transition-colors">
                                {{ $ag->name }}
                            </span>
                        </label>
                    @empty
                        <span class="text-sm text-on-surface-variant">No age bands yet. <a href="{{ route('admin.age-groups') }}" class="text-primary underline">Add age bands</a>.</span>
                    @endforelse
                </div>
            </div>



            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Learning Outcomes (One per line)</label>
                <textarea name="learning_outcomes" rows="4" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('learning_outcomes', implode("\n", $course->learning_outcomes ?? [])) }}</textarea>
            </div>


            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Thumbnail -->
                <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl"
                     x-data="{ imageUrl: '{{ old('thumbnail_url', $course->thumbnail_url) }}', previewFile(event) { const file = event.target.files[0]; if(file) { this.imageUrl = URL.createObjectURL(file); } } }">
                    <label class="block text-base font-bold mb-4 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">image</span> Thumbnail
                    </label>
                    <div class="space-y-4">
                        <template x-if="imageUrl">
                            <div class="mb-4">
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Preview</span>
                                <div class="relative w-full rounded-xl overflow-hidden border border-outline-variant/50 bg-surface-container flex items-center justify-center p-2">
                                    <img :src="imageUrl" class="max-w-full h-auto max-h-64 object-contain rounded-lg">
                                </div>
                                @if($course->thumbnail_url)
                                    <label class="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                        <input type="checkbox" name="remove_thumbnail" value="1" class="rounded border-outline-variant text-error focus:ring-error" @change="if($event.target.checked) imageUrl = ''">
                                        Remove existing thumbnail
                                    </label>
                                @endif
                            </div>
                        </template>

                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Image Web Link / URL</span>
                            <input type="text" name="thumbnail_url" value="{{ old('thumbnail_url', $course->thumbnail_url) }}" placeholder="https://example.com/image.jpg" class="w-full p-2.5 bg-white border border-outline-variant/50 rounded-xl text-sm outline-none focus:border-primary mb-3" @input="imageUrl = $event.target.value">
                        </div>

                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">OR Upload Thumbnail Image File</span>
                            <input type="file" name="thumbnail_file" accept="image/*" @change="previewFile" class="w-full p-2 bg-white border border-outline-variant/50 rounded-xl text-sm file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                        </div>
                    </div>
                </div>
                
                <!-- Promo Video -->
                <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl">
                    <label class="block text-base font-bold mb-4 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">smart_display</span> Promo Video File / Link
                    </label>
                    <div class="space-y-4">
                        @if($course->video_url)
                            <div class="mb-4">
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Current Promo Video</span>
                                @if(str_contains($course->video_url, 'youtube.com') || str_contains($course->video_url, 'youtu.be'))
                                    <a href="{{ $course->video_url }}" target="_blank" class="text-primary hover:underline">View YouTube Video</a>
                                @else
                                    <video x-data="{}" x-init="
                                        if (typeof Hls !== 'undefined' && Hls.isSupported() && '{{ $course->video_url }}'.includes('.m3u8')) {
                                            const hls = new Hls();
                                            hls.loadSource('{{ $course->video_url }}');
                                            hls.attachMedia($el);
                                        } else {
                                            $el.src = '{{ $course->video_url }}';
                                        }
                                    " controls class="w-full max-h-64 rounded-xl border border-outline-variant/50 bg-black"></video>
                                @endif
                                <label class="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                    <input type="checkbox" name="remove_video" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                    Remove existing promo video
                                </label>
                            </div>
                        @endif
                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Promo Video Web Link / URL</span>
                            <input type="text" name="video_url" value="{{ old('video_url', $course->video_url) }}" placeholder="https://youtube.com/... or direct MP4 link" class="w-full p-2.5 bg-white border border-outline-variant/50 rounded-xl text-sm outline-none focus:border-primary mb-3">
                        </div>
                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">OR Upload Video File</span>
                            <input type="file" name="video_file" accept="video/*" class="w-full p-2 bg-white border border-outline-variant/50 rounded-xl text-sm file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-6 grid grid-cols-1 gap-6">
                <!-- Welcome Video -->
                <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl">
                    <label class="block text-base font-bold mb-4 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">waving_hand</span> Welcome Video File / Link
                    </label>
                    <div class="space-y-4">
                        @if($course->welcome_video_url)
                            <div class="mb-4">
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Current Welcome Video</span>
                                @if(str_contains($course->welcome_video_url, 'youtube.com') || str_contains($course->welcome_video_url, 'youtu.be'))
                                    <a href="{{ $course->welcome_video_url }}" target="_blank" class="text-primary hover:underline">View YouTube Video</a>
                                @else
                                    <video x-data="{}" x-init="
                                        if (typeof Hls !== 'undefined' && Hls.isSupported() && '{{ $course->welcome_video_url }}'.includes('.m3u8')) {
                                            const hls = new Hls();
                                            hls.loadSource('{{ $course->welcome_video_url }}');
                                            hls.attachMedia($el);
                                        } else {
                                            $el.src = '{{ $course->welcome_video_url }}';
                                        }
                                    " controls class="w-full max-h-64 rounded-xl border border-outline-variant/50 bg-black"></video>
                                @endif
                                <label class="flex items-center gap-2 mt-2 text-sm text-error cursor-pointer">
                                    <input type="checkbox" name="remove_welcome_video" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                    Remove existing welcome video
                                </label>
                            </div>
                        @endif
                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">Welcome Video Web Link / URL</span>
                            <input type="text" name="welcome_video_url" value="{{ old('welcome_video_url', $course->welcome_video_url) }}" placeholder="https://youtube.com/... or direct MP4 link" class="w-full p-2.5 bg-white border border-outline-variant/50 rounded-xl text-sm outline-none focus:border-primary mb-3">
                        </div>
                        <div>
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 block">OR Upload Welcome Video File</span>
                            <input type="file" name="welcome_video_file" accept="video/*" class="w-full p-2 bg-white border border-outline-variant/50 rounded-xl text-sm file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">Welcome Message</label>
                <textarea name="welcome_message" rows="3" class="rich-text w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('welcome_message', $course->welcome_message) }}</textarea>
            </div>

            <div class="clay-card p-6 mb-6 bg-surface-container">
                <h3 class="font-bold text-lg mb-4 text-on-surface">Display Settings</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_top_course" value="1" {{ old('is_top_course', $course->is_top_course) ? 'checked' : '' }} class="w-5 h-5 text-primary rounded border-outline-variant focus:ring-primary">
                    <span class="text-sm font-bold text-on-surface">Show in "Top Courses" section on homepage</span>
                </label>
            </div>

            <div class="clay-card p-6 mb-6 bg-surface-container">
                <h3 class="font-bold text-lg mb-4 text-on-surface">SEO & Publishing</h3>
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold mb-2">SEO Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $course->seo_metadata['title'] ?? '') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">SEO Description</label>
                        <textarea name="seo_description" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">{{ old('seo_description', $course->seo_metadata['description'] ?? '') }}</textarea>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold mb-2">Publish Date (Schedule)</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $course->published_at?->format('Y-m-d\TH:i')) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" name="status" value="published" class="btn-primary">Update Course</button>
                <button type="submit" name="status" value="draft" class="px-6 py-3 bg-warning hover:bg-warning text-white font-bold rounded-xl inline-flex items-center gap-2 shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-sm">save_as</span> Save as Draft
                </button>
                <a href="{{ route('admin.courses') }}" class="px-6 py-3 bg-surface-container text-on-surface font-bold rounded-xl inline-flex items-center justify-center border border-outline hover:bg-surface-container-high transition-colors">Cancel</a>
            </div>
        </form>
    </div>

    @php
        $emailAutomations = $emailAutomations ?? collect();
        $defaultAutomationId = old('email_automation_id', $emailAutomations->firstWhere('is_enabled', true)?->id ?? $emailAutomations->first()?->id);
    @endphp
    <div class="clay-card p-5 md:p-8 mt-8" x-data="{ tab: 'new_course', automationId: @js((string) ($defaultAutomationId ?? '')) }">
        <h2 class="font-headline-md font-bold mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">campaign</span>
            Email triggers
        </h2>
        <p class="text-sm text-on-surface-variant mb-5">
            Announce this course or send an offer using one of your Email Automations’ audiences, include/exclude lists, and branding.
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-secondary-container text-on-secondary-container text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-error-container text-on-error-container text-sm font-medium">{{ session('error') }}</div>
        @endif

        <div class="mb-5">
            <label class="block text-sm font-bold mb-2">Use audiences from</label>
            <select x-model="automationId" class="w-full md:w-96 p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                @forelse($emailAutomations as $auto)
                    <option value="{{ $auto->id }}">{{ $auto->name }}{{ $auto->is_enabled ? '' : ' (off)' }}</option>
                @empty
                    <option value="">No automations — create one first</option>
                @endforelse
            </select>
        </div>

        <div class="flex flex-wrap gap-2 mb-5">
            <button type="button" @click="tab='new_course'" :class="tab==='new_course' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface'" class="px-4 py-2 rounded-xl text-sm font-bold">New course announcement</button>
            <button type="button" @click="tab='offer'" :class="tab==='offer' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface'" class="px-4 py-2 rounded-xl text-sm font-bold">Offer / special price</button>
        </div>

        <form method="POST" action="{{ route('admin.email-automations.course.send', $course) }}" class="space-y-4" x-show="tab==='new_course'" @submit="if ($event.submitter?.value === 'audience') return confirm('Queue this new-course email to the selected automation audiences?');">
            @csrf
            <input type="hidden" name="type" value="new_course">
            <input type="hidden" name="email_automation_id" :value="automationId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', 'New class: '.$course->title) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Headline</label>
                    <input type="text" name="heading" value="{{ old('heading', 'New class just launched') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-2">Intro</label>
                    <textarea name="intro_text" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">{{ old('intro_text', 'We just added a new class families are going to love:') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Button text</label>
                    <input type="text" name="cta_text" value="{{ old('cta_text', 'View this class') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Test email</label>
                    <input type="email" name="test_email" value="{{ old('test_email', 'younimatiq@gmail.com') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a :href="'{{ route('admin.email-automations.course.preview', $course) }}?type=new_course&email_automation_id=' + automationId" target="_blank" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Preview</a>
                <button type="submit" name="mode" value="test" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                <button type="submit" name="mode" value="audience" class="btn-primary">Send to audiences</button>
            </div>
        </form>

        <form id="offer-trigger-form" method="POST" action="{{ route('admin.email-automations.course.send', $course) }}" class="space-y-4" x-show="tab==='offer'" x-cloak @submit="if ($event.submitter?.value === 'audience') return confirm('Queue this offer email to the selected automation audiences?');">
            @csrf
            <input type="hidden" name="type" value="offer">
            <input type="hidden" name="email_automation_id" :value="automationId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', 'Special offer: '.$course->title) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Headline</label>
                    <input type="text" name="heading" value="{{ old('heading', 'A special offer for your family') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-2">Intro</label>
                    <textarea name="intro_text" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">{{ old('intro_text', 'For a limited time, this class is available at a special price:') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Original price (USD / class)</label>
                    <input type="number" step="0.01" min="0" name="original_price" value="{{ old('original_price', $course->price_usd ?? 18) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Offer price (USD / class)</label>
                    <input type="number" step="0.01" min="0" name="offer_price" value="{{ old('offer_price') }}" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary" placeholder="e.g. 9.00">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-2">Offer note</label>
                    <input type="text" name="offer_note" value="{{ old('offer_note', 'Limited seats · Book this week') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Button text</label>
                    <input type="text" name="cta_text" value="{{ old('cta_text', 'Claim this offer') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Test email</label>
                    <input type="email" name="test_email" value="{{ old('test_email', 'younimatiq@gmail.com') }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" @click="previewOfferEmail(automationId)" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Preview</button>
                <button type="submit" name="mode" value="test" class="px-4 py-2 rounded-xl border border-outline-variant font-bold text-sm">Send test</button>
                <button type="submit" name="mode" value="audience" class="btn-primary">Send offer to audiences</button>
            </div>
        </form>
    </div>
    <script>
    function previewOfferEmail(automationId) {
        const form = document.getElementById('offer-trigger-form');
        const params = new URLSearchParams({
            type: 'offer',
            email_automation_id: automationId || '',
            subject: form.subject.value || '',
            heading: form.heading.value || '',
            intro_text: form.intro_text.value || '',
            cta_text: form.cta_text.value || '',
            original_price: form.original_price.value || '',
            offer_price: form.offer_price.value || '',
            offer_note: form.offer_note.value || '',
        });
        window.open(@json(route('admin.email-automations.course.preview', $course)) + '?' + params.toString(), '_blank');
    }
    </script>


    <!-- Syllabus Builder Ported from Tutor -->
    <div class="clay-card p-5 md:p-8 mt-10">
        <h2 class="font-headline-xl text-headline-sm text-on-surface mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">view_list</span> Syllabus Units & Modules
        </h2>

        <!-- Syllabus Units List -->
        @if($course->units && $course->units->count() > 0)
            <div class="space-y-4 mb-8">
                @foreach($course->units as $unit)
                    <div class="p-5 border border-outline-variant/30 rounded-2xl bg-surface-container-low/50" x-data="{ editingUnit: false }">
                        <div class="flex items-center justify-between mb-1">
                            <div class="font-bold text-base text-on-surface flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">{{ $unit->order }}</span>
                                {{ $unit->title }}
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="editingUnit = !editingUnit" class="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-sm">edit</span> Edit
                                </button>
                                <form method="POST" action="{{ route('admin.courses.units.destroy', $unit->id) }}" onsubmit="return confirm('Delete unit \'{{ $unit->title }}\' and all its lessons?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                                        <span class="material-symbols-outlined text-sm">delete</span> Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Unit Form Accordion -->
                        <div x-show="editingUnit" x-collapse class="mt-3 p-4 bg-white border border-outline-variant/30 rounded-xl space-y-3 mb-3">
                            <h6 class="text-xs font-bold text-on-surface uppercase">Edit Unit Details</h6>
                            <form method="POST" action="{{ route('admin.courses.units.update', $unit->id) }}" class="flex flex-col sm:flex-row gap-3">
                                @csrf @method('PUT')
                                <input type="text" name="title" value="{{ $unit->title }}" required class="flex-1 px-3 py-2 border rounded-xl text-sm">
                                <input type="text" name="description" value="{{ $unit->description }}" placeholder="Description" class="flex-1 px-3 py-2 border rounded-xl text-sm">
                                <button type="submit" class="btn-primary py-2 px-4 text-xs font-bold whitespace-nowrap">Save</button>
                                <button type="button" @click="editingUnit = false" class="px-3 py-2 bg-surface-container-low text-on-surface text-xs font-bold rounded-xl">Cancel</button>
                            </form>
                        </div>

                        @if($unit->description)
                            <p class="text-xs text-on-surface-variant mb-3">{{ $unit->description }}</p>
                        @endif

                        <!-- Unit Modules List -->
                        <div class="space-y-2 pl-4 border-l-2 border-primary/20 mt-3">
                            @forelse($unit->modules as $mod)
                                <div class="p-3 bg-white rounded-xl border border-outline-variant/20 text-sm space-y-2" x-data="{ editingMod: false }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-on-surface">{{ $mod->title }}</span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-surface-container text-on-surface-variant uppercase font-bold">{{ $mod->content_type }}</span>
                                            @if($mod->duration_minutes)
                                                <span class="text-xs text-on-surface-variant font-medium">⏱️ {{ $mod->duration_minutes }} min</span>
                                            @endif
                                            @if($mod->content_url)
                                                <a href="{{ $mod->content_url }}" target="_blank" class="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                                                    <span class="material-symbols-outlined text-xs">file_present</span> Attached File
                                                </a>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="editingMod = !editingMod" class="text-xs text-primary font-bold hover:underline flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-xs">edit</span> Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.courses.modules.destroy', $mod->id) }}" onsubmit="return confirm('Delete lesson \'{{ $mod->title }}\'?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                                                    <span class="material-symbols-outlined text-xs">delete</span> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Edit Module Inline Form -->
                                    <div x-show="editingMod" x-collapse class="mt-2 pt-3 border-t border-outline-variant">
                                        <form method="POST" action="{{ route('admin.courses.modules.update', $mod->id) }}" enctype="multipart/form-data" class="space-y-3">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="course_unit_id" value="{{ $unit->id }}">
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                <input type="text" name="title" value="{{ $mod->title }}" required class="px-3 py-1.5 border rounded-lg text-xs">
                                                <select name="module_type" required class="px-3 py-1.5 border rounded-lg text-xs">
                                                    <option value="video" {{ $mod->content_type === 'video' ? 'selected' : '' }}>Video</option>
                                                    <option value="image" {{ $mod->content_type === 'image' ? 'selected' : '' }}>Image</option>
                                                    <option value="pdf" {{ $mod->content_type === 'pdf' ? 'selected' : '' }}>PDF Document</option>
                                                    <option value="text" {{ $mod->content_type === 'text' ? 'selected' : '' }}>Text / Reading</option>
                                                    <option value="assignment" {{ $mod->content_type === 'assignment' ? 'selected' : '' }}>Post class Activity</option>
                                                </select>
                                                <input type="number" name="duration_minutes" value="{{ $mod->duration_minutes }}" placeholder="Duration (min)" class="px-3 py-1.5 border rounded-lg text-xs">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="file" name="content_file" accept="video/*,image/*,application/pdf" class="flex-1 text-xs file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-surface-container-low">
                                                <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container">Save</button>
                                                <button type="button" @click="editingMod = false" class="px-3 py-1.5 bg-surface-container-low text-on-surface text-xs font-bold rounded-lg">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-on-surface-variant italic">No lessons in this unit yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Add Unit Form -->
        <div class="p-4 bg-surface-container-low border border-outline-variant/20 rounded-2xl space-y-3 mb-6">
            <h5 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-primary">add</span> Add Syllabus Unit
            </h5>
            <form method="POST" action="{{ route('admin.courses.units.store', $course->id) }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="title" placeholder="Unit Title (e.g. Unit 1: Foundations)" required class="flex-1 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                <input type="text" name="description" placeholder="Description (Optional)" class="flex-1 px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                <button type="submit" class="btn-primary py-2 px-4 text-xs font-bold whitespace-nowrap">Add Unit</button>
            </form>
        </div>

        <!-- Add Module Form -->
        <div class="p-4 bg-primary-container/20 border border-primary/20 rounded-2xl space-y-3">
            <h5 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-primary">post_add</span> Add Lesson / Content Block
            </h5>
            <form method="POST" action="{{ route('admin.courses.modules.store', $course->id) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" name="title" placeholder="Lesson Title" required class="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                    <select name="course_unit_id" class="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" required>
                        <option value="">Select Unit *</option>
                        @foreach($course->units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->title }}</option>
                        @endforeach
                    </select>
                    <select name="module_type" required class="px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                        <option value="video">Video</option>
                        <option value="image">Image</option>
                        <option value="text">Text / Reading</option>
                        <option value="pdf">PDF Document</option>
                        <option value="assignment">Post class Activity</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 items-center">
                    <div class="flex-1 w-full" id="fileUploadContainer">
                        <label class="block text-xs font-bold text-on-surface mb-1">Upload Video / Image / PDF File (practice sheet for Post class Activity)</label>
                        <input type="file" name="content_file" class="w-full px-3 py-1.5 bg-white border border-outline-variant/30 rounded-xl text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                    </div>
                    <div class="flex-1 w-full hidden" id="textContentContainer">
                        <label class="block text-xs font-bold text-on-surface mb-1">Text Content (For Text/Reading/Assignment)</label>
                        <textarea name="content_text" rows="2" class="w-full px-4 py-2 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none" placeholder="Enter text content here..."></textarea>
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="block text-xs font-bold text-on-surface mb-1">Duration (min)</label>
                        <input type="number" name="duration_minutes" placeholder="e.g. 15" class="w-full px-4 py-1.5 bg-white border border-outline-variant/30 rounded-xl text-sm outline-none">
                    </div>
                    <div class="w-full sm:w-auto self-end">
                        <button type="submit" class="btn-primary w-full py-1.5 px-6 font-bold">Add Lesson</button>
                    </div>
                </div>
                <script>
                    document.querySelector('select[name="module_type"]').addEventListener('change', function() {
                        const type = this.value;
                        const fileContainer = document.getElementById('fileUploadContainer');
                        const textContainer = document.getElementById('textContentContainer');
                        if(type === 'text') {
                            fileContainer.classList.add('hidden');
                            textContainer.classList.remove('hidden');
                        } else if(type === 'assignment') {
                            fileContainer.classList.remove('hidden');
                            textContainer.classList.remove('hidden');
                        } else {
                            fileContainer.classList.remove('hidden');
                            textContainer.classList.add('hidden');
                        }
                    });
                </script>
            </form>
        </div>
    </div>
</div>

<style>
.ck-editor__editable {
    min-height: 150px;
}
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
    document.querySelectorAll('.rich-text').forEach(el => {
        ClassicEditor.create(el).catch(error => { console.error(error); });
    });

    function syllabusEditor(initialRows) {
        return {
            rows: initialRows || [],
            csvUploadMsg: '',

            handleCsvUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    const text = e.target.result;
                    const parsedRows = this.parseCsvText(text);

                    if (parsedRows.length > 0) {
                        this.rows = parsedRows;
                        this.csvUploadMsg = `✅ Successfully loaded ${parsedRows.length} classes from your CSV! Remember to click "Update Course" or "Save as Draft" to save.`;
                    } else {
                        alert('Could not find class records in the CSV. Please ensure your CSV has "class_title" or "class_name" columns.');
                    }
                };
                reader.readAsText(file);
                event.target.value = '';
            },

            parseCsvText(text) {
                text = text.replace(/^\uFEFF/, '');

                // Detect delimiter from the header line
                const firstBreak = text.search(/\r\n|\n|\r/);
                const firstLine = firstBreak === -1 ? text : text.slice(0, firstBreak);
                const delimiter = firstLine.includes('\t') ? '\t' : (firstLine.includes(';') ? ';' : ',');

                // Full CSV state machine: handles quoted fields, embedded
                // commas and multi-line descriptions.
                const records = [];
                let row = [];
                let field = '';
                let inQuotes = false;
                for (let i = 0; i < text.length; i++) {
                    const char = text[i];
                    if (inQuotes) {
                        if (char === '"') {
                            if (text[i + 1] === '"') { field += '"'; i++; }
                            else { inQuotes = false; }
                        } else {
                            field += char;
                        }
                    } else if (char === '"') {
                        inQuotes = true;
                    } else if (char === delimiter) {
                        row.push(field); field = '';
                    } else if (char === '\n' || char === '\r') {
                        if (char === '\r' && text[i + 1] === '\n') i++;
                        row.push(field); field = '';
                        records.push(row); row = [];
                    } else {
                        field += char;
                    }
                }
                if (field !== '' || row.length) { row.push(field); records.push(row); }

                // Drop blank rows and hint rows (starting with "##")
                const cleaned = records.filter(r => r.some(c => c.trim() !== '') && !r[0].trim().startsWith('##'));
                if (cleaned.length < 2) return [];

                const headers = cleaned[0].map(h => h.trim().toLowerCase().replace(/[\s\-\.]+/g, '_'));
                const findIdx = (aliases) => headers.findIndex(h => aliases.includes(h));

                const titleIdx = findIdx(['class_title', 'class_name', 'module_title', 'topic', 'title', 'lesson_title']);
                const descIdx = findIdx(['class_description', 'module_description', 'description', 'details']);
                const outcomeIdx = findIdx(['class_learning_outcome', 'learning_outcome', 'outcome', 'learning_outcomes']);
                const videoIdx = findIdx(['practical_video_url', 'practical_video', 'demo_video_url', 'demo_video']);

                if (titleIdx === -1) return [];

                const results = [];
                for (let i = 1; i < cleaned.length; i++) {
                    const cols = cleaned[i];
                    const title = (cols[titleIdx] || '').trim();
                    if (!title) continue;

                    results.push({
                        id: Date.now() + i,
                        class_name: title,
                        description: descIdx !== -1 ? (cols[descIdx] || '').trim() : '',
                        outcome: outcomeIdx !== -1 ? (cols[outcomeIdx] || '').trim() : '',
                        practical_video_url: videoIdx !== -1 ? (cols[videoIdx] || '').trim() : ''
                    });
                }

                return results;
            }
        };
    }
</script>
@endsection

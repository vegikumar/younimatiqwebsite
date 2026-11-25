@extends('layouts.dashboard')

@section('title', 'Create Class / Course')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-4xl mx-auto py-12" x-data="courseCreateForm()" x-init="initForm()">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="mb-8 border-b border-outline-variant pb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">add_circle</span> Create Class / Tutoring Listing
                </h2>
                <p class="font-body-md text-on-surface-variant mt-2">Set up a new listing for students to discover. You can save as draft anytime.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.courses.sample-csv', ['type' => 'bulk']) }}" class="px-3.5 py-2 bg-surface-container-high hover:bg-surface-container-highest border border-outline-variant text-on-surface text-xs font-bold rounded-xl inline-flex items-center gap-1.5 transition-all shadow-sm" title="Download sample CSV for bulk upload">
                    <span class="material-symbols-outlined text-sm text-primary">download</span> Bulk Sample CSV
                </a>
            </div>
        </div>

        <!-- Local Draft Recovery Banner -->
        <template x-if="hasSavedDraft">
            <div class="mb-6 p-4 bg-warning-container border-2 border-warning-container rounded-2xl flex flex-wrap items-center justify-between gap-3 text-warning shadow-sm animate-in fade-in">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-2xl text-warning">history_edu</span>
                    <div>
                        <p class="font-bold text-sm">Unsaved Draft Recovered!</p>
                        <p class="text-xs text-warning">We detected an unsaved course draft from your previous browser session (<span x-text="savedDraftTime"></span>).</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="restoreDraft()" class="px-4 py-2 bg-warning hover:bg-warning text-white rounded-xl text-xs font-bold transition-colors shadow-sm">Restore Draft</button>
                    <button type="button" @click="discardDraft()" class="px-4 py-2 bg-warning-container hover:bg-warning-container text-warning rounded-xl text-xs font-bold transition-colors">Discard</button>
                </div>
            </div>
        </template>

        @if($errors->any())
            <div class="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="course-create-form" method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="space-y-6" @submit="clearDraftStorage()">
            @csrf
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Class Title *</label>
                <input type="text" name="title" x-model="formData.title" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all" placeholder="e.g. AI for Young Innovators: 24-Week Mastery Program">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Slug (optional)</label>
                <input type="text" name="slug" x-model="formData.slug" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all" placeholder="auto-generated from title if blank">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Listing Format *</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center gap-2 p-3.5 border border-outline-variant rounded-xl cursor-pointer hover:border-primary/50 font-bold bg-surface-container-low transition-colors text-sm">
                        <input type="radio" name="format" value="1to1" x-model="formData.format" class="text-primary w-4 h-4">
                        1 to 1 (1:1)
                    </label>
                    <label class="flex items-center gap-2 p-3.5 border border-outline-variant rounded-xl cursor-pointer hover:border-primary/50 font-bold bg-surface-container-low transition-colors text-sm">
                        <input type="radio" name="format" value="group" x-model="formData.format" class="text-primary w-4 h-4">
                        Group (1:M)
                    </label>
                    <label class="flex items-center gap-2 p-3.5 border border-outline-variant rounded-xl cursor-pointer hover:border-primary/50 font-bold bg-surface-container-low transition-colors text-sm">
                        <input type="radio" name="format" value="tutoring" x-model="formData.format" class="text-primary w-4 h-4">
                        Tutoring
                    </label>
                    <label class="flex items-center gap-2 p-3.5 border border-outline-variant rounded-xl cursor-pointer hover:border-primary/50 font-bold bg-surface-container-low transition-colors text-sm">
                        <input type="radio" name="format" value="club" x-model="formData.format" class="text-primary w-4 h-4">
                        Club
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Course Type *</label>
                <select name="course_type" x-model="formData.course_type" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                    <option value="live">Live Sessions (Zoom)</option>
                    <option value="self-paced">Self-Paced Content</option>
                </select>
                <label class="flex items-center gap-2 cursor-pointer text-xs text-on-surface-variant">
                    <input type="checkbox" name="self_paced_enabled" value="1" x-model="formData.self_paced_enabled" class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary">
                    Offer self-paced option at checkout (buyer chooses Live or Self-paced)
                </label>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Description *</label>
                <textarea id="course-description-input" name="description" x-model="formData.description" rows="4" class="rich-text w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all resize-none" placeholder="What will students learn in this class?"></textarea>
            </div>

            <!-- Syllabus Modules Section (Supports 24 to 100+ classes & CSV Upload) -->
            <div class="space-y-4 pt-6 border-t border-outline-variant">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <label class="block font-bold text-lg text-on-surface">Syllabus / Class Topics</label>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary" x-text="`${rows.length} Classes`"></span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-0.5">Add classes individually or upload a CSV to populate all 24–100 classes in one click.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- CSV File Upload Trigger -->
                        <label class="cursor-pointer text-secondary font-bold flex items-center gap-1 bg-secondary/10 hover:bg-secondary/20 px-3 py-1.5 rounded-lg transition-colors text-xs">
                            <span class="material-symbols-outlined text-sm">upload_file</span> Upload Syllabus CSV
                            <input type="file" accept=".csv,text/csv,text/plain" class="hidden" @change="handleSyllabusCsv($event)">
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

                <!-- CSV Upload notification badge -->
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
                                        <input type="text" :name="`modules[${index}][title]`" x-model="row.class_name" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm" placeholder="e.g. Intro to Neural Networks">
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
                        <button type="button" @click="addMultipleRows(16)" class="text-primary font-bold hover:underline">
                            + Add 16 more rows
                        </button>
                    </div>

                    <!-- Add Class button at bottom right -->
                    <button type="button" @click="rows.push({ id: Date.now(), class_name: '', description: '', outcome: '', practical_video_url: '' })" class="text-primary font-bold flex items-center gap-1.5 hover:bg-primary/20 bg-primary/10 px-4 py-2 rounded-xl transition-all shadow-sm text-sm">
                        <span class="material-symbols-outlined text-base">add</span> Add Class
                    </button>
                </div>
            </div>

            <!-- Syllabus Units & Modules (Self-Paced) -->
            <template x-if="formData.course_type === 'self-paced' || formData.self_paced_enabled">
                <div class="space-y-4 pt-6 border-t border-outline-variant">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <label class="block font-bold text-lg text-on-surface">Syllabus Units &amp; Modules</label>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary/10 text-secondary" x-text="`${units.length} Units`"></span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-0.5">Group self-paced lessons into units. Each unit holds ordered modules (video, pdf, quiz, etc.).</p>
                        </div>
                        <button type="button" @click="addUnit()" class="text-secondary font-bold flex items-center gap-1 hover:bg-secondary/20 bg-secondary/10 px-3 py-1.5 rounded-lg transition-colors text-xs">
                            <span class="material-symbols-outlined text-sm">add</span> Add Unit
                        </button>
                    </div>

                    <template x-if="units.length === 0">
                        <div class="p-6 text-center border-2 border-dashed border-outline-variant rounded-xl text-sm text-on-surface-variant">
                            No units yet. Click <strong>Add Unit</strong> to build your self-paced syllabus.
                        </div>
                    </template>

                    <template x-for="(unit, ui) in units" :key="unit.id">
                        <div class="p-4 border border-outline-variant rounded-2xl bg-white shadow-sm space-y-3">
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold text-xs" x-text="ui + 1"></span>
                                <input type="text" :name="`units[${ui}][title]`" x-model="unit.title" required class="flex-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-secondary outline-none text-sm font-bold" placeholder="Unit title (e.g. Unit 1: Foundations)">
                                <button type="button" @click="removeUnit(ui)" class="text-outline hover:text-error p-1.5 rounded-lg hover:bg-error/10" title="Remove unit">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                            <textarea :name="`units[${ui}][description]`" x-model="unit.description" rows="2" class="w-full p-2.5 bg-surface-container-low border border-outline-variant rounded-lg focus:border-secondary outline-none resize-none text-sm" placeholder="Unit description (optional)"></textarea>

                            <div class="pl-4 border-l-2 border-secondary/20 space-y-2">
                                <template x-for="(mod, mi) in unit.modules" :key="mod.id">
                                    <div class="p-3 rounded-xl bg-surface-container-low space-y-2">
                                        <div class="grid grid-cols-12 gap-2 items-center">
                                            <input type="text" :name="`units[${ui}][modules][${mi}][title]`" x-model="mod.title" required class="col-span-5 p-2 border border-outline-variant rounded-lg text-sm outline-none focus:border-secondary" placeholder="Module title">
                                            <select :name="`units[${ui}][modules][${mi}][content_type]`" x-model="mod.content_type" class="col-span-3 p-2 border border-outline-variant rounded-lg text-sm outline-none focus:border-secondary">
                                                <option value="video">Video</option>
                                                <option value="pdf">PDF</option>
                                                <option value="quiz">Quiz</option>
                                                <option value="assignment">Assignment</option>
                                                <option value="image">Image</option>
                                                <option value="text">Text</option>
                                            </select>
                                            <input type="number" :name="`units[${ui}][modules][${mi}][duration_minutes]`" x-model="mod.duration_minutes" class="col-span-2 p-2 border border-outline-variant rounded-lg text-sm outline-none focus:border-secondary" placeholder="min">
                                            <button type="button" @click="unit.modules.splice(mi, 1)" class="col-span-2 text-outline hover:text-error p-1.5 rounded-lg hover:bg-error/10 justify-self-end" title="Remove module">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                        <textarea :name="`units[${ui}][modules][${mi}][description]`" x-model="mod.description" rows="2" class="w-full p-2 border border-outline-variant rounded-lg text-sm outline-none resize-none focus:border-secondary" placeholder="Module description (optional)"></textarea>
                                    </div>
                                </template>
                                <button type="button" @click="addUnitModule(unit)" class="text-secondary font-bold flex items-center gap-1 hover:underline text-xs">
                                    <span class="material-symbols-outlined text-sm">add</span> Add Module
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4 border-t border-outline-variant">
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Per Class Price ($) *</label>
                    <input type="number" step="0.01" name="price_usd" x-model="formData.price_usd" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Per Class Price (AED) *</label>
                    <input type="number" step="0.01" name="price_aed" x-model="formData.price_aed" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Per Class Price (INR) *</label>
                    <input type="number" step="0.01" name="price_inr" x-model="formData.price_inr" required class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block font-bold text-on-surface">Category</label>
                    <select name="category_ids[]" multiple class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                        @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Upload Course Thumbnail (Supports Link / URL OR File) -->
            <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                <label class="block font-bold text-on-surface flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">image</span> Course Thumbnail
                    </span>
                    <span class="text-xs text-on-surface-variant font-normal">Direct Link OR File Upload</span>
                </label>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">Image Web Link / URL</span>
                        <input type="text" name="thumbnail_url" x-model="formData.thumbnail_url" placeholder="https://example.com/image.jpg" class="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">OR Upload Image File</span>
                        <input type="file" name="thumbnail_file" accept="image/*" @change="previewImageFile($event)" class="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                    </div>
                </div>

                <template x-if="imagePreview || formData.thumbnail_url">
                    <div class="pt-2 flex items-center gap-3">
                        <span class="text-xs font-bold text-on-surface-variant">Preview:</span>
                        <img :src="imagePreview || formData.thumbnail_url" class="h-14 w-24 object-cover rounded-lg border border-outline-variant">
                    </div>
                </template>
            </div>

            <!-- Upload Course Video (Supports Link / URL OR File) -->
            <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                <label class="block font-bold text-on-surface flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">smart_display</span> Promo Video
                    </span>
                    <span class="text-xs text-on-surface-variant font-normal">YouTube / Vimeo / CDN link OR File</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">Video Web Link / URL</span>
                        <input type="text" name="video_url" x-model="formData.video_url" placeholder="https://youtube.com/watch?v=... or direct MP4 link" class="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">OR Upload Promo Video File</span>
                        <input type="file" name="video_file" accept="video/*" class="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                    </div>
                </div>
            </div>
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Grade Band</label>
                <p class="text-xs text-on-surface-variant -mt-1">Select every age range this course suits. You can pick more than one.</p>
                <input type="hidden" name="age_groups_present" value="1">
                <div class="flex flex-wrap gap-2">
                    @forelse($ageGroups ?? [] as $ageGroup)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="age_groups[]" value="{{ $ageGroup->id }}" x-model="formData.age_groups" class="peer sr-only">
                            <span class="inline-flex items-center px-4 py-2 rounded-full border border-outline-variant bg-surface-container-low text-sm font-medium text-on-surface-variant peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary transition-colors">
                                {{ $ageGroup->name }}
                            </span>
                        </label>
                    @empty
                        <span class="text-sm text-on-surface-variant">No age bands yet. <a href="{{ route('admin.age-groups') }}" class="text-primary underline">Add age bands</a>.</span>
                    @endforelse
                </div>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Welcome Message for New Families</label>
                <textarea name="welcome_message" x-model="formData.welcome_message" rows="2" class="rich-text w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all resize-none" placeholder="Auto-generated greeting post for newly enrolled learners..."></textarea>
            </div>

            <!-- Welcome Video (Supports Link / URL OR File) -->
            <div class="p-5 bg-surface-container-low border border-outline-variant rounded-2xl space-y-4">
                <label class="block font-bold text-on-surface flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">waving_hand</span> Welcome Video
                    </span>
                    <span class="text-xs text-on-surface-variant font-normal">Web Link OR File</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">Welcome Video Web Link / URL</span>
                        <input type="text" name="welcome_video_url" x-model="formData.welcome_video_url" placeholder="https://youtube.com/... or direct MP4 link" class="w-full p-3 bg-white border border-outline-variant rounded-xl text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">OR Upload Welcome Video File</span>
                        <input type="file" name="welcome_video_file" accept="video/*" class="w-full p-2 bg-white border border-outline-variant rounded-xl text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/20 transition-colors cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-outline-variant mb-6">
                <h3 class="font-bold text-lg mb-4 text-on-surface">SEO & Publishing</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div class="space-y-2">
                        <label class="block font-bold text-on-surface">Meta Title</label>
                        <input type="text" name="seo_title" x-model="formData.seo_title" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="block font-bold text-on-surface">Meta Description</label>
                        <textarea name="seo_description" x-model="formData.seo_description" rows="2" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <label class="block font-bold text-on-surface">Publish Date (Schedule)</label>
                    <input type="datetime-local" name="published_at" x-model="formData.published_at" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                </div>

                <h3 class="font-bold text-lg mb-4 text-on-surface mt-6">Display Settings</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_top_course" value="1" x-model="formData.is_top_course" class="w-5 h-5 text-primary rounded border-outline-variant focus:ring-primary">
                    <span class="text-sm font-bold text-on-surface">Show in "Top Courses" section on homepage</span>
                </label>
            </div>

            <!-- Form Action Buttons with Save as Draft Option -->
            <div class="pt-6 border-t border-outline-variant flex flex-wrap items-center gap-4">
                <button type="submit" name="status" value="published" class="btn-primary flex-1 py-4 text-base font-bold shadow-md">
                    Create & Publish Course
                </button>
                <button type="submit" name="status" value="draft" class="px-8 py-4 bg-warning hover:bg-warning text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-base">
                    <span class="material-symbols-outlined text-lg">draft</span> Save as Draft
                </button>
                <a href="{{ route('admin.courses') }}" class="px-8 py-4 bg-surface-container-high hover:bg-surface-container-highest rounded-xl font-bold transition-colors text-base">Cancel</a>
            </div>
        </form>
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
        ClassicEditor.create(el).then(editor => {
            editor.model.document.on('change:data', () => {
                el.value = editor.getData();
                el.dispatchEvent(new Event('input'));
            });
        }).catch(error => { console.error(error); });
    });

    function courseCreateForm() {
        return {
            hasSavedDraft: false,
            savedDraftTime: '',
            csvUploadMsg: '',
            imagePreview: '',
            formData: {
                title: '',
                slug: '',
                format: 'group',
                course_type: 'live',
                self_paced_enabled: false,
                description: '',
                price_usd: '18.00',
                price_aed: '30.00',
                price_inr: '600.00',
                thumbnail_url: '',
                video_url: '',
                age_groups: [],
                welcome_message: '',
                welcome_video_url: '',
                seo_title: '',
                seo_description: '',
                published_at: '',
                is_top_course: false
            },
            rows: Array.from({ length: 8 }, (_, i) => ({ id: Date.now() + i, class_name: '', description: '', outcome: '', practical_video_url: '' })),
            units: [],
            
            newModule() {
                return { id: Date.now() + Math.random(), title: '', content_type: 'video', duration_minutes: '', description: '' };
            },
            addUnit() {
                this.units.push({ id: Date.now() + Math.random(), title: '', description: '', modules: [this.newModule()] });
            },
            removeUnit(index) {
                this.units.splice(index, 1);
            },
            addUnitModule(unit) {
                unit.modules.push(this.newModule());
            },
            
            initForm() {
                const draft = localStorage.getItem('admin_create_course_draft');
                if (draft) {
                    try {
                        const parsed = JSON.parse(draft);
                        this.hasSavedDraft = true;
                        this.savedDraftTime = parsed._savedAt ? new Date(parsed._savedAt).toLocaleTimeString() : 'earlier';
                    } catch(e){}
                }

                // Debounced auto-save to localStorage every 3 seconds
                setInterval(() => {
                    this.autoSaveDraft();
                }, 3000);
            },

            autoSaveDraft() {
                // If title is entered, save draft
                if (this.formData.title || this.rows.some(r => r.class_name)) {
                    const toSave = {
                        formData: this.formData,
                        rows: this.rows,
                        _savedAt: new Date().toISOString()
                    };
                    localStorage.setItem('admin_create_course_draft', JSON.stringify(toSave));
                }
            },

            restoreDraft() {
                const draft = localStorage.getItem('admin_create_course_draft');
                if (draft) {
                    try {
                        const parsed = JSON.parse(draft);
                        if (parsed.formData) this.formData = Object.assign(this.formData, parsed.formData);
                        if (parsed.rows && parsed.rows.length) this.rows = parsed.rows;
                        this.hasSavedDraft = false;
                        this.csvUploadMsg = 'Draft successfully restored!';
                    } catch(e){}
                }
            },

            discardDraft() {
                localStorage.removeItem('admin_create_course_draft');
                this.hasSavedDraft = false;
            },

            clearDraftStorage() {
                localStorage.removeItem('admin_create_course_draft');
            },

            addMultipleRows(count) {
                for (let i = 0; i < count; i++) {
                    this.rows.push({ id: Date.now() + Math.random(), class_name: '', description: '', outcome: '', practical_video_url: '' });
                }
            },

            previewImageFile(event) {
                const file = event.target.files[0];
                if (file) {
                    this.imagePreview = URL.createObjectURL(file);
                }
            },

            handleSyllabusCsv(event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    const text = e.target.result;
                    const parsedRows = this.parseCsvText(text);

                    if (parsedRows.length > 0) {
                        this.rows = parsedRows;
                        this.csvUploadMsg = `✅ Successfully loaded ${parsedRows.length} classes from your CSV into the syllabus!`;
                    } else {
                        alert('Could not find class records in the CSV. Please ensure your CSV has "class_title" or "class_name" columns.');
                    }
                };
                reader.readAsText(file);
                // Reset input
                event.target.value = '';
            },

            parseCsvText(text) {
                // Remove UTF-8 BOM
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

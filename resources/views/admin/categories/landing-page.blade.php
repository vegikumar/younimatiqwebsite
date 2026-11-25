@extends('layouts.dashboard')

@section('title', 'Manage Landing Page - ' . $category->name)

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12" x-data="landingPageForm()">
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}" class="text-primary hover:underline">&larr; Back to Categories</a>
    </div>

    <div class="clay-card p-5 md:p-8 lg:p-6 mb-10 border-t-8 border-primary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Manage Landing Page: {{ $category->name }}</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Customize the content and layout for this category's public landing page.</p>
    </div>

    <form method="POST" action="{{ route('admin.categories.landing-page.update', $category) }}" class="space-y-8" enctype="multipart/form-data">
        @csrf @method('PUT')

        <!-- Hero Section -->
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-4">Hero Section</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Hero Title</label>
                    <input type="text" name="hero_title" value="{{ old('hero_title', $lpData['hero_title']) }}" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Leave blank for default: Build, Code, and Create in {{ $category->name }}">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Theme Color</label>
                    <select name="theme_color" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                        <option value="green" {{ ($lpData['theme_color'] ?? '') == 'green' ? 'selected' : '' }}>Green</option>
                        <option value="blue" {{ ($lpData['theme_color'] ?? '') == 'blue' ? 'selected' : '' }}>Blue</option>
                        <option value="purple" {{ ($lpData['theme_color'] ?? '') == 'purple' ? 'selected' : '' }}>Purple</option>
                        <option value="orange" {{ ($lpData['theme_color'] ?? '') == 'orange' ? 'selected' : '' }}>Orange</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-2">Hero Subtitle</label>
                    <textarea name="hero_subtitle" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" placeholder="Leave blank to use category description">{{ old('hero_subtitle', $lpData['hero_subtitle']) }}</textarea>
                </div>
            </div>
        </div>
        
        <!-- Section Headers -->
        <div class="clay-card p-5 md:p-8">
            <h2 class="font-headline-md font-bold mb-4">Section Headers & Text</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Courses -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Courses Section</h3>
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="courses_title" value="{{ old('courses_title', $lpData['courses_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Pick Your Path in {{ $category->name }}">
                </div>
                
                <!-- Videos -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Videos (Maker Moments)</h3>
                    <label class="block text-xs font-bold">Badge</label>
                    <input type="text" name="videos_badge" value="{{ old('videos_badge', $lpData['videos_badge']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Maker Moments">
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="videos_title" value="{{ old('videos_title', $lpData['videos_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="See Younimatiq Creators in Action.">
                    <label class="block text-xs font-bold">Subtitle</label>
                    <input type="text" name="videos_subtitle" value="{{ old('videos_subtitle', $lpData['videos_subtitle']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                </div>
                
                <!-- Features -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Features Section</h3>
                    <label class="block text-xs font-bold">Badge</label>
                    <input type="text" name="features_badge" value="{{ old('features_badge', $lpData['features_badge']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Why Choose Younimatiq">
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="features_title" value="{{ old('features_title', $lpData['features_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Built Different. On Purpose.">
                </div>
                
                <!-- Reviews -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Reviews Section</h3>
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="reviews_title" value="{{ old('reviews_title', $lpData['reviews_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Parents Love Younimatiq">
                    <label class="block text-xs font-bold">Subtitle</label>
                    <input type="text" name="reviews_subtitle" value="{{ old('reviews_subtitle', $lpData['reviews_subtitle']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Real stories from real families around the world.">
                </div>
                
                <!-- Curriculum -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Curriculum Section</h3>
                    <label class="block text-xs font-bold">Badge</label>
                    <input type="text" name="curriculum_badge" value="{{ old('curriculum_badge', $lpData['curriculum_badge']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Logic, Engineering & Modding">
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="curriculum_title" value="{{ old('curriculum_title', $lpData['curriculum_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="The Creative Path to Mastery">
                    <label class="block text-xs font-bold">Subtitle</label>
                    <textarea name="curriculum_subtitle" rows="2" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="We transform Minecraft from a game into a canvas for engineering...">{{ old('curriculum_subtitle', $lpData['curriculum_subtitle']) }}</textarea>
                </div>
                
                <!-- Bottom Banner -->
                <div class="space-y-2 p-4 border border-outline-variant rounded-xl">
                    <h3 class="font-bold text-primary">Bottom Banner Section</h3>
                    <label class="block text-xs font-bold">Title</label>
                    <input type="text" name="bottom_banner_title" value="{{ old('bottom_banner_title', $lpData['bottom_banner_title']) }}" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Ready to Turn Screen Time Into Skill Time?">
                    <label class="block text-xs font-bold">Subtitle</label>
                    <textarea name="bottom_banner_subtitle" rows="2" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Book a free consultation and get a personalized AI & STEM Lab plan...">{{ old('bottom_banner_subtitle', $lpData['bottom_banner_subtitle']) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="clay-card p-5 md:p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-headline-md font-bold">Features (Why Choose Younimatiq)</h2>
                <button type="button" @click="addFeature" class="btn-secondary text-sm px-3 py-1">+ Add Feature</button>
            </div>
            <div class="space-y-4">
                <template x-for="(feature, index) in features" :key="index">
                    <div class="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                        <div class="w-16">
                            <label class="block text-xs font-bold mb-1">Emoji</label>
                            <input type="text" x-model="feature.icon" :name="`features[${index}][icon]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                        </div>
                        <div class="flex-1 space-y-2">
                            <div>
                                <label class="block text-xs font-bold mb-1">Title</label>
                                <input type="text" x-model="feature.title" :name="`features[${index}][title]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Description</label>
                                <textarea x-model="feature.description" :name="`features[${index}][description]`" rows="2" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg"></textarea>
                            </div>
                        </div>
                        <button type="button" @click="features.splice(index, 1)" class="text-error hover:text-error absolute top-4 right-4">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </template>
                <div x-show="features.length === 0" class="text-on-surface-variant italic text-sm">No features added.</div>
            </div>
        </div>

        <!-- Videos -->
        <div class="clay-card p-5 md:p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-headline-md font-bold">Maker Moments (Videos)</h2>
                <button type="button" @click="addVideo" class="btn-secondary text-sm px-3 py-1">+ Add Video</button>
            </div>
            <div class="space-y-4">
                <template x-for="(video, index) in videos" :key="index">
                    <div class="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                        <div class="flex-1 space-y-2">
                            <div>
                                <label class="block text-xs font-bold mb-1">Upload Video File</label>
                                <input type="file" :name="`videos[${index}][file]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" accept="video/*,image/*">
                                <input type="hidden" x-model="video.url" :name="`videos[${index}][url]`">
                                <template x-if="video.url">
                                    <div class="mt-1 text-xs text-on-tertiary-container">Currently uploaded: <span x-text="video.url"></span></div>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Title/Caption</label>
                                <input type="text" x-model="video.title" :name="`videos[${index}][title]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                            </div>
                        </div>
                        <button type="button" @click="videos.splice(index, 1)" class="text-error hover:text-error absolute top-4 right-4">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </template>
                <div x-show="videos.length === 0" class="text-on-surface-variant italic text-sm">No videos added.</div>
            </div>
        </div>

        <!-- Reviews -->
        <div class="clay-card p-5 md:p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-headline-md font-bold">Parents Love Younimatiq (Reviews)</h2>
                <button type="button" @click="addReview" class="btn-secondary text-sm px-3 py-1">+ Add Review</button>
            </div>
            <div class="space-y-4">
                <template x-for="(review, index) in reviews" :key="index">
                    <div class="border border-outline-variant p-4 rounded-xl relative space-y-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Review Text</label>
                            <textarea x-model="review.text" :name="`reviews[${index}][text]`" rows="2" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg"></textarea>
                        </div>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold mb-1">Parent Name</label>
                                <input type="text" x-model="review.name" :name="`reviews[${index}][name]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Initial (Avatar)</label>
                                <input type="text" x-model="review.initial" :name="`reviews[${index}][initial]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" maxlength="1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Location</label>
                                <input type="text" x-model="review.location" :name="`reviews[${index}][location]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                            </div>
                        </div>
                        <button type="button" @click="reviews.splice(index, 1)" class="text-error hover:text-error absolute top-4 right-4">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </template>
                <div x-show="reviews.length === 0" class="text-on-surface-variant italic text-sm">No reviews added.</div>
            </div>
        </div>

        <!-- Curriculum -->
        <div class="clay-card p-5 md:p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-headline-md font-bold">Curriculum Path (Steps)</h2>
                <button type="button" @click="addCurriculum" class="btn-secondary text-sm px-3 py-1">+ Add Step</button>
            </div>
            <div class="space-y-4">
                <template x-for="(step, index) in curriculum" :key="index">
                    <div class="flex gap-4 items-start border border-outline-variant p-4 rounded-xl relative">
                        <div class="flex-1 space-y-2">
                            <div>
                                <label class="block text-xs font-bold mb-1">Step Title</label>
                                <input type="text" x-model="step.title" :name="`curriculum[${index}][title]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Description</label>
                                <textarea x-model="step.description" :name="`curriculum[${index}][description]`" rows="2" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg"></textarea>
                            </div>
                        </div>
                        <button type="button" @click="curriculum.splice(index, 1)" class="text-error hover:text-error absolute top-4 right-4">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </template>
                <div x-show="curriculum.length === 0" class="text-on-surface-variant italic text-sm">No curriculum steps added.</div>
            </div>
        </div>
        
        <!-- FAQs -->
        <div class="clay-card p-5 md:p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-headline-md font-bold">FAQs</h2>
                <button type="button" @click="addFaq" class="btn-secondary text-sm px-3 py-1">+ Add FAQ</button>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">FAQ Section Title</label>
                <input type="text" name="faq_title" value="{{ old('faq_title', $lpData['faq_title']) }}" class="w-full max-w-md p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="Frequently asked questions">
            </div>
            <div class="space-y-4">
                <template x-for="(faq, index) in faqs" :key="index">
                    <div class="border border-outline-variant p-4 rounded-xl relative space-y-3">
                        <button type="button" @click="faqs.splice(index, 1)" class="text-error hover:text-error absolute top-4 right-4">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                        <div>
                            <label class="block text-xs font-bold mb-1">Question</label>
                            <input type="text" x-model="faq.question" :name="`faqs[${index}][question]`" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Answer</label>
                            <textarea x-model="faq.answer" :name="`faqs[${index}][answer]`" rows="3" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg"></textarea>
                        </div>
                    </div>
                </template>
                <div x-show="faqs.length === 0" class="text-on-surface-variant italic text-sm">No FAQs added.</div>
            </div>
        </div>

        <div class="flex justify-end pb-12">
            <button type="submit" class="btn-primary px-8 py-3 text-lg">Save Landing Page</button>
        </div>
    </form>
</div>

<script>
function landingPageForm() {
    return {
        features: @json($lpData['features'] ?? []),
        videos: @json($lpData['videos'] ?? []),
        reviews: @json($lpData['reviews'] ?? []),
        curriculum: @json($lpData['curriculum'] ?? []),
        faqs: @json($lpData['faqs'] ?? []),
        
        addFeature() {
            this.features.push({ icon: '✨', title: '', description: '' });
        },
        addVideo() {
            this.videos.push({ url: '', title: '' });
        },
        addReview() {
            this.reviews.push({ text: '', name: '', initial: '', location: '', color: 'bg-secondary' });
        },
        addCurriculum() {
            this.curriculum.push({ title: '', description: '' });
        },
        addFaq() {
            this.faqs.push({ question: '', answer: '' });
        }
    }
}
</script>
@endsection

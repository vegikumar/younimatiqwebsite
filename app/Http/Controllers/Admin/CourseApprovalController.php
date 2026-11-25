<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseUnit;
use App\Models\Notification;
use App\Models\User;
use App\Services\CourseCsvImportService;
use App\Services\EmailService;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseApprovalController extends Controller
{
    private function mediaUrlRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if (blank($value)) {
                return;
            }

            $isAbsolute = filter_var($value, FILTER_VALIDATE_URL) !== false;
            $isLocalPath = str_starts_with($value, '/') && ! str_starts_with($value, '//');

            if (! $isAbsolute && ! $isLocalPath) {
                $fail('The :attribute must be a valid URL or a local path starting with /.');
            }
        };
    }

    private function syncAgeGroups(Course $course, Request $request): void
    {
        if (! $request->has('age_groups_present')) {
            return;
        }

        $ids = collect($request->input('age_groups', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $course->ageGroups()->sync($ids);

        $primary = $course->ageGroups()->orderBy('sort_order')->first();
        $grades = $course->ageGroups()
            ->pluck('slug')
            ->filter(fn ($slug) => is_numeric($slug))
            ->map(fn ($slug) => (int) $slug);

        $course->forceFill([
            'age_group' => $primary?->slug,
            'grade_min' => $grades->isNotEmpty() ? $grades->min() : null,
            'grade_max' => $grades->isNotEmpty() ? $grades->max() : null,
        ])->save();
    }

    public function index(Request $request)
    {
        $query = Course::with(['tutor', 'categories'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('tutor', fn ($t) => $t->where('name', 'like', '%'.$search.'%'));
            });
        }

        $courses = $query->get();

        return \App\Support\InertiaPage::react('Admin/Courses/Index', [
            'courses' => \App\Support\AdminInertiaProps::courseIndexRows($courses),
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
            ],
            'importErrors' => session('import_errors', []),
            'importWarnings' => session('import_warnings', []),
            'importOpenModal' => (bool) session('import_open_modal', false),
            'routes' => [
                'index' => route('admin.courses'),
                'create' => route('admin.courses.create'),
                'sampleCsv' => route('admin.courses.sample-csv'),
                'bulkPreview' => route('admin.courses.bulk-preview'),
                'categoriesJson' => route('admin.categories.json'),
                'categoriesIndex' => route('admin.categories.index'),
            ],
        ]);
    }

    public function downloadSampleCsv(Request $request, CourseCsvImportService $csvService)
    {
        if ($request->get('type') === 'syllabus') {
            return $csvService->downloadSampleSyllabusCsv();
        }

        return $csvService->downloadSampleBulkCsv();
    }

    public function bulkImport(Request $request, CourseCsvImportService $csvService)
    {
        $payload = null;

        if ($request->filled('import_token')) {
            $payload = Cache::pull('course_csv_import_'.$request->input('import_token'));
            if (! $payload || ! Storage::exists($payload['path'])) {
                return redirect()->route('admin.courses')
                    ->with('error', 'This import preview has expired. Please upload the CSV file again.');
            }
        }

        if ($payload) {
            $file = new UploadedFile(Storage::path($payload['path']), 'courses.csv', 'text/csv', null, true);
            $defaultStatus = $payload['default_status'];
            $updateExisting = $payload['update_existing'];
            $categoryIds = $payload['category_ids'];
        } else {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:20480',
                'default_status' => 'nullable|in:draft,published',
                'update_existing' => 'nullable|boolean',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
            ]);

            $file = $request->file('csv_file');
            $defaultStatus = $request->input('default_status', 'draft');
            $updateExisting = $request->boolean('update_existing', true);
            $categoryIds = $request->input('category_ids', []);
        }

        $result = $csvService->importBulkCsv($file, $defaultStatus, $updateExisting, $categoryIds);

        if ($payload) {
            Storage::delete($payload['path']);
        }

        if ($result['success']) {
            return redirect()->route('admin.courses')->with('success', $result['message'])
                ->with('import_warnings', $result['warnings'] ?? []);
        }

        return redirect()->route('admin.courses')->with('error', $result['message']);
    }

    /**
     * Validate an uploaded CSV and show a preview before committing anything.
     */
    public function previewBulkImport(Request $request, CourseCsvImportService $csvService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:20480',
            'default_status' => 'nullable|in:draft,published',
            'update_existing' => 'nullable|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $defaultStatus = $request->input('default_status', 'draft');
        $updateExisting = $request->boolean('update_existing', true);
        $categoryIds = $request->input('category_ids', []);

        $analysis = $csvService->analyze($request->file('csv_file'), $defaultStatus, $updateExisting, $categoryIds);

        if (! empty($analysis['errors'])) {
            return redirect()->route('admin.courses')
                ->with('import_errors', $analysis['errors'])
                ->with('import_open_modal', true)
                ->withInput();
        }

        $token = (string) Str::uuid();
        $path = $request->file('csv_file')->store('imports');

        Cache::put('course_csv_import_'.$token, [
            'path' => $path,
            'default_status' => $defaultStatus,
            'update_existing' => $updateExisting,
            'category_ids' => $categoryIds,
            'warnings' => $analysis['warnings'],
        ], now()->addHour());

        return \App\Support\InertiaPage::react('Admin/Courses/ImportPreview', [
            'analysis' => $analysis,
            'token' => $token,
            'options' => [
                'default_status' => $defaultStatus,
                'update_existing' => $updateExisting,
            ],
            'routes' => [
                'index' => route('admin.courses'),
                'bulkImport' => route('admin.courses.bulk-import'),
                'warningsCsv' => route('admin.courses.bulk-import.warnings', ['token' => $token]),
            ],
        ]);
    }

    /**
     * Download the non-blocking warnings from the last preview as a CSV report.
     */
    public function downloadImportWarnings(Request $request)
    {
        $payload = Cache::get('course_csv_import_'.$request->input('token'));
        $warnings = $payload['warnings'] ?? [];

        return response()->streamDownload(function () use ($warnings) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['#', 'Warning']);
            foreach ($warnings as $i => $warning) {
                fputcsv($handle, [$i + 1, $warning]);
            }
            fclose($handle);
        }, 'course_import_warnings.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importSyllabus(Request $request, Course $course, CourseCsvImportService $csvService)
    {
        $request->validate([
            'syllabus_csv' => 'required|file|mimes:csv,txt|max:10240',
            'replace_existing' => 'nullable|boolean',
        ]);

        $replaceExisting = $request->boolean('replace_existing', true);
        $result = $csvService->importSyllabusToCourse($course, $request->file('syllabus_csv'), $replaceExisting);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function create()
    {
        $categories = Category::all();
        $ageGroups = AgeGroup::active()->get();

        return \App\Support\InertiaPage::react('Admin/Courses/Create', [
            'categories' => \App\Support\AdminInertiaProps::categoryOptions(),
            'ageGroups' => \App\Support\AdminInertiaProps::ageGroupOptions(),
            'routes' => [
                'store' => route('admin.courses.store'),
                'index' => route('admin.courses'),
                'sampleCsvBulk' => route('admin.courses.sample-csv', ['type' => 'bulk']),
                'sampleCsvSyllabus' => route('admin.courses.sample-csv', ['type' => 'syllabus']),
                'ageGroupsIndex' => route('admin.age-groups'),
            ],
        ]);
    }

    public function store(Request $request, ImageUploadService $images)
    {
        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft' || $request->has('save_draft');

        $rules = [
            'tutor_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => $isDraft ? 'nullable|string' : 'required|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'price_usd' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'price_aed' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'price_inr' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'course_type' => $isDraft ? 'nullable|in:live,self-paced' : 'required|in:live,self-paced',
            'self_paced_enabled' => 'nullable|boolean',
            'format' => 'nullable|in:1to1,group,tutoring,club',
            'welcome_message' => 'nullable|string',
            'welcome_video_url' => ['nullable', $this->mediaUrlRule()],
            'welcome_video_file' => 'nullable|file|mimes:mp4,mov,avi|max:131072',
            'age_group' => 'nullable|string',
            'age_groups' => 'nullable|array',
            'age_groups.*' => 'exists:age_groups,id',
            'thumbnail_url' => ['nullable', $this->mediaUrlRule()],
            'thumbnail_file' => 'nullable|image|max:10240',
            'video_url' => ['nullable', $this->mediaUrlRule()],
            'video_file' => 'nullable|file|mimes:mp4,mov,avi|max:131072',
            '3d_asset_path' => 'nullable|string',
            '3d_asset_file' => 'nullable|file|max:131072',
            'slug' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
            'units' => 'nullable|array',
            'units.*.title' => 'nullable|string|max:255',
            'units.*.description' => 'nullable|string',
            'units.*.modules' => 'nullable|array',
            'units.*.modules.*.title' => 'nullable|string|max:255',
            'units.*.modules.*.content_type' => 'nullable|in:video,pdf,quiz,assignment,image,text',
            'units.*.modules.*.duration_minutes' => 'nullable|integer',
            'units.*.modules.*.description' => 'nullable|string',
        ];

        $data = $request->validate($rules);

        // Units are persisted separately (not a course column).
        unset($data['units']);

        $thumbnailUrl = $request->thumbnail_url;
        if ($request->hasFile('thumbnail_file')) {
            $path = $images->store($request->file('thumbnail_file'), 'courses/thumbnails', 'public');
            $thumbnailUrl = $path['url'];
        }

        $videoUrl = $request->video_url;
        if ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('courses/videos', 'local');
            $videoUrl = route('secure.asset', ['path' => $path], false);
        }

        $welcomeVideoUrl = $request->welcome_video_url;
        if ($request->hasFile('welcome_video_file')) {
            $path = $request->file('welcome_video_file')->store('courses/welcome_videos', 'local');
            $welcomeVideoUrl = route('secure.asset', ['path' => $path], false);
        }

        $assetPath = $request->input('3d_asset_path');
        if ($request->hasFile('3d_asset_file')) {
            $path = $request->file('3d_asset_file')->store('courses/3d_assets', 'local');
            $assetPath = route('secure.asset', ['path' => $path]);
        }

        $data['status'] = $isDraft ? 'draft' : 'published';
        $data['description'] = $data['description'] ?? 'Course draft in progress...';
        $data['format'] = $request->format ?? 'group';
        $data['course_type'] = $data['course_type'] ?? 'live';
        $data['self_paced_enabled'] = $request->boolean('self_paced_enabled');
        $data['importance'] = null;
        $data['price_usd'] = $data['price_usd'] ?? 18.00;
        $data['price_aed'] = $data['price_aed'] ?? 30.00;
        $data['price_inr'] = $data['price_inr'] ?? 600.00;
        $data['price'] = $data['price'] ?? $data['price_usd'];
        $data['thumbnail_url'] = $thumbnailUrl;
        $data['video_url'] = $videoUrl;
        $data['welcome_video_url'] = $welcomeVideoUrl;
        $data['3d_asset_path'] = $assetPath;
        $data['is_top_course'] = $request->has('is_top_course');

        $data['slug'] = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($data['title']);
        $data['published_at'] = $request->input('published_at') ?? now();

        $seoMetadata = array_filter([
            'title' => $request->seo_title,
            'description' => $request->seo_description,
        ]);
        $data['seo_metadata'] = empty($seoMetadata) ? null : $seoMetadata;

        $course = Course::create($data);
        if ($request->has('category_ids')) {
            $course->categories()->sync($request->category_ids);
        }

        $this->syncAgeGroups($course, $request);

        if ($request->has('modules') && is_array($request->modules)) {
            $order = 1;
            foreach ($request->modules as $moduleData) {
                if (! empty($moduleData['title'])) {
                    CourseModule::create([
                        'course_id' => $course->id,
                        'title' => $moduleData['title'],
                        'description' => $moduleData['description'] ?? null,
                        'learning_outcome' => $moduleData['learning_outcome'] ?? null,
                        'practical_video_url' => $moduleData['practical_video_url'] ?: null,
                        'content_type' => 'text',
                        'order' => $order++,
                    ]);
                }
            }
        }

        // Self-paced syllabus units (each unit holds ordered modules).
        if ($request->has('units') && is_array($request->units)) {
            $unitOrder = 1;
            foreach ($request->units as $unitData) {
                if (empty(trim($unitData['title'] ?? ''))) {
                    continue;
                }
                $unit = CourseUnit::create([
                    'course_id' => $course->id,
                    'title' => $unitData['title'],
                    'description' => $unitData['description'] ?? null,
                    'order' => $unitOrder++,
                ]);

                $moduleOrder = 1;
                foreach (($unitData['modules'] ?? []) as $moduleData) {
                    if (empty(trim($moduleData['title'] ?? ''))) {
                        continue;
                    }
                    CourseModule::create([
                        'course_id' => $course->id,
                        'unit_id' => $unit->id,
                        'title' => $moduleData['title'],
                        'description' => $moduleData['description'] ?? null,
                        'content_type' => $moduleData['content_type'] ?? 'video',
                        'duration_minutes' => $moduleData['duration_minutes'] ?: null,
                        'order' => $moduleOrder++,
                    ]);
                }
            }
        }

        if ($course->tutor_id) {
            $this->notifyAssignedTutor($course);
        }

        $message = $isDraft
            ? 'Course saved as draft! Your work is safely saved and you can resume anytime.'
            : 'Course created and published successfully.';

        return redirect()->route('admin.courses.edit', $course)->with('success', $message);
    }

    public function updateStatus(Request $request, Course $course, EmailService $emailService)
    {
        $request->validate(['status' => 'required|in:draft,pending,published,rejected']);

        $oldStatus = $course->status;
        $newStatus = $request->status;

        $course->update(['status' => $newStatus]);

        if ($course->tutor && $oldStatus !== $newStatus) {
            if ($newStatus === 'published') {
                $emailService->sendCustomEmail(
                    $course->tutor->email,
                    'Your Course is Published: '.$course->title,
                    'Congratulations <strong>'.htmlspecialchars($course->tutor->name).'</strong>,<br><br>'.
                    'Your course <strong>'.htmlspecialchars($course->title).'</strong> has been approved by the admin and is now published and live!<br><br>'.
                    'Thank you for your hard work.'
                );
            } elseif ($newStatus === 'rejected') {
                $emailService->sendCustomEmail(
                    $course->tutor->email,
                    'Course Revision Required: '.$course->title,
                    'Hi <strong>'.htmlspecialchars($course->tutor->name).'</strong>,<br><br>'.
                    'The admin has reviewed your course <strong>'.htmlspecialchars($course->title).'</strong> and has requested changes before it can be published.<br><br>'.
                    'Please log into your tutor dashboard to review your course and submit it again once updated.'
                );
            }
        }

        return back()->with('success', 'Course status updated.');
    }

    public function toggleActive(Course $course)
    {
        $course->update(['is_active' => ! $course->is_active]);
        $status = $course->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Course {$status} successfully.");
    }

    public function edit(Course $course)
    {
        $course->load(['categories', 'units.modules', 'ageGroups']);
        $categories = Category::all();
        $ageGroups = AgeGroup::active()->get();
        $selectedAgeGroups = old('age_groups', $course->ageGroups->pluck('id')->all());
        $emailAutomations = \App\Models\EmailAutomation::query()
            ->orderByDesc('is_enabled')
            ->orderBy('name')
            ->get(['id', 'name', 'is_enabled']);

        return \App\Support\InertiaPage::react('Admin/Courses/Edit', [
            'course' => \App\Support\AdminInertiaProps::courseForEdit($course, $selectedAgeGroups),
            'categories' => \App\Support\AdminInertiaProps::categoryOptions(),
            'ageGroups' => \App\Support\AdminInertiaProps::ageGroupOptions(),
            'emailAutomations' => $emailAutomations->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'is_enabled' => (bool) $a->is_enabled,
            ])->values()->all(),
            'defaultAutomationId' => old('email_automation_id', $emailAutomations->firstWhere('is_enabled', true)?->id ?? $emailAutomations->first()?->id),
            'routes' => [
                'index' => route('admin.courses'),
                'sampleCsvSyllabus' => route('admin.courses.sample-csv', ['type' => 'syllabus']),
                'ageGroupsIndex' => route('admin.age-groups'),
            ],
        ]);
    }

    public function update(Request $request, Course $course, ImageUploadService $images)
    {
        $request->validate([
            'tutor_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'price_usd' => 'required|numeric|min:0',
            'price_aed' => 'required|numeric|min:0',
            'price_inr' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'course_type' => 'required|in:live,self-paced',
            'self_paced_enabled' => 'nullable|boolean',
            'format' => 'nullable|in:1to1,group,tutoring,club',
            'welcome_message' => 'nullable|string',
            'welcome_video_url' => ['nullable', $this->mediaUrlRule()],
            'age_group' => 'nullable|string',
            'age_groups' => 'nullable|array',
            'age_groups.*' => 'exists:age_groups,id',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'video_url' => ['nullable', $this->mediaUrlRule()],
            'video_file' => 'nullable|file|mimes:mp4,mov,avi|max:131072',
            'thumbnail_url' => ['nullable', $this->mediaUrlRule()],
            'thumbnail_file' => 'nullable|image|max:10240',
            '3d_asset_path' => 'nullable|string',
            '3d_asset_file' => 'nullable|file|max:131072',
            'welcome_video_url' => ['nullable', $this->mediaUrlRule()],
            'welcome_video_file' => 'nullable|file|mimes:mp4,mov,avi|max:131072',
            'slug' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]);

        $learningOutcomes = array_filter(array_map('trim', explode("\n", $request->input('learning_outcomes', ''))));

        $seoMetadata = array_filter([
            'title' => $request->seo_title,
            'description' => $request->seo_description,
        ]);

        $thumbnailUrl = $request->thumbnail_url ?? $course->thumbnail_url;
        if ($request->has('remove_thumbnail')) {
            $thumbnailUrl = null;
        } elseif ($request->hasFile('thumbnail_file')) {
            $path = $images->store($request->file('thumbnail_file'), 'courses/thumbnails', 'public');
            $thumbnailUrl = $path['url'];
        }

        $videoUrl = $request->video_url ?? $course->video_url;
        if ($request->has('remove_video')) {
            $videoUrl = null;
        } elseif ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('courses/videos', 'local');
            $videoUrl = route('secure.asset', ['path' => $path], false);
        }

        $assetPath = $request->input('3d_asset_path', $course->{'3d_asset_path'});
        if ($request->has('remove_3d_asset')) {
            $assetPath = null;
        } elseif ($request->hasFile('3d_asset_file')) {
            $path = $request->file('3d_asset_file')->store('courses/3d_assets', 'local');
            $assetPath = route('secure.asset', ['path' => $path], false);
        }

        $welcomeVideoUrl = $request->welcome_video_url ?? $course->welcome_video_url;
        if ($request->has('remove_welcome_video')) {
            $welcomeVideoUrl = null;
        } elseif ($request->hasFile('welcome_video_file')) {
            $path = $request->file('welcome_video_file')->store('courses/welcome_videos', 'local');
            $welcomeVideoUrl = route('secure.asset', ['path' => $path], false);
        }

        $newStatus = $course->status ?? 'published';
        if ($request->filled('status') && in_array($request->status, ['draft', 'published', 'pending', 'rejected'])) {
            $newStatus = $request->status;
        } elseif ($request->input('action') === 'draft' || $request->has('save_draft')) {
            $newStatus = 'draft';
        }

        $course->update([
            'tutor_id' => $request->tutor_id,
            'title' => $request->title,
            'description' => $request->description,
            'price_usd' => $request->price_usd,
            'price_aed' => $request->price_aed,
            'price_inr' => $request->price_inr,
            'price' => $request->price ?? 0,
            'status' => $newStatus,
            'course_type' => $request->course_type,
            'self_paced_enabled' => $request->boolean('self_paced_enabled'),
            'format' => $request->format ?? 'group',
            'welcome_message' => $request->welcome_message,
            'welcome_video_url' => $welcomeVideoUrl,
            'age_group' => $request->input('age_group', $course->age_group),
            'video_url' => $videoUrl,
            'thumbnail_url' => $thumbnailUrl,
            '3d_asset_path' => $assetPath,
            'importance' => null,
            'is_camp' => $request->has('is_camp'),
            'is_top_course' => $request->has('is_top_course'),
            'learning_outcomes' => empty($learningOutcomes) ? null : array_values($learningOutcomes),
            'seo_metadata' => empty($seoMetadata) ? null : $seoMetadata,
            'slug' => $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($request->title),
            'published_at' => $request->input('published_at') ?? ($course->published_at ?? now()),
        ]);

        if ($request->has('category_ids')) {
            $course->categories()->sync($request->category_ids);
        }

        $this->syncAgeGroups($course, $request);

        if ($request->has('modules') && is_array($request->modules)) {
            $existingModuleIds = [];
            $order = 1;
            foreach ($request->modules as $index => $moduleData) {
                if (! empty($moduleData['title'])) {
                    // We assume it's a new or updated module. If it has no specific ID from the form, we can just create/update by order for simplicity
                    // However, we should be careful about deleting. The UI doesn't send IDs in the array format `modules[$index]`.
                    // We will fetch existing by order or create new.
                    $module = CourseModule::firstOrNew([
                        'course_id' => $course->id,
                        'unit_id' => null,
                        'order' => $order,
                    ]);
                    $module->title = $moduleData['title'];
                    $module->description = $moduleData['description'] ?? null;
                    $module->learning_outcome = $moduleData['learning_outcome'] ?? null;
                    $module->practical_video_url = $moduleData['practical_video_url'] ?: null;
                    $module->content_type = $module->content_type ?? 'text';
                    $module->save();

                    $existingModuleIds[] = $module->id;
                    $order++;
                }
            }
            // Optional: delete standalone class topics that were removed
            // (never touch unit modules belonging to self-paced units).
            CourseModule::where('course_id', $course->id)
                ->whereNull('unit_id')
                ->whereNotIn('id', $existingModuleIds)
                ->delete();
        }

        $course->save();

        return redirect()->route('admin.courses')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('admin.courses')->with('success', 'Course deleted.');
    }

    public function storeUnit(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course->units()->create([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $course->units()->count() + 1,
        ]);

        return back()->with('success', 'Unit added successfully!');
    }

    public function storeModule(Request $request, Course $course, ImageUploadService $images)
    {
        $request->validate([
            'course_unit_id' => 'nullable',
            'unit_id' => 'nullable|exists:course_units,id',
            'title' => 'required|string|max:255',
            'module_type' => 'nullable|in:video,pdf,quiz,assignment,image,text',
            'content_type' => 'nullable|in:video,pdf,quiz,assignment,image,text',
            'content_file' => 'nullable|file|max:131072',
            'duration_minutes' => 'nullable|integer',
        ]);

        $contentUrl = null;
        if ($request->hasFile('content_file')) {
            $path = $images->storePath($request->file('content_file'), 'courses/modules', 'public');
            $contentUrl = route('secure.asset', ['path' => $path]);
        }

        $order = CourseModule::where('course_id', $course->id)
            ->where('unit_id', $request->course_unit_id ?? $request->unit_id)
            ->count() + 1;

        CourseModule::create([
            'course_id' => $course->id,
            'unit_id' => $request->course_unit_id ?? $request->unit_id,
            'title' => $request->title,
            'content_type' => $request->module_type ?? $request->content_type ?? 'video',
            'content_url' => $contentUrl,
            'content_text' => $request->content_text,
            'duration_minutes' => $request->duration_minutes,
            'order' => $order,
        ]);

        return back()->with('success', 'Module added successfully!');
    }

    public function updateUnit(Request $request, CourseUnit $unit)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $unit->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Unit updated successfully!');
    }

    public function destroyUnit(CourseUnit $unit)
    {
        $unit->delete();

        return back()->with('success', 'Unit deleted successfully!');
    }

    public function updateModule(Request $request, CourseModule $module, ImageUploadService $images)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'course_unit_id' => 'nullable',
            'unit_id' => 'nullable|exists:course_units,id',
            'module_type' => 'nullable|in:video,pdf,quiz,assignment,image,text',
            'content_type' => 'nullable|in:video,pdf,quiz,assignment,image,text',
            'content_file' => 'nullable|file|max:131072',
            'duration_minutes' => 'nullable|integer',
        ]);

        $contentUrl = $module->content_url;
        if ($request->hasFile('content_file')) {
            $path = $images->storePath($request->file('content_file'), 'courses/modules', 'public');
            $contentUrl = route('secure.asset', ['path' => $path]);
        }

        $module->update([
            'title' => $request->title,
            'unit_id' => $request->course_unit_id,
            'content_type' => $request->module_type ?? $request->content_type ?? 'video',
            'content_url' => $contentUrl,
            'duration_minutes' => $request->duration_minutes,
        ]);

        return back()->with('success', 'Lesson module updated successfully!');
    }

    public function destroyModule(CourseModule $module)
    {
        $module->delete();

        return back()->with('success', 'Lesson module deleted successfully!');
    }

    private function notifyAssignedTutor(Course $course): void
    {
        if (! $course->tutor_id) {
            return;
        }
        $tutor = User::find($course->tutor_id);
        if (! $tutor) {
            return;
        }

        try {
            Notification::create([
                'user_id' => $tutor->id,
                'type' => 'assignment',
                'title' => 'Assigned as Instructor: '.$course->title,
                'body' => 'You have been assigned to teach '.$course->title.' on Younimatiq.',
                'action_url' => route('tutor.manage-courses'),
            ]);

            $subject = 'New Teaching Assignment: '.$course->title;
            $body = '<h3>New Teaching Assignment</h3>'
                .'<p>Hi <strong>'.e($tutor->name).'</strong>,</p>'
                .'<p>You have been assigned as the instructor for <strong>'.e($course->title).'</strong> on Younimatiq.</p>'
                .'<p><strong>Classroom & Dashboard Link:</strong> <a href="'.url('/tutor/dashboard').'">'.url('/tutor/dashboard').'</a></p>'
                .'<p>Thank you for inspiring young minds!</p>';

            Mail::to($tutor->email)->queue(new TemplateMail($subject, $body));
        } catch (\Throwable $e) {
            Log::error('Failed to notify assigned tutor: '.$e->getMessage());
        }
    }
}

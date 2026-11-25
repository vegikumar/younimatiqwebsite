<?php

namespace App\Services;

use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseCsvImportService
{
    /**
     * Column definitions for the unified template.
     * [key, human label] — order here is the order written to the CSV header.
     */
    public const COLUMNS = [
        'course_title',
        'course_slug',
        'category',
        'grade_band',
        'format',
        'course_type',
        'number_of_classes',
        'per_class_price',
        'grade_min',
        'grade_max',
        'price_usd',
        'price_aed',
        'price_inr',
        'status',
        'course_description',
        'importance',
        'learning_outcomes',
        'highlights',
        'thumbnail_url',
        'video_url',
        'welcome_message',
        'welcome_video_url',
        'is_camp',
        'is_top_course',
        'seo_title',
        'seo_description',
        'tutor_email',
        'class_number',
        'class_title',
        'class_description',
        'class_learning_outcome',
        'practical_video_url',
    ];

    /**
     * Download a single unified Course CSV template with all course fields
     * and prefilled 24 sample classes including practical_video_url.
     */
    public function downloadSampleBulkCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="younimatiq_courses_template_24_classes.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it with proper encoding.
            fputs($handle, "\xEF\xBB\xBF");

            $categoryNames = Category::where('is_active', true)->orderBy('name')->pluck('name')->implode(' | ');
            $ageGroupHint  = AgeGroup::active()->orderBy('sort_order')->get()
                ->map(fn ($g) => $g->slug . ' (' . $g->name . ')')
                ->implode(' | ');

            fputcsv($handle, self::COLUMNS);

            // Hint rows (safe to leave in — rows whose course_title starts with "##" are skipped).
            fputcsv($handle, $this->hintRow(
                '## VALID CATEGORIES (copy an exact name into the category column) ##',
                'category',
                $categoryNames
            ));
            fputcsv($handle, $this->hintRow(
                '## VALID GRADE BANDS (use one of these in the grade_band column) ##',
                'grade_band',
                $ageGroupHint
            ));

            $course = [
                'course_title'      => 'AI & Future Innovators: 24-Week Mastery Program',
                'course_slug'       => 'ai-future-innovators-24-week',
                'category'          => 'AI',
                'grade_band'        => '6-8',
                'format'            => 'group',
                'course_type'       => 'live',
                'number_of_classes' => '24',
                'per_class_price'   => '18.00',
                'grade_min'         => '6',
                'grade_max'         => '8',
                'price_usd'         => '18.00',
                'price_aed'         => '30.00',
                'price_inr'         => '600.00',
                'status'            => 'draft',
                'course_description'=> 'A complete 24-class curriculum taking young learners from the foundations of Artificial Intelligence to building custom AI models, prompt engineering, vision recognition, and ethical AI applications.',
                'importance'        => 'AI is reshaping every career. This program gives kids practical fluency with models, prompts, and ethics so they can create — not just consume — intelligent tools.',
                'learning_outcomes' => 'Understand how AI learns from data | Write effective prompts | Train a custom image classifier | Evaluate AI bias responsibly',
                'highlights'        => '24 live classes | Hands-on capstone project | Certificate on completion | Small group coaching',
                'thumbnail_url'     => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=800&q=80',
                'video_url'         => '',
                'welcome_message'   => 'Welcome to the AI & Future Innovators cohort! We are excited to embark on this 24-class learning journey with you.',
                'welcome_video_url' => '',
                'is_camp'           => 'no',
                'is_top_course'     => 'no',
                'seo_title'         => 'AI & Future Innovators Course for Kids | Younimatiq',
                'seo_description'   => 'A 24-week project-based AI course for grades 6-8 with prompt engineering, computer vision, and a capstone showcase.',
                'tutor_email'       => '',
            ];

            // [class_number, class_title, class_description, class_learning_outcome, practical_video_url]
            $sampleClasses = [
                ['1',  'Class 1: Demystifying Artificial Intelligence',       'What AI is, history of computing, and how machines learn from patterns.',               'Distinguish between rule-based programs and AI learning algorithms.',              ''],
                ['2',  'Class 2: Inside the Machine Learning Brain',          'Explore neural networks, inputs, weights, and predictions visually.',                   'Understand how neural layers process signals into decisions.',                     ''],
                ['3',  'Class 3: Mastering Large Language Models (LLMs)',     'How models like GPT understand and predict language tokens.',                           'Formulate mental models of LLM tokens and temperature.',                          ''],
                ['4',  'Class 4: Prompt Engineering Fundamentals',            'The anatomy of a perfect prompt: persona, context, task, and constraints.',             'Write structured prompts to solve math, writing, and coding challenges.',          ''],
                ['5',  'Class 5: Advanced Few-Shot & Chain-of-Thought',       'Guiding models through step-by-step reasoning and examples.',                          'Construct multi-step prompts for complex problem-solving.',                       ''],
                ['6',  'Class 6: AI Storytelling & Digital Narrative',        'Using AI as a creative co-author to craft choose-your-own-adventure stories.',         'Design interactive branching narratives using AI tools.',                         ''],
                ['7',  'Class 7: Computer Vision: How Machines "See"',        'Image recognition, convolutional concepts, and pixel data representation.',            'Explain how computers identify shapes, edges, and objects in pictures.',          ''],
                ['8',  'Class 8: Training a Custom Teachable Machine',        'Collect training photos to train a custom image classifier model.',                    'Train and evaluate a real image classification model.',                           ''],
                ['9',  'Class 9: Sound & Audio Recognition with AI',          'Spectrograms and audio pitch training for voice-controlled interactions.',              'Build a sound-responsive AI trigger.',                                            ''],
                ['10', 'Class 10: Generative AI for Digital Art & Design',    'Diffusion models, style transfers, and text-to-image prompts.',                        'Generate high-quality concept art using precise descriptive prompts.',            ''],
                ['11', 'Class 11: AI in Game Development',                    'Designing intelligent NPC behaviors, pathfinding, and dynamic game dialogue.',         'Script smart non-player character interactions.',                                 ''],
                ['12', 'Class 12: NLP & Sentiment Analysis',                  'How AI detects mood, emotion, and tone in written text.',                              'Classify customer reviews and messages by emotional sentiment.',                  ''],
                ['13', 'Class 13: Building Your First AI Chatbot',            'Creating a specialized knowledge assistant with custom system prompts.',               'Deploy a domain-specific interactive chatbot.',                                   ''],
                ['14', 'Class 14: Data Science Basics: Garbage In, Garbage Out', 'Data cleaning, labeling, and training set diversity.',                             'Demonstrate how low-quality training data degrades AI accuracy.',                 ''],
                ['15', 'Class 15: AI Ethics, Bias & Fairness',                'Algorithmic bias, safety guardrails, and responsible technology design.',              'Identify potential algorithmic bias in real-world use cases.',                    ''],
                ['16', 'Class 16: Deepfakes, Hallucinations & Media Literacy','Critical thinking in the AI era: verifying sources and spotting misinformation.',      'Evaluate media authenticity and verify AI claims.',                               ''],
                ['17', 'Class 17: Speech Synthesis & Voice Cloning',          'Text-to-speech technologies, phonemes, and voice modulation.',                        'Produce realistic narration clips using generative voice synthesis.',             ''],
                ['18', 'Class 18: AI-Assisted Coding with Copilots',          'Accelerating Python programming using AI pair programmer assistants.',                 'Debug and expand Python programs using code generation prompts.',                 ''],
                ['19', 'Class 19: Robotics & Sensor-Driven AI',               'How autonomous vehicles and drones interpret sensory inputs in real-time.',            'Simulate obstacle-avoidance logic for an autonomous vehicle.',                    ''],
                ['20', 'Class 20: Predictive Modeling & Weather Forecasting', 'Time-series data, regression, and predicting future trends.',                         'Analyze trend lines to make probabilistic predictions.',                          ''],
                ['21', 'Class 21: Autonomous Agents & AutoGPT Concepts',      'Autonomous goal-directed agents that plan and execute multi-step tasks.',              'Explain how autonomous agent loops plan, reflect, and execute.',                  ''],
                ['22', 'Class 22: Capstone Project Planning & Architecture',  'Brainstorming a personal AI solution for a real-world community problem.',             'Write a functional project specification and data pipeline.',                     ''],
                ['23', 'Class 23: Capstone Build & Prototype Testing',        'Hands-on construction and user testing of the capstone AI project.',                   'Implement functional prototype and conduct peer usability testing.',              ''],
                ['24', 'Class 24: Capstone Showcase, Demo Day & Graduation',  'Presenting projects, reflecting on 24 weeks of growth, and certification.',           'Deliver a compelling 3-minute pitch demonstrating project impact.',               ''],
            ];

            foreach ($sampleClasses as $class) {
                $row = $course;
                $row['class_number']            = $class[0];
                $row['class_title']             = $class[1];
                $row['class_description']       = $class[2];
                $row['class_learning_outcome']  = $class[3];
                $row['practical_video_url']     = $class[4];

                $line = [];
                foreach (self::COLUMNS as $col) {
                    $line[] = $row[$col] ?? '';
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Alias kept for backward-compat — redirects to the unified template.
     * @deprecated Use downloadSampleBulkCsv() instead.
     */
    public function downloadSampleSyllabusCsv(): StreamedResponse
    {
        return $this->downloadSampleBulkCsv();
    }

    /**
     * Analyze a CSV without writing anything. Returns a preview:
     * summary counts, per-course rows, blocking errors and non-blocking warnings.
     *
     * @return array{errors: array<int,string>, warnings: array<int,string>, summary: array{new:int,updated:int,classes:int}, courses: array<int,array>}
     */
    public function analyze(UploadedFile $file, string $defaultStatus = 'draft', bool $updateExisting = true, array $categoryIds = []): array
    {
        $rows = $this->parseCsvFile($file);

        if (empty($rows)) {
            return [
                'errors'   => ['The uploaded CSV file is empty or could not be parsed.'],
                'warnings' => [],
                'summary'  => ['new' => 0, 'updated' => 0, 'classes' => 0],
                'courses'  => [],
            ];
        }

        $errors = [];
        $warnings = [];
        $plans = $this->buildPlans($rows, $defaultStatus, $updateExisting, $categoryIds, $errors, $warnings);

        $new = count(array_filter($plans, fn ($p) => $p['action'] === 'create'));
        $updated = count($plans) - $new;
        $classes = array_sum(array_map(fn ($p) => $p['class_count'], $plans));

        $courses = array_map(fn ($p) => [
            'title'             => $p['title'],
            'slug'              => $p['slug'],
            'action'            => $p['action'],
            'classes'           => $p['class_count'],
            'category'          => $p['category'],
            'age_group'         => $p['age_group'],
            'grade_range'       => $p['grade_range'],
            'price_usd'         => $p['price_usd'],
            'status'            => $p['status'],
            'tutor'             => $p['tutor_name'],
            'outcomes'          => count($p['fields']['learning_outcomes'] ?? []),
            'highlights'        => count($p['fields']['highlights'] ?? []),
            'is_camp'           => (bool) ($p['fields']['is_camp'] ?? false),
            'is_top_course'     => (bool) ($p['fields']['is_top_course'] ?? false),
        ], $plans);

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'summary'  => ['new' => $new, 'updated' => $updated, 'classes' => $classes],
            'courses'  => $courses,
        ];
    }

    /**
     * Import multiple courses and/or classes from a CSV file.
     */
    public function importBulkCsv(UploadedFile $file, string $defaultStatus = 'draft', bool $updateExisting = true, array $categoryIds = []): array
    {
        $rows = $this->parseCsvFile($file);
        if (empty($rows)) {
            return [
                'success'       => false,
                'message'       => 'The uploaded CSV file is empty or could not be parsed.',
                'courses_count' => 0,
                'modules_count' => 0,
            ];
        }

        $errors = [];
        $warnings = [];
        $plans = $this->buildPlans($rows, $defaultStatus, $updateExisting, $categoryIds, $errors, $warnings);

        if (!empty($errors)) {
            return [
                'success'       => false,
                'message'       => 'Import blocked: ' . implode(' ', $errors),
                'courses_count' => 0,
                'modules_count' => 0,
                'errors'        => $errors,
            ];
        }

        if (empty($plans)) {
            return [
                'success'       => false,
                'message'       => 'No valid courses were found in the CSV.',
                'courses_count' => 0,
                'modules_count' => 0,
            ];
        }

        $created = 0;
        $updated = 0;
        $modulesTotal = 0;

        DB::beginTransaction();
        try {
            foreach ($plans as $plan) {
                $fields = $plan['fields'];
                $existing = $plan['existing'];

                if ($existing) {
                    // Only push values that were actually provided, so blank CSV
                    // cells never wipe existing content on an update.
                    $update = array_filter($fields, fn ($v) => $v !== null);
                    unset($update['is_active']);
                    $update['published_at'] = $existing->published_at ?: now();
                    $update['slug'] = $existing->slug; // slug is the identity; never change on update
                    $existing->update($update);
                    $course = $existing;
                    $updated++;
                } else {
                    $create = array_merge($this->createDefaults($plan, $defaultStatus), array_filter($fields, fn ($v) => $v !== null));
                    $create['slug'] = $plan['slug'];
                    $create['published_at'] = now();
                    $course = Course::create($create);
                    $created++;
                }

                // Categories: chips from the upload modal always win; otherwise the CSV column.
                if (!empty($categoryIds)) {
                    $course->categories()->sync($categoryIds);
                } elseif ($plan['category']) {
                    $matched = Category::whereRaw('LOWER(name) = ?', [strtolower($plan['category'])])
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->first();
                    if ($matched) {
                        $course->categories()->sync([$matched->id]);
                    }
                }

                // Replace syllabus classes in order — but only when the CSV actually has classes,
                // so a course-only update never wipes an existing syllabus.
                if (!empty($plan['classes'])) {
                    CourseModule::where('course_id', $course->id)->delete();

                    $order = 1;
                    foreach ($plan['classes'] as $class) {
                        CourseModule::create([
                            'course_id'           => $course->id,
                            'title'               => $class['title'],
                            'description'         => $class['description'],
                            'learning_outcome'    => $class['learning_outcome'],
                            'practical_video_url' => $class['practical_video_url'],
                            'content_type'        => 'text',
                            'order'               => $class['order'] ?? $order,
                        ]);
                        $order = max($order + 1, ($class['order'] ?? 0) + 1);
                        $modulesTotal++;
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'CSV Import failed: ' . $e->getMessage(),
                'courses_count' => 0,
                'modules_count' => 0,
            ];
        }

        $message = "Successfully imported {$created} new course(s), updated {$updated} course(s), and created {$modulesTotal} class syllabus topic(s).";
        if (!empty($warnings)) {
            $message .= ' ' . count($warnings) . ' warning(s) — see import report.';
        }

        return [
            'success'       => true,
            'message'       => $message,
            'courses_count' => $created + $updated,
            'modules_count' => $modulesTotal,
            'warnings'      => $warnings,
        ];
    }

    /**
     * Import syllabus classes directly into a specific course.
     */
    public function importSyllabusToCourse(Course $course, UploadedFile $file, bool $replaceExisting = true): array
    {
        $rows = $this->parseCsvFile($file);
        if (empty($rows)) {
            return [
                'success'       => false,
                'message'       => 'The uploaded syllabus CSV is empty or invalid.',
                'modules_count' => 0,
            ];
        }

        DB::beginTransaction();
        try {
            if ($replaceExisting) {
                CourseModule::where('course_id', $course->id)->delete();
                $order = 1;
            } else {
                $order = (int) CourseModule::where('course_id', $course->id)->max('order') + 1;
            }

            $count = 0;
            foreach ($rows as $row) {
                $classTitle = $this->extractField($row, ['class_title', 'class_name', 'module_title', 'topic', 'title', 'lesson_title']);
                if (empty($classTitle)) {
                    continue;
                }

                $classDesc = $this->extractField($row, ['class_description', 'module_description', 'description', 'details']);
                $outcome = $this->extractField($row, ['class_learning_outcome', 'learning_outcome', 'outcome', 'learning_outcomes']);
                $practicalVideoUrl = $this->extractField($row, ['practical_video_url', 'practical_video', 'demo_video_url', 'demo_video']) ?: null;
                $classNum = (int) $this->extractField($row, ['class_number', 'order', 'module_number', '#', 'class_no']);
                $finalOrder = $classNum > 0 ? $classNum : $order++;

                CourseModule::create([
                    'course_id'           => $course->id,
                    'title'               => $classTitle,
                    'description'         => $classDesc,
                    'learning_outcome'    => $outcome,
                    'practical_video_url' => $practicalVideoUrl,
                    'content_type'        => 'text',
                    'order'               => $finalOrder,
                ]);

                $count++;
            }

            DB::commit();

            return [
                'success'       => true,
                'message'       => "Successfully imported {$count} classes into {$course->title}!",
                'modules_count' => $count,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'Failed to import syllabus: ' . $e->getMessage(),
                'modules_count' => 0,
            ];
        }
    }

    /**
     * Defaults applied to a brand-new course when a CSV cell is blank.
     */
    protected function createDefaults(array $plan, string $defaultStatus): array
    {
        return [
            'description'       => 'Comprehensive course curriculum.',
            'format'            => 'group',
            'course_type'       => 'live',
            'price_usd'         => 18.00,
            'price_aed'         => 30.00,
            'price_inr'         => 600.00,
            'price'             => $plan['price_usd'] ?? 18.00,
            'number_of_classes' => max(1, $plan['class_count'] ?: 8),
            'is_active'         => true,
            'is_camp'           => false,
            'is_top_course'     => false,
            'status'            => $defaultStatus,
        ];
    }

    /**
     * Transform raw parsed rows into normalized course "plans" used by both
     * analyze() (preview) and importBulkCsv() (commit). Collects errors/warnings.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array>
     */
    protected function buildPlans(array $rows, string $defaultStatus, bool $updateExisting, array $categoryIds, array &$errors, array &$warnings): array
    {
        $grouped = [];
        $unassociated = [];

        foreach ($rows as $row) {
            $line = (int) ($row['__line'] ?? 0);
            $courseTitle = $this->extractField($row, ['course_title', 'title', 'course', 'course_name', 'name']);

            // Skip template hint rows.
            if (str_starts_with(trim($courseTitle), '##')) {
                continue;
            }

            $classTitle = $this->extractField($row, ['class_title', 'class_name', 'module_title', 'topic', 'lesson_title']);

            if ($courseTitle !== '') {
                $grouped[$courseTitle]['line'] ??= $line;
                $grouped[$courseTitle]['rows'][] = $row;
            } elseif ($classTitle !== '') {
                $last = array_key_last($grouped);
                if ($last !== null) {
                    $grouped[$last]['rows'][] = $row;
                } else {
                    $unassociated[] = $line;
                }
            }
        }

        if (!empty($unassociated)) {
            $errors[] = 'Row(s) ' . implode(', ', $unassociated) . ' have a class title but no course_title above them.';
        }
        if (empty($grouped)) {
            $errors[] = 'No valid course titles found in the CSV. Please ensure the CSV has a "course_title" column.';
            return [];
        }

        $ageGroups = AgeGroup::active()->get()->keyBy('slug');
        $ageLookup = $this->buildAgeGroupLookup($ageGroups);

        $plans = [];
        $seenSlugs = [];

        foreach ($grouped as $title => $group) {
            $courseRows = $group['rows'];
            $firstRow = $courseRows[0];
            $startLine = $group['line'];
            $where = "Course '{$title}' (row {$startLine})";

            $slug = $this->extractField($firstRow, ['course_slug', 'slug']) ?: Str::slug($title);
            if ($slug === '') {
                $errors[] = "{$where}: could not generate a slug.";
                continue;
            }
            if (isset($seenSlugs[$slug])) {
                $errors[] = "Course '{$title}' (row {$startLine}): slug '{$slug}' duplicates course '{$seenSlugs[$slug]}' earlier in this file. Course titles must be unique.";
                continue;
            }
            $seenSlugs[$slug] = $title;

            // ── Classes ──────────────────────────────────────────────
            $classes = [];
            $autoOrder = 1;
            $classLines = [];
            foreach ($courseRows as $row) {
                $classTitle = $this->extractField($row, ['class_title', 'class_name', 'module_title', 'topic', 'lesson_title']);
                if ($classTitle === '') {
                    continue;
                }
                $classLines[] = (int) ($row['__line'] ?? 0);
                $num = (int) $this->extractField($row, ['class_number', 'order', 'module_number', '#', 'class_no']);
                $finalOrder = $num > 0 ? $num : $autoOrder;

                $classes[] = [
                    'title'               => $classTitle,
                    'description'         => $this->extractField($row, ['class_description', 'module_description', 'description', 'class_details']) ?: null,
                    'learning_outcome'    => $this->extractField($row, ['class_learning_outcome', 'learning_outcome', 'outcome', 'learning_outcomes']) ?: null,
                    'practical_video_url' => $this->extractField($row, ['practical_video_url', 'practical_video', 'demo_video_url', 'demo_video']) ?: null,
                    'order'               => $finalOrder,
                ];
                $autoOrder = max($autoOrder + 1, $finalOrder + 1);
            }

            // ── Grade band / age group (strict) ──────────────────────
            $gradeBandRaw = $this->extractField($firstRow, ['grade_band', 'age_group', 'grades', 'grade', 'age_range']);
            $ageGroupSlug = null;
            if ($gradeBandRaw !== '') {
                $ageGroupSlug = $this->resolveAgeGroup($gradeBandRaw, $ageLookup);
                if ($ageGroupSlug === null) {
                    $errors[] = "{$where}: grade_band '{$gradeBandRaw}' matches no age group. Valid values: " . $ageGroups->keys()->implode(', ') . '.';
                }
            }

            // ── Grade min / max (explicit, else derived from grade_band) ──
            $gradeMin = $this->extractIntOrNull($firstRow, ['grade_min', 'min_grade']);
            $gradeMax = $this->extractIntOrNull($firstRow, ['grade_max', 'max_grade']);
            if (($gradeMin === null || $gradeMax === null) && $gradeBandRaw !== ''
                && preg_match('/(\d+)\s*[-–—to]+\s*(\d+)/i', $gradeBandRaw, $m)) {
                $gradeMin ??= (int) $m[1];
                $gradeMax ??= (int) $m[2];
            }

            // ── Enumerations ─────────────────────────────────────────
            $format = strtolower($this->extractField($firstRow, ['format', 'listing_format']) ?: 'group');
            if (!in_array($format, ['1to1', 'group', 'tutoring', 'club'], true)) {
                $warnings[] = "{$where}: format '{$format}' is not recognised — defaulting to 'group'.";
                $format = 'group';
            }

            $courseType = strtolower($this->extractField($firstRow, ['course_type', 'type']) ?: 'live');
            if (!in_array($courseType, ['live', 'self-paced'], true)) {
                $warnings[] = "{$where}: course_type '{$courseType}' is not recognised — defaulting to 'live'.";
                $courseType = 'live';
            }

            $priceUsd = $this->extractFloat($firstRow, ['price_usd', 'usd'], 18.00);
            $priceAed = $this->extractFloat($firstRow, ['price_aed', 'aed'], 30.00);
            $priceInr = $this->extractFloat($firstRow, ['price_inr', 'inr'], 600.00);
            $perClassPrice = $this->extractFloatOrNull($firstRow, ['per_class_price', 'price_per_class']);

            $rowStatus = strtolower($this->extractField($firstRow, ['status']) ?: '');
            if ($rowStatus !== '' && !in_array($rowStatus, ['draft', 'published', 'pending', 'rejected'], true)) {
                $warnings[] = "{$where}: status '{$rowStatus}' is invalid — using the default status instead.";
                $rowStatus = '';
            }
            $status = $rowStatus !== '' ? $rowStatus : $defaultStatus;

            // ── Lists ────────────────────────────────────────────────
            $learningOutcomes = $this->parseList($this->extractField($firstRow, ['learning_outcomes', 'learning_outcome', 'outcomes']));
            $highlights = $this->parseList($this->extractField($firstRow, ['highlights', 'course_highlights']));

            // ── Booleans ─────────────────────────────────────────────
            $isCamp = $this->parseBool($this->extractField($firstRow, ['is_camp', 'camp']));
            $isTopCourse = $this->parseBool($this->extractField($firstRow, ['is_top_course', 'top_course', 'featured']));

            // ── Class count ──────────────────────────────────────────
            $declaredClassesRaw = $this->extractField($firstRow, ['number_of_classes', 'total_classes', 'classes']);
            $numberClasses = (int) ($declaredClassesRaw ?: count($classes));
            if ($numberClasses <= 0) {
                $numberClasses = count($classes) ?: 8;
            }
            if ($declaredClassesRaw !== '' && (int) $declaredClassesRaw !== count($classes)) {
                $warnings[] = "{$where}: number_of_classes ({$declaredClassesRaw}) differs from the number of class rows (" . count($classes) . ').';
            }

            // ── Category (never auto-created) ────────────────────────
            $categoryName = null;
            if (empty($categoryIds)) {
                $csvCategoryName = trim($this->extractField($firstRow, ['category', 'categories', 'category_name']));
                if ($csvCategoryName !== '') {
                    $matches = Category::whereRaw('LOWER(name) = ?', [strtolower($csvCategoryName)])
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->get();
                    if ($matches->isNotEmpty()) {
                        $matched = $matches->first();
                        $categoryName = $matched->name;
                        if ($matches->count() > 1) {
                            $warnings[] = "{$where}: multiple active categories named '{$csvCategoryName}' — using #{$matched->id} ({$matched->slug}). Merge duplicates under Admin → Categories.";
                        }
                    } else {
                        $warnings[] = "{$where}: category '{$csvCategoryName}' not found — course left uncategorised. Add it under Categories first.";
                    }
                }
            }

            // ── Tutor (matched by email) ─────────────────────────────
            $tutorId = null;
            $tutorName = null;
            $tutorEmail = strtolower(trim($this->extractField($firstRow, ['tutor_email', 'instructor_email', 'tutor'])));
            if ($tutorEmail !== '') {
                $tutor = User::where('role', 'tutor')->whereRaw('LOWER(email) = ?', [$tutorEmail])->first();
                if ($tutor) {
                    $tutorId = $tutor->id;
                    $tutorName = $tutor->name;
                } else {
                    $warnings[] = "{$where}: tutor_email '{$tutorEmail}' matches no tutor — course left unassigned.";
                }
            }

            if (empty($classes)) {
                $warnings[] = "{$where}: no class rows found — the course will have an empty syllabus.";
            }

            // ── SEO ──────────────────────────────────────────────────
            $seoTitle = $this->extractField($firstRow, ['seo_title', 'meta_title']) ?: null;
            $seoDescription = $this->extractField($firstRow, ['seo_description', 'meta_description']) ?: null;
            $seoMetadata = ($seoTitle || $seoDescription)
                ? array_filter(['title' => $seoTitle, 'description' => $seoDescription])
                : null;

            // ── Media ────────────────────────────────────────────────
            $thumbnailUrl = $this->extractField($firstRow, ['thumbnail_url', 'thumbnail', 'image_url', 'image', 'img']) ?: null;
            $videoUrl = $this->extractField($firstRow, ['video_url', 'video', 'promo_video', 'promo_video_url']) ?: null;
            $welcomeMessage = $this->extractField($firstRow, ['welcome_message', 'welcome']) ?: null;
            $welcomeVideoUrl = $this->extractField($firstRow, ['welcome_video_url', 'welcome_video']) ?: null;

            foreach (['thumbnail_url' => $thumbnailUrl, 'video_url' => $videoUrl, 'welcome_video_url' => $welcomeVideoUrl] as $label => $value) {
                if ($value !== null && $this->isLikelyInvalidUrl($value)) {
                    $warnings[] = "{$where}: {$label} '{$value}' does not look like a valid URL.";
                }
            }
            foreach ($classes as $class) {
                if ($class['practical_video_url'] !== null && $this->isLikelyInvalidUrl($class['practical_video_url'])) {
                    $warnings[] = "{$where}: class '{$class['title']}' practical_video_url '{$class['practical_video_url']}' does not look like a valid URL.";
                }
            }

            // ── Existing course? ─────────────────────────────────────
            $match = Course::where('slug', $slug)->orWhere('title', $title)->first();

            if ($match && !$updateExisting) {
                $errors[] = "{$where}: a course with this title or slug already exists. Enable \"Update existing course\" to update it, or change the course_slug.";
                continue;
            }

            $existing = $updateExisting ? $match : null;

            $gradeRange = ($gradeMin !== null || $gradeMax !== null)
                ? trim(($gradeMin ?? '?') . '–' . ($gradeMax ?? '?'))
                : null;

            $plans[] = [
                'title'       => $title,
                'slug'        => $slug,
                'existing'    => $existing,
                'action'      => $existing ? 'update' : 'create',
                'category'    => $categoryName,
                'age_group'   => $ageGroupSlug,
                'grade_range' => $gradeRange,
                'price_usd'   => $priceUsd,
                'status'      => $status,
                'tutor_name'  => $tutorName,
                'class_count' => count($classes),
                'classes'     => $classes,
                'fields'      => [
                    'tutor_id'          => $tutorId,
                    'title'             => $title,
                    'description'       => $this->extractField($firstRow, ['course_description', 'description', 'summary', 'overview']) ?: null,
                    'importance'        => $this->extractField($firstRow, ['importance', 'why_this_course', 'why_it_matters', 'short_description']) ?: null,
                    'format'            => $format,
                    'course_type'       => $courseType,
                    'price_usd'         => $priceUsd,
                    'price_aed'         => $priceAed,
                    'price_inr'         => $priceInr,
                    'price'             => $priceUsd,
                    'per_class_price'   => $perClassPrice,
                    'number_of_classes' => $numberClasses,
                    'thumbnail_url'     => $thumbnailUrl,
                    'video_url'         => $videoUrl,
                    'welcome_message'   => $welcomeMessage,
                    'welcome_video_url' => $welcomeVideoUrl,
                    'learning_outcomes' => $learningOutcomes,
                    'highlights'        => $highlights,
                    'seo_metadata'      => $seoMetadata,
                    'age_group'         => $ageGroupSlug,
                    'grade_min'         => $gradeMin,
                    'grade_max'         => $gradeMax,
                    'status'            => $status,
                    'is_camp'           => $isCamp,
                    'is_top_course'     => $isTopCourse,
                    'is_active'         => true,
                ],
            ];
        }

        return $plans;
    }

    /**
     * Parse an uploaded CSV file into associative rows.
     *
     * Uses a quote-aware record splitter so that quoted fields containing
     * newlines are handled correctly and each row carries its real line number
     * (in the "__line" key) for accurate error reporting.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function parseCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            return [];
        }

        $content = (string) file_get_contents($path);
        if ($content === '') {
            return [];
        }

        // Strip a UTF-8 BOM if present.
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $firstLine = str_contains($content, "\n") ? strstr($content, "\n", true) : $content;
        $delimiter = $this->detectDelimiter((string) $firstLine);

        $records = $this->splitCsvRecords($content, $delimiter);

        $header = null;
        $rows = [];

        foreach ($records as $record) {
            $fields = $record['fields'];

            if (count($fields) === 1 && trim((string) $fields[0]) === '') {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($col) => $this->normalizeKey((string) $col), $fields);
                continue;
            }

            if (count($fields) < count($header)) {
                $fields = array_pad($fields, count($header), '');
            }

            $row = ['__line' => $record['line']];
            foreach ($header as $idx => $key) {
                $row[$key] = trim((string) ($fields[$idx] ?? ''));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Split raw CSV content into records, respecting quoted fields (including
     * embedded newlines). Returns each record with its starting line number.
     *
     * @return array<int,array{line:int,fields:array<int,string>}>
     */
    protected function splitCsvRecords(string $content, string $delimiter): array
    {
        $records = [];
        $fields = [];
        $field = '';
        $inQuotes = false;
        $line = 1;
        $recordLine = 1;
        $len = strlen($content);

        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];

            if ($inQuotes) {
                if ($ch === '"') {
                    if ($i + 1 < $len && $content[$i + 1] === '"') {
                        $field .= '"';
                        $i++;
                    } else {
                        $inQuotes = false;
                    }
                } elseif ($ch === "\n") {
                    $field .= "\n";
                    $line++;
                } elseif ($ch === "\r") {
                    $field .= "\n";
                    if ($i + 1 < $len && $content[$i + 1] === "\n") {
                        $i++;
                    }
                    $line++;
                } else {
                    $field .= $ch;
                }
                continue;
            }

            if ($ch === '"') {
                $inQuotes = true;
            } elseif ($ch === $delimiter) {
                $fields[] = $field;
                $field = '';
            } elseif ($ch === "\n" || $ch === "\r") {
                if ($ch === "\r" && $i + 1 < $len && $content[$i + 1] === "\n") {
                    $i++;
                }
                $fields[] = $field;
                $records[] = ['line' => $recordLine, 'fields' => $fields];
                $fields = [];
                $field = '';
                $line++;
                $recordLine = $line;
            } else {
                $field .= $ch;
            }
        }

        if ($field !== '' || !empty($fields)) {
            $fields[] = $field;
            $records[] = ['line' => $recordLine, 'fields' => $fields];
        }

        return $records;
    }

    /**
     * Pick the most likely delimiter from a header line.
     */
    protected function detectDelimiter(string $line): string
    {
        $delimiter = ',';
        $max = 0;
        foreach ([',', ';', "\t"] as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $max) {
                $max = $count;
                $delimiter = $candidate;
            }
        }
        return $delimiter;
    }

    protected function normalizeKey(string $column): string
    {
        return strtolower(trim(str_replace([' ', '-', '.', '/'], '_', $column)));
    }

    /**
     * Extract a field value, checking multiple possible column aliases.
     */
    protected function extractField(array $row, array $aliases): string
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeKey($alias);
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }
        return '';
    }

    protected function extractIntOrNull(array $row, array $aliases): ?int
    {
        $value = $this->extractField($row, $aliases);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }

    protected function extractFloat(array $row, array $aliases, float $default): float
    {
        return $this->extractFloatOrNull($row, $aliases) ?? $default;
    }

    protected function extractFloatOrNull(array $row, array $aliases): ?float
    {
        $value = $this->extractField($row, $aliases);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    /**
     * Split a multi-value cell into a clean list. Supports "|" and newlines.
     *
     * @return array<int,string>|null
     */
    protected function parseList(string $value): ?array
    {
        if (trim($value) === '') {
            return null;
        }
        $parts = preg_split('/\s*[|\n]\s*/', $value) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
        return empty($parts) ? null : $parts;
    }

    protected function parseBool(string $value): ?bool
    {
        $v = strtolower(trim($value));
        if ($v === '') {
            return null;
        }
        return in_array($v, ['1', 'yes', 'y', 'true', 'on'], true);
    }

    /**
     * True when a media value is clearly not a usable URL. Relative paths
     * (starting with "/") are always accepted.
     */
    protected function isLikelyInvalidUrl(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '/')) {
            return false;
        }
        if (!preg_match('~^https?://~i', $value)) {
            return true;
        }
        return !filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Build a normalized lookup map (value => age group slug) from active groups.
     */
    protected function buildAgeGroupLookup($ageGroups): array
    {
        $norm = fn ($s) => $this->normalizeAgeKey((string) $s);
        $lookup = [];
        foreach ($ageGroups as $group) {
            $lookup[$norm($group->slug)] = $group->slug;
            $lookup[$norm($group->name)] = $group->slug;
            if ($group->age_range) {
                $lookup[$norm($group->age_range)] = $group->slug;
            }
        }
        return $lookup;
    }

    protected function normalizeAgeKey(string $value): string
    {
        $s = strtolower(trim($value));
        $s = str_replace(['–', '—', ' to ', ' through ', ' thru '], '-', $s);
        $s = preg_replace('/(\d+)(st|nd|rd|th)\b/', '$1', $s) ?? $s;
        $s = preg_replace('/\bgrade[s]?\b/', '', $s) ?? $s;
        $s = preg_replace('/\s+/', '', $s) ?? $s;
        return trim($s, '-');
    }

    /**
     * Resolve an AgeGroup slug by raw value (e.g. "6-8", "Grades 6–8", "Grade 7").
     * Returns null when nothing matches — the caller reports this as an error
     * instead of silently assigning the wrong age group.
     */
    protected function resolveAgeGroup(?string $raw, array $lookup): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $key = $this->normalizeAgeKey($raw);

        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        // Range like "6-8" written differently.
        if (preg_match('/(\d+)-(\d+)/', $key, $m)) {
            $range = $m[1] . '-' . $m[2];
            if (isset($lookup[$range])) {
                return $lookup[$range];
            }
        }

        // Single grade like "7" — find the band that contains it.
        if (preg_match('/^(\d+)$/', $key, $m)) {
            $grade = (int) $m[1];
            foreach ($lookup as $lookupKey => $slug) {
                if (preg_match('/^(\d+)-(\d+)$/', $lookupKey, $rm) && $grade >= (int) $rm[1] && $grade <= (int) $rm[2]) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * Build a CSV hint row padded to the full column width, with a value placed
     * in the given target column.
     */
    protected function hintRow(string $label, string $targetColumn, string $value): array
    {
        $row = array_fill(0, count(self::COLUMNS), '');
        $row[0] = $label;

        $index = array_search($targetColumn, self::COLUMNS, true);
        if ($index !== false) {
            $row[$index] = $value;
        }

        return $row;
    }
}

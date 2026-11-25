<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\CareerPathway;
use App\Models\Course;
use App\Support\GradeBand;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $user = $request->user();
        $grade = $user->grade !== null ? (int) $user->grade : null;
        $band = GradeBand::fromGrade($grade);

        if (in_array($band, [GradeBand::EARLY], true)) {
            return $this->error('Career explorer opens in grades 6–12.', 403, null, [
                'grade' => $grade,
                'grade_band' => $band,
                'grade_band_label' => GradeBand::label($band),
            ]);
        }

        $pathways = CareerPathway::active()
            ->forGrade($grade ?: 8)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        if ($pathways->isEmpty()) {
            $this->seedDefaults();
            $pathways = CareerPathway::active()->forGrade($grade ?: 8)->orderBy('sort_order')->get();
        }

        $courseIds = $pathways->flatMap(fn ($p) => $p->related_course_ids ?? [])->unique()->filter()->all();
        $courses = Course::whereIn('id', $courseIds)->where('status', 'published')->get()->keyBy('id');

        return $this->success([
            'grade' => $grade,
            'grade_band' => $band,
            'grade_band_label' => GradeBand::label($band),
            'pathways' => $pathways->map(function (CareerPathway $p) use ($courses) {
                $related = collect($p->related_course_ids ?? [])
                    ->map(fn ($id) => $courses->get($id))
                    ->filter()
                    ->map(fn (Course $c) => [
                        'id' => $c->id,
                        'title' => $c->title,
                        'slug' => $c->slug,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'tagline' => $p->tagline,
                    'description' => $p->description,
                    'icon' => $p->icon,
                    'skill_tags' => $p->skill_tags ?? [],
                    'courses' => $related,
                ];
            })->values()->all(),
        ]);
    }

    private function seedDefaults(): void
    {
        $defaults = [
            [
                'slug' => 'software-creator',
                'title' => 'Software creator',
                'tagline' => 'Build apps, games, and tools people love',
                'description' => 'Explore coding, product thinking, and how software teams ship ideas.',
                'icon' => 'code',
                'min_grade' => 6,
                'max_grade' => 12,
                'skill_tags' => ['coding', 'problem-solving', 'design'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'data-storyteller',
                'title' => 'Data storyteller',
                'tagline' => 'Turn numbers into clear insights',
                'description' => 'Learn how analysts use data, charts, and questions to explain the world.',
                'icon' => 'analytics',
                'min_grade' => 7,
                'max_grade' => 12,
                'skill_tags' => ['math', 'visualization', 'critical-thinking'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'creative-technologist',
                'title' => 'Creative technologist',
                'tagline' => 'Blend art, design, and interactive media',
                'description' => 'Pathways into digital art, animation, UX, and creative coding.',
                'icon' => 'palette',
                'min_grade' => 6,
                'max_grade' => 12,
                'skill_tags' => ['design', 'animation', 'storytelling'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'stem-innovator',
                'title' => 'STEM innovator',
                'tagline' => 'Robotics, engineering, and invention',
                'description' => 'Hands-on engineering habits that transfer to labs, makerspaces, and competitions.',
                'icon' => 'precision_manufacturing',
                'min_grade' => 6,
                'max_grade' => 12,
                'skill_tags' => ['robotics', 'engineering', 'experimentation'],
                'sort_order' => 4,
            ],
            [
                'slug' => 'future-leader',
                'title' => 'Future leader',
                'tagline' => 'Communication, entrepreneurship, and impact',
                'description' => 'Practice pitching ideas, collaborating, and building projects that help others.',
                'icon' => 'campaign',
                'min_grade' => 8,
                'max_grade' => 12,
                'skill_tags' => ['leadership', 'communication', 'entrepreneurship'],
                'sort_order' => 5,
            ],
        ];

        foreach ($defaults as $row) {
            CareerPathway::updateOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }
}

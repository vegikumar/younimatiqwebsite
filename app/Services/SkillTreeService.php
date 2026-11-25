<?php

namespace App\Services;

use App\Models\Course;
use App\Models\SkillNode;
use App\Models\SkillTree;
use App\Models\StudentPortfolio;
use App\Models\StudentSkillProgress;
use Illuminate\Support\Facades\Log;

class SkillTreeService
{
    /**
     * Get or create skill tree for course and compute child's mastery level & progress.
     */
    public function getStudentSkillTree(int $studentId, int $courseId): array
    {
        $course = Course::find($courseId);
        $skillTree = $this->ensureTree($courseId, $course?->title);

        $nodes = $skillTree->nodes()->orderBy('level_number')->orderBy('order')->get();
        $progressRecords = StudentSkillProgress::where('user_id', $studentId)
            ->whereIn('skill_node_id', $nodes->pluck('id'))
            ->get()
            ->keyBy('skill_node_id');

        $masteredByLevel = [];
        $countByLevel = [];
        foreach ($nodes as $node) {
            $countByLevel[$node->level_number] = ($countByLevel[$node->level_number] ?? 0) + 1;
            $rec = $progressRecords->get($node->id);
            if ($rec && $rec->status === 'mastered') {
                $masteredByLevel[$node->level_number] = ($masteredByLevel[$node->level_number] ?? 0) + 1;
            }
        }

        $masteredCount = 0;
        $maxMasteredLevel = 0;

        $nodesData = $nodes->map(function ($node) use ($progressRecords, $masteredByLevel, $countByLevel, &$masteredCount, &$maxMasteredLevel) {
            $record = $progressRecords->get($node->id);
            $prevComplete = $node->level_number <= 1
                || (($masteredByLevel[$node->level_number - 1] ?? 0) >= ($countByLevel[$node->level_number - 1] ?? 1));

            if ($record && $record->status === 'mastered') {
                $status = 'mastered';
                $masteredCount++;
                $maxMasteredLevel = max($maxMasteredLevel, (int) $node->level_number);
            } elseif ($prevComplete) {
                $status = $record?->status === 'in_progress' ? 'in_progress' : 'in_progress';
            } else {
                $status = 'locked';
            }

            return [
                'id' => $node->id,
                'title' => $node->title,
                'description' => $node->description,
                'level_number' => $node->level_number,
                'icon' => $node->icon,
                'status' => $status,
            ];
        });

        $portfolios = StudentPortfolio::where('user_id', $studentId)
            ->where('course_id', $courseId)
            ->latest()
            ->get();

        return [
            'course_title' => $course->title ?? 'Coding App Lab',
            'current_level_label' => ($course->title ?? 'Course').': Level '.max(1, $maxMasteredLevel).' of '.$skillTree->total_levels,
            'current_level' => max(1, $maxMasteredLevel),
            'total_levels' => $skillTree->total_levels,
            'mastered_nodes' => $masteredCount,
            'total_nodes' => $nodes->count(),
            'completion_percentage' => round(($masteredCount / max(1, $nodes->count())) * 100),
            'nodes' => $nodesData,
            'portfolios' => $portfolios,
        ];
    }

    public function masterNode(int $studentId, int $nodeId): StudentSkillProgress
    {
        return StudentSkillProgress::updateOrCreate(
            [
                'user_id' => $studentId,
                'skill_node_id' => $nodeId,
            ],
            [
                'status' => 'mastered',
                'mastered_at' => now(),
            ]
        );
    }

    /**
     * Advance the next unlocked skill node after real learning activity; grant level milestones.
     */
    public function advanceForActivity(int $studentId, int $courseId): ?array
    {
        try {
            $tree = $this->ensureTree($courseId, Course::find($courseId)?->title);
            $nodes = $tree->nodes()->orderBy('level_number')->orderBy('order')->get();
            if ($nodes->isEmpty()) {
                return null;
            }

            $progress = StudentSkillProgress::where('user_id', $studentId)
                ->whereIn('skill_node_id', $nodes->pluck('id'))
                ->get()
                ->keyBy('skill_node_id');

            $masteredByLevel = [];
            $countByLevel = [];
            foreach ($nodes as $node) {
                $countByLevel[$node->level_number] = ($countByLevel[$node->level_number] ?? 0) + 1;
                $rec = $progress->get($node->id);
                if ($rec && $rec->status === 'mastered') {
                    $masteredByLevel[$node->level_number] = ($masteredByLevel[$node->level_number] ?? 0) + 1;
                }
            }

            $target = null;
            foreach ($nodes as $node) {
                $rec = $progress->get($node->id);
                if ($rec && $rec->status === 'mastered') {
                    continue;
                }
                $prevComplete = $node->level_number <= 1
                    || (($masteredByLevel[$node->level_number - 1] ?? 0) >= ($countByLevel[$node->level_number - 1] ?? 1));
                if ($prevComplete) {
                    $target = $node;
                    break;
                }
            }

            if (! $target) {
                return ['mastered' => null, 'milestones' => []];
            }

            $this->masterNode($studentId, $target->id);
            $masteredByLevel[$target->level_number] = ($masteredByLevel[$target->level_number] ?? 0) + 1;

            $milestones = [];
            $level = (int) $target->level_number;
            if (($masteredByLevel[$level] ?? 0) >= ($countByLevel[$level] ?? 1)) {
                try {
                    $milestone = app(MilestoneBadgeService::class)->grantMilestone($studentId, $courseId, $level);
                    $milestones[] = [
                        'level' => $level,
                        'badge' => $milestone->badge?->title ?? "Level {$level}",
                        'certificate_hash' => $milestone->certificate_hash,
                    ];
                } catch (\Throwable $e) {
                    Log::warning('Milestone grant failed: '.$e->getMessage());
                }
            }

            return [
                'mastered' => ['id' => $target->id, 'title' => $target->title, 'level' => $level],
                'milestones' => $milestones,
            ];
        } catch (\Throwable $e) {
            Log::warning('Skill advance failed: '.$e->getMessage());

            return null;
        }
    }

    private function ensureTree(int $courseId, ?string $courseTitle): SkillTree
    {
        $skillTree = SkillTree::firstOrCreate(
            ['course_id' => $courseId],
            [
                'name' => ($courseTitle ?? 'Coding').' Skill Tree',
                'total_levels' => 4,
            ]
        );

        if ($skillTree->nodes()->count() === 0) {
            $defaultNodes = [
                ['title' => 'Level 1: Variables & Data Types', 'level_number' => 1, 'icon' => 'data_object'],
                ['title' => 'Level 1: Conditional Logic', 'level_number' => 1, 'icon' => 'alt_route'],
                ['title' => 'Level 2: Loops & Automation', 'level_number' => 2, 'icon' => 'loop'],
                ['title' => 'Level 2: Custom Functions', 'level_number' => 2, 'icon' => 'code'],
                ['title' => 'Level 3: App Interfaces', 'level_number' => 3, 'icon' => 'dashboard'],
                ['title' => 'Level 4: Full App Project', 'level_number' => 4, 'icon' => 'rocket_launch'],
            ];

            foreach ($defaultNodes as $index => $node) {
                SkillNode::create([
                    'skill_tree_id' => $skillTree->id,
                    'title' => $node['title'],
                    'description' => "Mastering {$node['title']} concepts.",
                    'icon' => $node['icon'],
                    'level_number' => $node['level_number'],
                    'order' => $index + 1,
                ]);
            }
        }

        return $skillTree->fresh('nodes');
    }
}

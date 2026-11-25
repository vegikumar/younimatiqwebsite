<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelColorTokenTest extends TestCase
{
    use RefreshDatabase;

    private const STOCK_PALETTE = '/(?:bg|text|border|from|to|via|ring|divide|fill|stroke|accent|decoration|outline)-(?:slate|gray|zinc|emerald|green|lime|teal|red|rose|amber|yellow|orange|blue|sky|indigo|cyan|purple|violet|fuchsia|pink)-\d{2,3}/';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function tutor(): User
    {
        return User::factory()->create(['role' => 'tutor', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function assertNoStockPalette(string $html, string $context): void
    {
        preg_match_all(self::STOCK_PALETTE, $html, $m);
        $this->assertSame([], array_values(array_unique($m[0])), "Stock Tailwind palette classes leaked into {$context}");
    }

    public function test_admin_pages_use_design_tokens(): void
    {
        $admin = $this->admin();

        foreach ([
            'admin.dashboard' => [],
            'admin.courses' => [],
            'admin.enrollments' => [],
            'admin.students' => [],
            'admin.tutors' => [],
        ] as $route => $_) {
            $html = $this->actingAs($admin, 'admin')->get(route($route))->assertOk()->getContent();
            $this->assertNoStockPalette($html, $route);
        }
    }

    public function test_tutor_pages_use_design_tokens(): void
    {
        $tutor = $this->tutor();

        foreach (['tutor.dashboard', 'tutor.classes'] as $route) {
            $html = $this->actingAs($tutor, 'tutor')->get(route($route))->assertOk()->getContent();
            $this->assertNoStockPalette($html, $route);
        }
    }

    public function test_student_pages_use_design_tokens(): void
    {
        $student = $this->student();

        foreach (['student.dashboard', 'student.courses'] as $route) {
            $html = $this->actingAs($student, 'web')->get(route($route))->assertOk()->getContent();
            $this->assertNoStockPalette($html, $route);
        }
    }
}

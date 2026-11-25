<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\ChatbotTraining;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->get()->map(fn (Faq $faq) => [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'category' => $faq->category,
            'sort_order' => $faq->sort_order,
            'is_active' => (bool) $faq->is_active,
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/Faqs/Index', [
            'faqs' => $faqs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);
        $faq = Faq::create($data);
        $this->syncToChatbot($faq);
        return back()->with('success', 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $faq->update($data);
        $this->syncToChatbot($faq);
        return back()->with('success', 'FAQ updated.');
    }

    private function syncToChatbot(Faq $faq): void
    {
        ChatbotTraining::updateOrCreate(
            ['question' => $faq->question],
            ['answer' => $faq->answer, 'tags' => $faq->category ?? 'faq']
        );
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return back()->with('success', 'FAQ deleted.');
    }
}

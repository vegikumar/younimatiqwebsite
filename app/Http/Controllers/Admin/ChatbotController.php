<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotTraining;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function index()
    {
        $trainings = ChatbotTraining::orderBy('created_at', 'desc')->get()->map(fn (ChatbotTraining $t) => [
            'id' => $t->id,
            'question' => $t->question,
            'tags' => $t->tags,
            'answer_snippet' => \Illuminate\Support\Str::limit($t->answer, 60),
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/Chatbot/Index', [
            'trainings' => $trainings,
        ]);
    }

    public function create()
    {
        return \App\Support\InertiaPage::react('Admin/Chatbot/Form', [
            'training' => null,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'tags' => 'nullable|string|max:255',
        ]);

        ChatbotTraining::create($validated);

        return redirect()->route('admin.chatbot.index')->with('success', 'Chatbot training data added successfully.');
    }

    public function edit(ChatbotTraining $chatbot)
    {
        return \App\Support\InertiaPage::react('Admin/Chatbot/Form', [
            'training' => [
                'id' => $chatbot->id,
                'question' => $chatbot->question,
                'answer' => $chatbot->answer,
                'tags' => $chatbot->tags,
            ],
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, ChatbotTraining $chatbot)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'tags' => 'nullable|string|max:255',
        ]);

        $chatbot->update($validated);

        return redirect()->route('admin.chatbot.index')->with('success', 'Chatbot training data updated successfully.');
    }

    public function destroy(ChatbotTraining $chatbot)
    {
        $chatbot->delete();

        return redirect()->route('admin.chatbot.index')->with('success', 'Chatbot training data deleted successfully.');
    }
}

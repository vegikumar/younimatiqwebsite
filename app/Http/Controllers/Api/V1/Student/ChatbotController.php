<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ChatbotTraining;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    use RespondsWithJson;

    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = strtolower($data['message']);

        $stopwords = ['a', 'an', 'the', 'is', 'to', 'in', 'on', 'of', 'for', 'with', 'and', 'or', 'but', 'can', 'you', 'please', 'are', 'am', 'i', 'we', 'do', 'does', 'did', 'have', 'has', 'had', 'what', 'where', 'when', 'why', 'how', 'there', 'their', 'they', 'it', 'at', 'this', 'that', 'these', 'those'];
        $keywords = array_filter(
            explode(' ', preg_replace('/[^a-z0-9 ]/', '', $message)),
            fn ($word) => ! in_array($word, $stopwords, true) && strlen($word) > 1
        );

        if (empty($keywords)) {
            return $this->success([
                'answer' => "I'm sorry, I didn't quite catch that. Could you please rephrase?",
            ]);
        }

        $query = ChatbotTraining::query();
        foreach ($keywords as $keyword) {
            $query->orWhere('question', 'LIKE', '%'.$keyword.'%')
                ->orWhere('tags', 'LIKE', '%'.$keyword.'%');
        }

        $match = $query->first();

        if ($match) {
            return $this->success(['answer' => $match->answer]);
        }

        return $this->success([
            'answer' => "I'm still learning! For detailed inquiries, please reach out via our Contact page or WhatsApp.",
        ]);
    }
}

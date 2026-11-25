<?php

namespace App\Http\Controllers;

use App\Models\ChatbotTraining;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $message = strtolower($request->message);

        // Very basic NLP - split message into keywords and search tags or questions
        $stopwords = ['a', 'an', 'the', 'is', 'to', 'in', 'on', 'of', 'for', 'with', 'and', 'or', 'but', 'can', 'you', 'please', 'are', 'am', 'i', 'we', 'do', 'does', 'did', 'have', 'has', 'had', 'what', 'where', 'when', 'why', 'how', 'there', 'their', 'they', 'it', 'at', 'this', 'that', 'these', 'those'];
        $keywords = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', '', $message)), function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 1;
        });

        if (empty($keywords)) {
            return response()->json([
                'answer' => "I'm sorry, I didn't quite catch that. Could you please rephrase?"
            ]);
        }

        $query = ChatbotTraining::query();
        
        foreach ($keywords as $keyword) {
            $query->orWhere('question', 'LIKE', '%' . $keyword . '%')
                  ->orWhere('tags', 'LIKE', '%' . $keyword . '%');
        }

        $match = $query->first();

        if ($match) {
            return response()->json([
                'answer' => $match->answer
            ]);
        }

        return response()->json([
            'answer' => "I'm still learning! For detailed inquiries, please reach out via our Contact page or WhatsApp."
        ]);
    }
}

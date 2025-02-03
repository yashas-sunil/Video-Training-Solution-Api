<?php

namespace App\Services\Professor;

use App\Models\Answer;
use App\Models\AskAQuestion;
use Illuminate\Support\Facades\Auth;

class AnswerService
{
    public function create($attributes = [])
    {
        $attributes['user_id'] = Auth::id();
        $attributes['answer']= nl2br($attributes['answer']);
        $answer = Answer::query()
            ->where('question_id', $attributes['question_id'])
            ->first();

        if ($answer) {
            $answer->answer = $attributes['answer'];
            $answer->save();

            return $answer;
        } else {
            return Answer::create($attributes);
        }
    }
}

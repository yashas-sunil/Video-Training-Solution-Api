<?php

namespace App\Services;

use App\Models\AskAQuestion;

class AskAQuestionService
{
    /**
     * @param array $attributes
     * @return AskAQuestion
     */
    public function create($attributes = [])
    {
        return AskAQuestion::create($attributes);
    }

    /**
     * @param integer $id
     * @param array $attributes
     * @return AskAQuestion
     */
    public function update($id, $attributes = [])
    {
        $question = AskAQuestion::findOrFail($id);

        $question->update($attributes);

        return $question;
    }

    /**
     * @param integer $id
     */
    public function delete($id = null)
    {
        $question = AskAQuestion::findOrFail($id);

        $question->delete();
    }
}

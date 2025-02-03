<?php


namespace App\Services;

use App\Models\StudentNote;
use Illuminate\Support\Facades\Auth;

class StudentNoteService
{
    /**
     * @param $attributes array
     * @return StudentNote
     */
    public function create($attributes) {
        return StudentNote::create($attributes);
    }

    /**
     * @param $id
     * @param array $attributes
     * @return StudentNote
     */
    public function update($id, $attributes = [])
    {
        $studentNote = StudentNote::with('video.course', 'video.level', 'video.subject', 'video.chapter')->where('id',$id)
                                    ->where('user_id',Auth::id())->first();

        $studentNote->update($attributes);

        return $studentNote;
    }

    /**
     * @param StudentNote $note
     * @return bool
     * @throws \Exception
     */
    public function delete(StudentNote $note) {
        return $note->delete();
    }
}

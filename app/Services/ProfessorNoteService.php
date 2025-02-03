<?php

namespace App\Services;

use App\Models\ProfessorNote;
use Illuminate\Support\Facades\Auth;

class ProfessorNoteService
{
    /**
     * @param $attributes array
     * @return ProfessorNote
     */
    public function create($attributes) {
        $attributes['user_id'] = Auth::id();

        return ProfessorNote::create($attributes);
    }

    /**
     * @param ProfessorNote $professorNote
     * @param $attributes array
     * @return bool
     */
    public function update($professorNote, $attributes) {
        $attributes['user_id'] = Auth::id();

        return $professorNote->update($attributes);
    }

    /**
     * @param ProfessorNote $professorNote
     * @return bool
     * @throws \Exception
     */
    public function delete($professorNote) {
        return $professorNote->delete();
    }
}

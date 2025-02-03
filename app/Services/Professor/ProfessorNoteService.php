<?php

namespace App\Services\Professor;

use App\Models\Professor;
use App\Models\ProfessorNote;
use App\Models\User;

class ProfessorNoteService
{
    public function create($attributes) {
        return ProfessorNote::create($attributes);
    }
}

<?php

namespace App\Services\Professor;

use App\Models\Professor;
use App\Models\User;

class ProfileService
{
    public function update($id = null, $attributes = [])
    {
        $professor = Professor::findOrFail($id);
        $professor->update($attributes);

        $user = User::findOrFail($professor->user_id);
        $user->name = $attributes['name'];
        $user->save();

        return $professor;
    }
}

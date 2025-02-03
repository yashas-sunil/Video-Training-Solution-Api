<?php

namespace App\Services\Associate;

use Illuminate\Support\Facades\Hash;
use App\Models\Associate;
use App\Models\User;

class ProfileService
{
    public function update($id, $attributes = [])
    {
        if ($attributes['password']) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        unset($attributes['confirm_password']);

        $user = User::find($id);
        $user->update($attributes);

        unset($attributes['password']);

//        $associate = Associate::where('user_id', $id)->first();
//        $associate->update($attributes);

        return $user->load('associate');
    }
}

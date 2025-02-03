<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetPasswordService
{
    public function update($attributes = [])
    {
        $passwordReset = PasswordReset::where('token', $attributes['token'])->first();

        if ($passwordReset) {
            $user = User::where('email', $passwordReset->email)->whereIn('role', [5,6,7])->first();
            $user->password = Hash::make($attributes['password']);
            $user->save();

            PasswordReset::where('token', $attributes['token'])->delete();

            return ['reset' => true];
        }

        return ['reset' => false];
    }
}

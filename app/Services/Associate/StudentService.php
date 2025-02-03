<?php

namespace App\Services\Associate;

use App\Mail\VerifiedMail;
use App\Mail\VerifyMail;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignUpMail;
use Illuminate\Support\Str;
use Mockery\Exception;

class StudentService
{
    public function create($attributes = [])
    {
        $user = new User();
        $user->name = $attributes['name'];
        $user->country_code = $attributes['mobile_code'];
        $user->phone = $attributes['phone'];
        $user->email = $attributes['email'];
        $password = Str::random(8);
        $user->password = Hash::make($password);
        $user->role = 5;
        $user->verification_token = Str::random(16);
        $user->save();

        $student = new Student();
        $student->user_id = $user->id;

        if (auth('api')->user()->role == 7) {
            $student->associate_id = auth('api')->id();
        }
        if (auth('api')->user()->role == 11) {
            $student->branch_manager_id = auth('api')->id();
        }

        $student->name = $attributes['name'];
        $student->email = $attributes['email'];
        $student->country_code = $attributes['mobile_code'];
        $student->phone = $attributes['phone'];
        $student->country_id = $attributes['country_id'];
        $student->state_id = $attributes['state_id'];
        $student->city = $attributes['city'];
        $student->pin = $attributes['pin'];
        $student->course_id = $attributes['course_id'];
        $student->level_id = $attributes['level_id'];
        $student->save();

        $address = new Address();
        $address->user_id = $user->id;
        $address->name = $attributes['name'];
        $address->country_code = $attributes['mobile_code'];
        $address->phone = $attributes['phone'];
        $address->city = $attributes['city'];
        $address->country = $attributes['country_id_text'];
        $address->state = $attributes['state_id_text'];
        $address->pin = $attributes['pin'];
        $address->address = $attributes['address'];
        $address->area = $attributes['area'];
        $address->landmark = $attributes['landmark'];
        $address->address_type = 1;
        $address->save();

        try {
            Mail::send(new VerifyMail($user));
        } catch (Exception $exception) {
//            info ($exception->getTraceAsString());
        }

        return Student::query()->where('id', $student->id)->with('addresses')->first();
    }

    public function update($attributes, $id)
    {
        /** @var Student $student */
        $student = Student::query()->find($id);
        $student->name = $attributes['name'];
        $student->email = $attributes['email'];
        $student->country_code = $attributes['mobile_code'];
        $student->phone = $attributes['phone'];
        $student->country_id = $attributes['country_id'];
        $student->state_id = $attributes['state_id'];
        $student->city = $attributes['city'];
        $student->pin = $attributes['pin'];
        $student->course_id = $attributes['course_id'];
        $student->level_id = $attributes['level_id'];
        $student->save();

        /** @var User $user */
        $user = User::query()->find($student->user_id);
        $user->name = $attributes['name'];
        $user->country_code = $attributes['mobile_code'];
        $user->phone = $attributes['phone'];
        $user->email = $attributes['email'];
        $user->save();

        /** @var Address $address */
        $address = Address::query()->find($student->user_id);
        $address->user_id = $user->id;
        $address->name = $attributes['name'];
        $address->country_code = $attributes['mobile_code'];
        $address->phone = $attributes['phone'];
        $address->city = $attributes['city'];
        $address->country = $attributes['country_id_text'];
        $address->state = $attributes['state_id_text'];
        $address->pin = $attributes['pin'];
        $address->address = $attributes['address'];
        $address->area = $attributes['area'];
        $address->landmark = $attributes['landmark'];
        $address->save();

        return Student::query()->where('id', $student->id)->with('addresses')->first();
    }

    public function sendVerificationMail($attributes = [])
    {
        /** @var Student $student */
        $student = Student::query()->find($attributes['student_id']);
        /** @var User $user */
        $user = User::query()->find($student->user_id);

        $user->verification_token = Str::random(16);
        $user->save();

        try {
            Mail::send(new VerifyMail($user));
        } catch (Exception $exception) {
//            info ($exception->getTraceAsString());
        }

        return $attributes;
    }
}

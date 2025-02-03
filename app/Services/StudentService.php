<?php


namespace App\Services;

use App\Models\Country;
use App\Models\Course;
use App\Models\Level;
use App\Models\Otp;
use App\Models\State;
use App\Models\Student;
use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
//use Illuminate\Validation\ValidationException;
use App\Models\Referral;
use Carbon\Carbon;
use App\Models\JMoney;
use App\Models\JMoneySetting;
use Illuminate\Support\Facades\Mail;
use App\Mail\JMoneyMail;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\StudentLog;
use App\Models\EmailLog;

class StudentService
{

    /**
     * @param $attributes array
     * @return Student
//     * @throws ValidationException
     */
    public function create($attributes) {
        info($attributes);
        $user = new User();
        $user->name = $attributes['name'];
        $user->country_code = $attributes['mobile_code'];
        $user->email = $attributes['email'];
        $user->country_code = $attributes['mobile_code'];
        $user->phone = $attributes['mobile'];
        $user->password = Hash::make($attributes['password']);
        $user->role = 5;
        $user->user_source=base64_encode(serialize($_SERVER));
        $user->save();

        $state = State::find($attributes['state_id']);
        $country = Country::find($attributes['country_id']);
        $course = Course::find($attributes['course_id']);
        $level = Level::find($attributes['level_id']);

        $student = new Student();
        $student->user_id = $user->id;
        $student->name = $attributes['name'];
        $student->email = $attributes['email'];
        $student->country_code = $attributes['mobile_code'];
        $student->phone = $attributes['mobile'];
        $student->country_id = $attributes['country_id'];
        $student->state_id = $attributes['state_id'];
        $student->city = $attributes['city'];
        $student->attempt_year = $attributes['attempt_year'];
        $student->gender = @$attributes['gender'];
        $student->pin = $attributes['pin'];
        $student->course_id = $attributes['course_id'];
        $student->level_id = $attributes['level_id'];
        $student->save();


        $student_log=new StudentLog();
        $student_log->user_id=$user->id;
        $student_log->ip_address=request()->ip();
        $student_log->log_type=1;
        $student_log->save();

        $address = new Address();
        $address->user_id = $user->id;
        $address->name = $attributes['name'];
        $address->country_code = $attributes['mobile_code'];
        $address->phone = $attributes['mobile'];
        $address->city = $attributes['city'];
        $address->state = $state->name;
        $address->country = $country->name;
        $address->pin = $attributes['pin'];
        $address->save();

        

        // Mail to user if JMoney greater than 0
        $j_points = JMoneySetting::first()->sign_up_point;
        if($j_points > 0){
            
            //Insert to jmoney table
            $jMoney = new JMoney();
            $jMoney->user_id = $user->id;
            $jMoney->activity = JMoney::SIGN_UP;
            $jMoney->points = JMoneySetting::first()->sign_up_point;
            $jMoney->expire_after = JMoneySetting::first()->sign_up_point_expiry;
            $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
            $jMoney->save();

            $attributes['j_amount'] = $j_points;
            try {
                Mail::send(new JMoneyMail($attributes));
                $email_log = new EmailLog();
                $email_log->email_to = $attributes['email'];
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "Your J-Koins Gift card is here";
                $email_log->save();
            }
            catch (\Exception $exception) {
                info($exception->getMessage(), ['exception' => $exception]);
            }
            // Insert to notification table

            $body = "Welcome to JK Shah Online Classes. We have added Rs.".$j_points." worth of J-Koins in your wallet. Kindly use the same for buying packages.
                    For regular updates, offers and more, please follow us on Telegram - jkshahonline and Instagram - officialjksc";
            
            $notification = new Notification();
            $notification->title = "J-Koins Notification";
            $notification->notification_body = $body;
            $notification->type = 1;
            $notification->save();

            $user_notification = new UserNotification();
            $user_notification->notification_id = $notification->id;
            $user_notification->user_id = $user->id;
            $user_notification->is_read = 0;
            $user_notification->save();
        }


        if (array_key_exists('referral', $attributes)) {
            $referral = Referral::where('code', $attributes['referral'])->first();

            if ($referral) {
                $jMoney = new JMoney();
                $jMoney->user_id = $referral->user_id;
                $jMoney->activity = JMoney::REFERRAL_ACTIVITY;
                $jMoney->points = JMoneySetting::first()->referral_activity_point;
                $jMoney->expire_after = JMoneySetting::first()->referral_activity_point_expiry;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->save();
            }
        }

        return $student;
    }

    /**
     * @param $student
     * @param $attributes array
     * @return Student
     */
    public function update($student, $attributes) {

//        $emailExists = User::query()->where('role', 5)->where('email', $attributes['email'])->where('id', '!=', Auth::id())->exists();
//
//        if ($emailExists) {
//            throw ValidationException::withMessages([
//                'email' => ['This email address already used']
//            ]);
//        }

//        $mobileExists = User::query()->where('role', 5)->where('email', $attributes['phone'])->where('id', '!=', Auth::id())->exists();
//
//        if ($mobileExists) {
//            throw ValidationException::withMessages([
//                'phone' => ['This mobile number already used']
//            ]);
//        }



        DB::beginTransaction();
        $user = User::findOrFail($student->user_id);
        $user->name = $attributes['name'];
        $user->country_code = $attributes['mobile_code'];
        $user->save();

        $student = Student::find($student->id);
        $student->name = $attributes['name'];
        $student->age = $attributes['age'];
        $student->country_code = $attributes['mobile_code'];
        $student->country_id = $attributes['country_id'];
        $student->state_id = $attributes['state_id'];
        $student->address = $attributes['address'];
        $student->city = $attributes['city'];
        $student->attempt_year = $attributes['attempt_year'];
        $student->pin = $attributes['pin'];
        $student->course_id = $attributes['course_id'];
        $student->level_id = $attributes['level_id'];
        $student->save();

        $address = Address::where('user_id',$student->user_id)->first();

        if(!$address){
            $address = new Address();
        }
        $address->user_id = $user->id;
        $address->name = $attributes['name'];
        $address->country_code = $attributes['mobile_code'];
        $address->phone = $user->phone;
        $address->city = $attributes['city'];
        $address->state = State::find($attributes['state_id'])->name;
        $address->pin = $attributes['pin'];
        $address->address = $attributes['address'];
        $address->save();

        DB::commit();

        return $student;
    }

    public function updatePersonalDetails($student, $attributes) {

        DB::beginTransaction();
        $user = User::findOrFail($student->user_id);
        $user->name = $attributes['name'];
        $user->save();

        $student = Student::find($student->id);
        $student->name = $attributes['name'];
        $student->age = $attributes['age'];     
        // $student->email =$user->email;        
        // $student->country_code = $user->country_code;
        // $student->phone = $user->phone;
        
        $student->save();

        $address = Address::where('user_id',$student->user_id)->first();

        if(!$address){
            $address = new Address();
        }
        $address->user_id = $user->id;
        $address->name = $attributes['name'];
        $address->save();

        DB::commit();

        return $student;
    }

    public function updateAcademicDetails($student, $attributes)
    {
        DB::beginTransaction();
        $user = User::findOrFail($student->user_id);

        $student = Student::find($student->id);
        $student->course_id = $attributes['course_id'];
        $student->level_id = $attributes['level_id'];
        $student->save();

        DB::commit();

        return $student;
    }

    public function updateStudentAddress($student, $attributes)
    {
        DB::beginTransaction();
        $user = User::findOrFail($student->user_id);

        $student = Student::find($student->id);
        $student->country_id = $attributes['country_id'];
        $student->state_id = $attributes['state_id'];
        $student->address = $attributes['address'];
        $student->city = $attributes['city'];
        $student->pin = $attributes['pin'];
        $student->save();

        $address = Address::where('user_id',$student->user_id)->first();

        if(!$address){
            $address = new Address();
        }
        $address->user_id = $user->id;
        $address->city = $attributes['city'];
        $address->state = State::find($attributes['state_id'])->name;
        $address->pin = $attributes['pin'];
        $address->address = $attributes['address'];
        $address->save();

        DB::commit();

        return $student;
    }

}

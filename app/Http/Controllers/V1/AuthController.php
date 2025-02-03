<?php

namespace App\Http\Controllers\V1;

use App\Mail\OtpVerificationSignUp;
use App\Mail\SignUpMail;
use App\Mail\VerifiedMail;
use App\Models\CampaignRegistration;
use App\Models\Cart;
use App\Models\Course;
use App\Models\ErpStudent;
use App\Models\Level;
use App\Models\Otp;
use App\Models\PushNotification;
use App\Models\Referral;
use App\Models\Student;
use App\Models\TempCampaignPoint;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\JMoney;
use App\Models\JMoneySetting;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Token;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Parser;
use Mockery\Exception;

class AuthController extends Controller
{
    /** @var  StudentService */
    var $studentService;

    /**
     * AuthController constructor.
     * @param StudentService $studentService
     */
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function has_testpress(Request $request){
        $credential = $request->validate([
            'email' => 'required',
        ]);
        try{
            $student=ErpStudent::where('student_email',$credential['email'])->where('has_testpress','Y')->first();
        
            if ($student) {
                return response()->json(['data' => $student,'message'=>"User is mapped with Testpress"],200);
            }else{
                return response()->json(['message'=>"User is not mapped with Testpress"],422);
            }
        }catch(Exception $e){
            return response()->json(['message'=>"Something went wrong"],422);
        }
    }

    public function login_testpress(Request $request)
    {
        $credential = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        try {
            $response = Http::post(env('TESTPRESS_URL').'api/v2.3/auth-token/', [
                'username' => $credential['email'],
                'password' => $credential['password'],
            ]);

            $responseData = $response->json();
            if ($response->successful()) {
                return response()->json(['message'=>"User Authenticated"],200);
            } else {
                return response()->json(['message'=>"These credentials do not match our testpress record"],422);
            }
        } catch (Exception $e) {
            return response()->json(['message'=>"Something went wrong"],500);
        }
    }

    public function sso_testpress(Request $request){

        try{
            $credential = $request->validate([
                'email' => 'required',
            ]);
    
            $epoch = time();
            $username = $credential['email'];
            $qstring = "username=" . $username . "&time=" . $epoch;
            $payload = base64_encode($qstring);
            $secret_key =env('TESTPRESS_SECRET_KEY');
            $hmac_signature = hash_hmac('sha256', $payload, $secret_key);
            
            return response()->json(['data' => env('TESTPRESS_URL_SSO').'?sig='.$hmac_signature.'&sso='.$payload],200);
        }catch(Exception $e){
            return response()->json(['message'=>"Something went wrong"],422);
        }
        
        
    }

    public function login(Request $request) {
        $credential = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

       if($request->mobile){

            $mobileUrl = env('MOBILE_API_URL');
            $response = Http::accept('application/json')
                 ->get($mobileUrl.'user-exists?email='.$credential['email'].'&source=online');

            if($response['data']['response'] == 1){
                throw ValidationException::withMessages(['username' => ['The user already exist.']]);
            }
        }


        $userEmail = User::where('email', $credential['email'])->whereNotIn('role', [5, 6, 7, 11])->first();

        $userPhone = User::where('phone', $credential['email'])->whereNotIn('role', [5, 6, 7, 11])->first();

        if ($userEmail || $userPhone) {
            throw ValidationException::withMessages(['email' => ['These credentials do not match our records.']]);
        }

        if(filter_var($credential['email'], FILTER_VALIDATE_EMAIL)) {
            $credential = ['email' => $credential['email'], 'password' => $credential['password']];
        }

        if(is_numeric($credential['email'])) {
            $credential = ['phone' => $credential['email'], 'password' => $credential['password']];
        }

        if (request('role') == 'associate') {
            $credential['role'] = 7;
        }

        if (! auth()->attempt($credential)) {
            throw ValidationException::withMessages(['email' => ['These credentials do not match our records.']]);
        }

        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        $userId = auth()->user()->id;

        $user = User::findOrFail($userId);
        $user->last_login = Carbon::now();
        $user->save();

        $pushNotification = PushNotification::where('user_id', $userId)
            ->where('device_id', $request->device_id)
            ->where('device_id', '!=', null)
            ->first();

        if(!$pushNotification){
            $pushNotification = new PushNotification();
        }

        $pushNotification->user_id = $userId;
        $pushNotification->device_id = $request->device_id;
        $pushNotification->device_type = $request->device_type;
        $pushNotification->fcm_token = $request->fcm_token;
        $pushNotification->web_or_mobile_login = $request->login_type;
        $pushNotification->save();

        $multipleLogin = 0;
        if($request->login_type == 'web'){
            $loginDetails = PushNotification::where('user_id', $userId)->get();
            if(count($loginDetails)>1){
                $multipleLogin = 1;

                $value = $accessToken;
                /** @var Parser $parser */
                $parser = app()->make(Parser::class);
                $id = $parser->parse($value)->claims()->get('jti');
                /** @var Token[] $tokens */
                $tokens = $request->user()->tokens->where('id', '!=', $id);

                foreach ($tokens as $token){
                    $token->revoke();
                }

                PushNotification::where('id','!=', $pushNotification->id)->where('user_id', $userId)->delete();

            }
        }

        //  $mobileUrl = env('MOBILE_API_URL');

        //  $userLogUpdate = Http::accept('application/json')
        //      ->get($mobileUrl.'update-user-logs?user_id='.$userId);


        if ($request->has('cart_uuid')) {
            $packages = Cart::where('uuid', $request->input('cart_uuid'))->get();

            foreach ($packages as $package) {
                $package->user_id = auth()->user()->id;
                $package->save();
            }
        }


        $campaign_registrations = CampaignRegistration::where('phone', auth()->user()->phone)->first();
       if($campaign_registrations){
//           $campaign_registrations = new CampaignRegistration();
           $campaign_registrations->user_id = auth()->user()->id;
           $campaign_registrations->save();
       }

//        if ($request->filled('campaign_registration_id')) {
//            $tempCampaignPoints = TempCampaignPoint::query()
//                ->where('campaign_registration_id', $request->input('campaign_registration_id'))
//                ->where('is_reward_updated', false)
//                ->where('point', '>', 0)
//                ->get();
//
//            foreach ($tempCampaignPoints as $tempCampaignPoint) {
//                $jMoney = new JMoney();
//                $jMoney->user_id = auth()->user()->id;
//                $jMoney->activity = JMoney::PROMOTIONAL_ACTIVITY;
//                $jMoney->points = $tempCampaignPoint->point;
//                $jMoney->expire_after = Carbon::parse($tempCampaignPoint->expire_at)->diffInDays($tempCampaignPoint->created_at);
//                $jMoney->expire_at = $tempCampaignPoint->expire_at;
//                $jMoney->is_used = 0;
//                $jMoney->save();
//
//                $tempCampaignPoint->is_reward_updated = true;
//                $tempCampaignPoint->save();
//            }
//        }


        return $this->jsonResponse('You are successfully logged in',
            ['user' => auth()->user()->load('student'), 'access_token' => $accessToken, 'multipleLogin' => $multipleLogin]);
    }

    public function register(Request $request) {
        $validated = $request->validate([
            'otp_register_token' => 'required',
            'otp_register_code' => 'required|numeric',
            'name' => 'required',
            'email' => 'required|email',
            'mobile_code' => '',
            'mobile' => 'required',
            'password' => 'required|confirmed',
            'course_id' => 'required',
            'level_id' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'city' => 'required',
            'pin' => 'required',
            'gender' => 'required',
            'attempt_year' => 'required',
            'referral' => 'nullable'
        ]);


        DB::beginTransaction();


        /** @var Student $student */
        $student = $this->studentService->create($validated);

        $user = $student->user;

        $accessToken = $user->createToken('authToken')->accessToken;

        $user_details = [
            'name' => $validated['name'],
            'email' => $validated['email']
        ];

        DB::commit();

        $campaign_registrations = CampaignRegistration::where('phone', $user->phone)->first();
        if($campaign_registrations){
            $campaign_registrations->user_id = $user->id;
            $campaign_registrations->save();
        }

//        if ($request->filled('campaign_registration_id')) {
//            $tempCampaignPoints = TempCampaignPoint::query()
//                ->where('campaign_registration_id', $request->input('campaign_registration_id'))
//                ->where('is_reward_updated', false)
//                ->where('point', '>', 0)
//                ->get();
//
//            foreach ($tempCampaignPoints as $tempCampaignPoint) {
//                $jMoney = new JMoney();
//                $jMoney->user_id = $user->id;
//                $jMoney->activity = JMoney::PROMOTIONAL_ACTIVITY;
//                $jMoney->points = $tempCampaignPoint->point;
//                $jMoney->expire_after = Carbon::parse($tempCampaignPoint->expire_at)->diffInDays($tempCampaignPoint->created_at);
//                $jMoney->expire_at = $tempCampaignPoint->expire_at;
//                $jMoney->is_used = 0;
//                $jMoney->save();
//
//                $tempCampaignPoint->is_reward_updated = true;
//                $tempCampaignPoint->save();
//            }
//        }

        return $this->success('You are successfully registered', ['user' => $user, 'access_token' => $accessToken]);


    }

    public function validateEmail()
    {
        $emailExists = User::query()->where('email', request('email'))->exists();

        if (request()->filled('user_id')) {
            $emailExists = User::query()
                ->where('email', request('email'))
                ->whereNotIn('id', [request()->input('user_id')])
                ->exists();
        }

        if ($emailExists) {
            return 'false';
        }

        return 'true';
    }

    public function validatePhone()
    {
        $phoneExists = User::query()->where('phone', request('mobile'))->exists();

        if (request()->filled('user_id')) {
            $phoneExists = User::query()
                ->where('phone', request('mobile'))
                ->whereNotIn('id', [request()->input('user_id')])
                ->exists();
        }

        if ($phoneExists) {
            return 'false';
        }

        return 'true';
    }

    public function validateLogin()
    {
        $user = User::where('email', request()->get('username'))
            ->orWhere('phone', request()->get('username'))
            ->where('role', 5)
            ->first();

        $userExist = false;

        if ($user) {
            $userExist = Hash::check(request()->get('password'), $user->password);
        }

        $secondaryLogin = Http::withHeaders([
            'Accept' => 'application/json'
        ])->post(env('EDUGULP_URL') . 'api/validate-login',
            [
                'username' => request()->get('username'),
                'password' => request()->get('password')
            ]
        );

        $pri = $userExist;
        $sec = $secondaryLogin != "" && $secondaryLogin->status() == 200;

//        $response = 'false';
//
//        if ($pri && $sec) {
//            $response = 'true';
//        }

        return ['pri' => $pri, 'sec' => $sec];
    }

    public function markAsVerified($token = null)
    {
        /** @var User $user */
        $user = User::query()
            ->where('verification_token', $token)
            ->where('is_verified', false)
            ->first();

        if (! $user) {
            return 'false';
        }

        $password = Str::random(8);

        $user->password = Hash::make($password);
        $user->is_verified = true;
        $user->save();

        $user['password'] = $password;

        try {
            Mail::send(new VerifiedMail($user));
        } catch (Exception $exception) {
//            info ($exception->getTraceAsString());

            return 'false';
        }

        return 'true';
    }

    public function mobileLogin(Request $request)
    {
       // info('inside mobile login');
        $userId = $request->user_id;
        $user = User::find($userId);
        $user->last_login = Carbon::now();
        $user->save();

        // info('userId ='.$userId);
        // info('deviceId ='.$request->device_id);
        // info('deviceType ='.$request->device_type);
        // info('fcmToken ='.$request->fcm_token);

        Auth::login($user);

        $pushNotification = PushNotification::where('user_id', $userId)
            ->where('device_id', $request->device_id)
            ->first();

        if(!$pushNotification){
            $pushNotification = new PushNotification();
        }

        $pushNotification->user_id = $userId;
        $pushNotification->device_id = $request->device_id;
        $pushNotification->device_type = $request->device_type;
        $pushNotification->fcm_token = $request->fcm_token;
        $pushNotification->save();

        if($user){
            $accessToken = $user->createToken('authToken')->accessToken;
        }
        else{
            $accessToken = null;
        }

        $multipleLogin = 0;
            $loginDetails = PushNotification::where('user_id', $userId)->get();
            if(count($loginDetails)>1){
                $multipleLogin = 1;
            }

        // $mobileUrl = env('MOBILE_API_URL');

        // $userLogUpdate = Http::accept('application/json')
        //     ->get($mobileUrl.'update-user-logs?user_id='.$userId.'&source=online');

        return $this->jsonResponse('Access Token', ['token' => $accessToken, 'multiple_login' => $multipleLogin]);
    }

    public function removeToken(Request $request)
    {
        //info('inside removeToken');
       // info(Auth::id());
      //  info('device_id='.$request->device_token);
        $value = $request->bearerToken();
        /** @var Parser $parser */
        $parser = app()->make(Parser::class);
        $id = $parser->parse($value)->claims()->get('jti');
        /** @var Token[] $tokens */
        $tokens = $request->user()->tokens->where('id', '!=', $id);
        foreach ($tokens as $token){
            $token->revoke();
        }

        $pushNotificationToken = PushNotification::where('user_id', Auth::id())
            ->where('device_id', '!=', $request->device_token)
            ->where('device_id', '!=', null)
            ->pluck('fcm_token');

//        info($pushNotificationToken);
//        $pushNotificationToken = ['e1u68HmdTm-Ct4OejAS5lr:APA91bHv9EFqqhvn7erpZaHZtVD6g7ciVZrRDFOCC0BR7B4EejNYsrPdfOBssswqFQoMQsSbzeUWE4UwvMBygqhSFNYm4NncqcQvtlt7oQ0t6xD2-zVxJT9BrNXypTaPGg995oi6FBY1'];

        if(count($pushNotificationToken)>0){
            $attributes['title'] = 'Session expired';
            $attributes['message'] = 'Your account is logged in other device';

            try {
                \Illuminate\Support\Facades\Notification::route('fcm', $pushNotificationToken)
                    ->notify(new \App\Notifications\PushNotification($attributes));
            } catch (\Exception $exception) {
                info($exception->getMessage());
            }
        }

        $pushNotifications = PushNotification::where('user_id', Auth::id())
        ->where('device_id', '!=', $request->device_token)
        ->get();
        //  info('notifications');
        // info($pushNotifications);

        foreach ($pushNotifications as $pushNotification) {
            $pushNotification->delete();
        }
    }

    public function signup_otp_verify(Request $request) {
        $validated = $request->validate([
            'otp_register_token' => 'required',
            'otp_register_code' => 'required|numeric'
        ]);
        $returnval=Otp::verify($request->otp_register_token, $request->otp_register_code, 'signup');
        if($returnval==1){
            return $this->success('success', 1);
        }
        else{
            return $this->error('error', 0);
        }
    }

}

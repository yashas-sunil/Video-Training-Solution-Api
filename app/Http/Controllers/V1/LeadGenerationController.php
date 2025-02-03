<?php

namespace App\Http\Controllers\V1;

use Validator;
use Carbon\Carbon;
use App\Models\Note;
use App\Models\User;
use App\Models\SendSms;
use App\Models\Setting;
use App\Models\Student;

// use Illuminate\Support\Facades\Request;
use App\Models\UserTemp;
use App\Models\SendEmail;
use App\Models\JMoneySetting;
use App\Models\JMoney;

use App\Models\DaysMaster;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TimeTableDisplay;
use App\Models\BranchErpOnlineMap;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Mail\GetLeadMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Services\ForgotPasswordService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Mail\JMoneyMail;
use App\Mail\ThankYouMail;
use App\Models\Notification;
use App\Models\UserNotification;

// use Response;

class LeadGenerationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     * 
     */
    /** @var ForgotPasswordService */
    var $forgotPasswordService;

    /**
     * ForgotPasswordController constructor.
     * @param ForgotPasswordService $forgotPasswordService
     */
    public function __construct(ForgotPasswordService $forgotPasswordService)
    {
        $this->forgotPasswordService = $forgotPasswordService;
        
        $this->smsUsername = env('SMSUSERNAME');
        $this->smsApiKey = env('SMSAPIKEY');
        $this->smsApiRequest = env('SMSAPIREQUEST');
        $this->smsSender = env('SMSSENDER');
        // Route details
        $this->apiRoute = env('SMSAPIROUTE'); // Route Name (Promotional, Transactional, DND, Scrub)
        $this->templateidSms = env('TEMPLATEIDSMSOTP');
        $this->templateidSmsThankyou = env('TEMPLATEIDSMSTHANKYOU');
        $this->logo = env('WEB_URL') . '/assets/images/logo.png';
        $this->webLink = env('WEB_URL');
        $this->url = env('WEB_URL');
        $this->power = env('PWD_ENCRYPT_VAL');
    }
    
    public function getLead(Request $request)
    {   
        // dd(1);
        $first_name = $request->first_name;
        $last_name =  $request->last_name;
        $name = $first_name." ".$last_name;
        $email =  $request->email;
        $phone =  $request->phone;
        $logo = $this->logo;
        $webLink = $this->webLink;
        $url = $this->url;
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|string',
            'phone' => 'required|numeric|digits:10',  
        ])->validate();

        // if($validator->fails()){
        //     return response()->json(['error' => $validator->errors(),422]);
        // }else{
        $uservalidcheck= User::where('email',$request->email)->orWhere('phone',$request->phone)->where('is_verified',1)->first();

        // if(!empty($uservalidcheck->id)){
        //     throw ValidationException::withMessages(['message' => ['Student with this phone number/email User already exist']]);
        //     $result["data"] = '';
        //     $result["value"] = 422;
        //     return response()->json($result);
        if(!empty($uservalidcheck->id)){
            if($uservalidcheck->phone == $request->phone && $uservalidcheck->email == $request->email){
                throw ValidationException::withMessages(['message' => ['Student with this phone number and email User already exist']]);
                $result["data"] = '';
                $result["value"] = 422;
                return response()->json($result);
           
            }else if($uservalidcheck->phone == $request->phone){
                throw ValidationException::withMessages(['message' => ['Student with this phone number User already exist']]);
                $result["data"] = '';
                $result["value"] = 422;
                return response()->json($result);
            }else if($uservalidcheck->email == $request->email){
                throw ValidationException::withMessages(['message' => ['Student with this email User already exist']]);
                $result["data"] = '';
                $result["value"] = 422;
                return response()->json($result); 
            }
        }else {
            $mobile_otp =  rand(1000, 9999);
            $mail_otp = rand(100000, 999999);
            // $mobile_otp = rand(100000, 999999);

            $sms_content = "Dear Student, Your OTP is: ".$mobile_otp.". Please enter it to confirm your Mobile Number Thanks J K Shah Education pvt Limited.";
        
            $data = new UserTemp;
            $data->name = ucwords(strtolower($name));
            $data->email = $email;
            $data->phone = $phone;
            $data->role = 5;
            $data->is_verified = 0;
            //$data->mobile_otp = base64_encode($mobile_otp);
            //$data->mail_otp = base64_encode($mail_otp);
            $x = $this->power;
            $data->mobile_otp = pow($mobile_otp,$x);
            $data->mail_otp = pow($mail_otp,$x);
            $data->is_verify_email = 0;
            $data->is_verify_phone = 0;
            $data->is_imported = 3;
            $data->lead_source = base64_encode(serialize($_SERVER));
            $data-> save();
            $user_id = $data->id;

            $subject = 'Verify Email';

            $mail_content = "<p>Dear ".$name." </p><br>";
            $mail_content .="<p>Please Enter this code to verify your email</p>";
            $mail_content .="<p><b>".$mail_otp."</b></p>";
            
            
            $data1 = new SendEmail;
            $data1->email = $email;
            $data1->content = strip_tags($mail_content);
            $data1->category = 'MOBILE_APP_REGISTRATION';
            $data1->status = 'N';
            $data1-> save();
            try{
                // \Mail::send('emails.email',["mail_otp"=>$mail_otp,"email"=>$email,"name"=>$name,"subject"=>$subject, 'mail_content' =>$mail_content, 'logo' =>$logo, 'webLink' =>$webLink, 'url' =>$url ], function ($message) use ($mail_otp,$name,$email,$subject, $mail_content, $logo, $webLink, $url) {
                //     $message->to($email)->subject($subject);
                //     $message->from(env('MAIL_USERNAME'),env('MAIL_FROM_NAME'));
                // });

                $attributes = [
                    'mail_otp' => $mail_otp,
                    'email' =>$email,
                    'name' =>$name,
                    'subject' => $subject,
                    'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                    'web_url' => env('WEB_URL')
                ];
                Mail::send(new GetLeadMail($attributes));
                $data1->status = 'Y';
                $data1->save(); 
            } catch (\Exception $exception) {
                
            }

            // $save_phone_no = Otp::save_otp($phone, $random);

            
            $username =$this->smsUsername;
            $apiKey = $this->smsApiKey;
            $apiRequest =  $this->smsApiRequest;
            $sender = $this->smsSender;
            // Route details
            $apiRoute = $this->apiRoute; // Route Name (Promotional, Transactional, DND, Scrub)
            $templateid = $this->templateidSms;
            $number = $phone; // Multiple numbers separated by comma

            $data = 'username=' . $username . '&apikey=' . $apiKey . '&apirequest=' . $apiRequest . '&route=' . $apiRoute . '&mobile=' . $number . '&sender=' . $sender . "&TemplateID=" . $templateid . "&Values=" . $mobile_otp;

            
            // Send the GET request with cURL
            $url = 'https://k3digitalmedia.co.in/websms/api/http/index.php?' . $data;
            $url = preg_replace("/ /", "%20", $url);

            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );

            $response = file_get_contents($url, false, stream_context_create($arrContextOptions));
            $jsondescode = json_decode($response);

            

            if ($jsondescode->status != 'success' && !empty($error_msg1)) {
                return 0;
            } else {

                $data1 = new SendSms;
                $data1->mobile_no = $phone;
                $data1->sms = $sms_content;
                $data1->category = 'MOBILE_APP_REGISTRATION';
                $data1->status = 'Y';
                $data1-> save();

                $result["message"] = "Please verify your email and mobile number";
                $result["data"] = 1;
                $result["value"] = 200;
                // $result['user_id'] = $user_id;
                // $result['mobile_otp'] = $mobile_otp;
                // $result['email_otp'] = $mail_otp;

                return response()->json($result);
            }
        }
        
    }

    public function getVerifiedLead(Request $request)
    {
        // dd(1);
        //$user_id = $request->user_id;
        $mail_otp = '';
        $mobile_otp = '';
        $mail_otp = $request->mail_otp;
        $mobile_otp = $request->mobile_otp;
        $logo = $this->logo;
        $webLink = $this->webLink;
        $url = $this->url;
        //$check_mail = base64_decode($mail_otp);
        //$check_mobile = base64_decode($mobile_otp); 
        $x = $this->power;
        $check_mobile = pow($mobile_otp,$x);
        $check_mail = pow($mail_otp,$x);
        // Hash::check($request->newPasswordAtLogin, $hashedPassword)    

        $validator = Validator::make($request->all(), [
            'mail_otp' => 'required',
            'mobile_otp' => 'required'
        ])->validate();

        $matchThese = ['mail_otp' =>  $check_mail, 'mobile_otp' => $check_mobile, 'is_verify_email' => 0, 'is_verify_phone' => 0];
     
        $user_data = UserTemp::where($matchThese)
                        ->get()
                        ->toarray();
        // dd($user_data);

        $message1 = Setting::where('key', 'thank_you_msg')->get()->toarray();
        $get_msg_value = $message1[0]['value'];
        $message_email = Setting::where('key', 'thank_you_email')->get()->toarray();
        $get_msg_email = $message_email[0]['value'];
        // dd($get_msg_value);
        // $name = $user_data[0]['name'];
        // $mail_content = '<p>Dear '.$name.' </p><br>';
        // $mail_content .="<p>".$get_msg_email."</p>";
        // $email = $user_data[0]['email'];
        // $phone = $user_data[0]['phone'];
        // $subject = 'Thank you for registering';
        
        if(count($user_data)==1){

            $name = $user_data[0]['name'];
            $mail_content = '<p>Dear '.$name.' </p><br>';
            $mail_content .="<p>".$get_msg_email."</p>";
            $email = $user_data[0]['email'];
            $phone = $user_data[0]['phone'];
            $subject = 'Thank you for registering';
            
            // dd($name);
          $affected = UserTemp::where('id', $user_data[0]['id'])->update(['is_verified' => 1,'is_verify_email' => 1, 'is_verify_phone'=> 1, 'mail_otp' => 0,'mobile_otp' => 0]);
          
            $data1 = new User;
            $data1->name = $user_data[0]['name'];
            $data1->email = $user_data[0]['email'];
            $data1->phone = $user_data[0]['phone'];
            $data1->role = 5;
            $data1->is_verified = 1;
            $data1->mobile_otp = 0;
            $data1->mail_otp = 0;
            $data1->is_verify_email = 1;
            $data1->is_verify_phone = 1;
            $data1->is_imported = 3;
            $data1-> save();
            $new_user = $data1->id;

            $data2 = new Student;
            $data2->user_id = $new_user;
            $data2->name = $user_data[0]['name'];
            $data2->email = $user_data[0]['email'];
            $data2->country_code = '+91';
            $data2->phone = $user_data[0]['phone'];
            $data2-> save();

            // $jMoney = new JMoney();
            // $jMoney->user_id = $new_user;;
            // $jMoney->activity = JMoney::SIGN_UP;
            // $jMoney->points = JMoneySetting::first()->sign_up_point;
            // $jMoney->expire_after = JMoneySetting::first()->sign_up_point_expiry;
            // $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
            // $jMoney->save();
            // $new_student = $data2->id;

            

            $j_points = JMoneySetting::first()->sign_up_point;
            if($j_points > 0){

                //Insert to jmoney table

                $jMoney = new JMoney();
                $jMoney->user_id = $new_user;
                $jMoney->activity = JMoney::SIGN_UP;
                $jMoney->points = JMoneySetting::first()->sign_up_point;
                $jMoney->expire_after = JMoneySetting::first()->sign_up_point_expiry;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->save();
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
                $user_notification->user_id = $new_user;
                $user_notification->is_read = 0;
                $user_notification->save();
            }

            $data1 = new SendEmail;
            $data1->email = $email;
            $data1->content = strip_tags($mail_content);
            $data1->category = 'MOBILE_APP_REGISTRATION';
            $data1->status = 'N';
            $data1-> save();
             
            try{
                // \Mail::send('emails.thankyou_email',["email"=>$email,"name"=>$name,"subject"=>$subject, 'mail_content' =>$mail_content, 'logo' =>$logo, 'webLink' =>$webLink, 'url' =>$url, 'get_msg_email' =>$get_msg_email ], function ($message) use ($mail_otp,$name,$email,$subject, $mail_content,$logo, $webLink, $url, $get_msg_email) {
                //     $message->to($email)->subject($subject);
                //     $message->from(env('MAIL_USERNAME'),env('MAIL_FROM_NAME'));
                // });
                $attributes = [
                    'mail_otp' => $mail_otp,
                    'email' =>$email,
                    'name' =>$name,
                    'subject' => $subject,
                    'get_msg_email'=>$get_msg_email,
                    'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                    'web_url' => env('WEB_URL')
                ];
    
    
    
                Mail::send(new ThankYouMail($attributes));

                $data1->status = 'Y';
                $data1->save(); 
            } catch (\Exception $exception) {
                
            }

            $username =$this->smsUsername;
            $apiKey = $this->smsApiKey;
            $apiRequest =  $this->smsApiRequest;
            $sender = $this->smsSender;
            // Route details
            $apiRoute = $this->apiRoute; // Route Name (Promotional, Transactional, DND, Scrub)
            $templateid = $this->templateidSmsThankyou;
            $number = $phone; // Multiple numbers separated by comma

            $data = 'username=' . $username . '&apikey=' . $apiKey . '&apirequest=' . $apiRequest . '&route=' . $apiRoute . '&mobile=' . $number . '&sender=' . $sender . "&TemplateID=" . $templateid . "&Values=";
            // Send the GET request with cURL
            $url = 'https://k3digitalmedia.co.in/websms/api/http/index.php?' . $data;
            $url = preg_replace("/ /", "%20", $url);

            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            $response = file_get_contents($url, false, stream_context_create($arrContextOptions));
            $jsondescode = json_decode($response);
           // echo '<pre>';
//print_r($jsondescode);exit;
            $result["message"] = $get_msg_value;
            $result["data"] = 1;
            $result["value"] = 200;
        }else{

            $user_data = UserTemp::where('mail_otp',$check_mail)->orWhere('mobile_otp',$check_mobile)->where('is_verify_email',0)->where('is_verify_phone',0)
                        ->first();
            if(!empty($user_data->id)){
                if($user_data->mobile_otp != $check_mobile){
                    throw ValidationException::withMessages(['message' => ['Invalid Mobile Otp']]);
                    $result["data"] = '';
                    $result["value"] = 422;
                    return response()->json($result);
                }else if($user_data->mail_otp != $check_mail){
                    throw ValidationException::withMessages(['message' => ['Invalid Email Otp']]);
                    $result["data"] = '';
                    $result["value"] = 422;
                    return response()->json($result); 
                }
            }else{
                throw ValidationException::withMessages(['message' => ['Invalid Otps.']]);
                $result["data"] = '';
                $result["value"] = 422;
            }

            // throw ValidationException::withMessages(['message' => ['Invalid Otp.']]);
            // // $result["message"] = "Invalid Otps";
            // $result["data"] = '';
            // $result["value"] = 422;
        }
        return response()->json($result);
    }
}
<?php

namespace App\Http\Controllers\V1;

use Validator;
use Carbon\Carbon;
use App\Models\Note;
use App\Models\User;
use App\Models\SendEmail;
use App\Models\SendSms;
use App\Models\DaysMaster;

// use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\TimeTableDisplay;
use App\Models\BranchErpOnlineMap;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Mail\GetLeadMail;
use App\Mail\GetVerifiedLeadMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Services\ForgotPasswordService;
use Illuminate\Database\Eloquent\Model;

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
    }

    public function getLead(Request $request)
    {   
        // dd(1);
        $first_name = $request->first_name;
        $last_name =  $request->last_name;
        $name = $first_name." ".$last_name;

        // dd($name);
        $email =  $request->email;
        $phone =  $request->phone;
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|string|unique:users',
            'phone' => 'required|numeric|unique:users'
        ]);
        if($validator->fails()){
        //if(1==2){
            return response()->json(['error' => $validator->errors()]);
        }else{
            
            $mobile_otp =  rand(1000, 9999);
            $mail_otp = rand(100000, 999999);
            // $mobile_otp = rand(100000, 999999);
        
            $data = new User;
            $data->name = ucwords(strtolower($name));
            $data->email = $email;
            $data->phone = $phone;
            $data->role = 5;
            $data->is_verified = 0;
            $data->mobile_otp = $mobile_otp;
            $data->mail_otp = $mail_otp;
            $data->is_verify_email = 0;
            $data->is_verify_phone = 0;
            $data->is_imported = 3;
            $data-> save();
            $user_id = $data->id;

            $subject = 'Verify Email';

            $mail_content = "<p>Dear ".$name." </p><br>";
            $mail_content .="<p>Please Enter this code to verify your email</p>";
            $mail_content .="<p><b>".$mail_otp."</b></p>";

            // \Mail::send('emails.email',["mail_otp"=>$mail_otp,"email"=>$email,"name"=>$name,"subject"=>$subject, 'mail_content' =>$mail_content], function ($message) use ($mail_otp,$name,$email,$subject, $mail_content) {
            //     $message->to($email)->subject($subject);
            //     $message->from('noreply@datavoice.co.in','Datavoice');
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

            
            $data1 = new SendEmail;
            $data1->email = $email;
            $data1->content = strip_tags($mail_content);
            $data1->category = 'MOBILE_APP_REGISTRATION';
            $data1->status = 'Y';
            $data1-> save();


           
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

            
            // dd($data);
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
                $data1->sms = strip_tags($mail_content);
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

    public function verifyEmail($token)
    {
        $verifyUser = NewLeads::where('mail_otp', $mail_otp)->first();

        if(!empty($verifyUser)) {
            $data = NewLeads::find($verifyUser);
            $data->is_verify_email = 1;
            $data-> save();

            $result["msg"] = "Email verified successfully.";
            $result["status"] = 1;
            $result["value"] = 200;
            return response()->json($result);
        }else{
            $result["msg"] = "Something went wrong.";
            $result["status"] = 1;
            $result["value"] = 200;
            return response()->json($result);
        }
    }

    // public function verifyEmail($token)
    // {
    //     $verifyUser = NewLeads::where('mail_otp', $mail_otp)->first();

    //     if(!empty($verifyUser)) {
    //         $data = NewLeads::find($verifyUser);
    //         $data->is_verify_email = 1;
    //         $data-> save();

    //         $result["msg"] = "Email verified successfully.";
    //         $result["status"] = 1;
    //         $result["value"] = 200;
    //         return response()->json($result);
    //     }else{
    //         $result["msg"] = "Something went wrong.";
    //         $result["status"] = 1;
    //         $result["value"] = 200;
    //         return response()->json($result);
    //     }
    // }
    public function getVerifiedLead(Request $request)
    {
        //$user_id = $request->user_id;
        $mail_otp = '';
        $mobile_otp = '';
        $mail_otp = $request->mail_otp;
        $mobile_otp = $request->mobile_otp;

    
        $matchThese = ['mail_otp' =>  $mail_otp, 'mobile_otp' => $mobile_otp, 'is_verify_email' => 0, 'is_verify_phone' => 0];
     
        $user_data = User::where($matchThese)->get()->toarray();
       
        if(count($user_data)==1){
         
           //dd($user_data);
           $name = $user_data[0]['name'];
           $mail_content = '<p>Dear '.$name.' </p><br>';
           $mail_content .="<p>Thank you for registering</p>";
           $email = $user_data[0]['email'];
           $phone = $user_data[0]['phone'];
          
          // echo $mail_content;exit;
            $affected = User::where('id', $user_data[0]['id'])->update(['is_verified' => 1,'is_verify_email' => 1,'mail_otp' => 0,'mobile_otp' => 0]);
            $subject = 'Thank you for registering';

            
            
            //$mail_content .="<p><b>".$mail_otp."</b></p>";

            // \Mail::send('emails.email',["email"=>$email,"name"=>$name,"subject"=>$subject, 'mail_content' =>$mail_content], function ($message) use ($mail_otp,$name,$email,$subject, $mail_content) {
            //     $message->to($email)->subject($subject);
            //     $message->from('noreply@datavoice.co.in','Datavoice');
            // });

            $attributes = [
                'mail_otp' => $mail_otp,
                'email' =>$email,
                'name' =>$name,
                'subject' => $subject,
                'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                'web_url' => env('WEB_URL')
            ];
            Mail::send(new GetVerifiedLeadMail($attributes));

            $username =$this->smsUsername;
            $apiKey = $this->smsApiKey;
            $apiRequest =  $this->smsApiRequest;
            $sender = $this->smsSender;
            // Route details
            $apiRoute = $this->apiRoute; // Route Name (Promotional, Transactional, DND, Scrub)
            $templateid = $this->templateidSmsThankyou;
            $number = $phone; // Multiple numbers separated by comma

            $data = 'username=' . $username . '&apikey=' . $apiKey . '&apirequest=' . $apiRequest . '&route=' . $apiRoute . '&mobile=' . $number . '&sender=' . $sender . "&TemplateID=" . $templateid . "&Values=Thank you";
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
            $result["msg"] = "Email and mobile no verified successfully.";
            $result["status"] = 1;
            $result["value"] = 200;
        }else{
            $result["msg"] = "Invalid Otps";
            $result["status"] = 1;
            $result["value"] = 200;
        }
        return response()->json($result);
    }
}


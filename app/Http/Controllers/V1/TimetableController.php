<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\Note;
use App\Models\User;
use App\Models\DaysMaster;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

// use Illuminate\Support\Facades\Request;
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

// use Response;

class TimetableController extends Controller
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

    public function getEventList(Request $request)
    {
        //  dd(1);
        $date =  $request->input('date');
        $center_id =  $request->input('center_id');
        $student_id = $request->input('student_id');
        $startDate = date('Y-m-d', strtotime(Carbon::today()));
        $endDate = date('Y-m-d', strtotime(Carbon::today()->addDays(6)));
        
        $from_dateq = $startDate." 00:00:00";
        $to_dateq = $endDate." 23:59:59";
        $time_table1 = TimeTableDisplay::select('time_table_display.id', 'days_master.date as date', 'branch.branch_name', 'batch.name as batch_name', 'room_master.name as room_name', 'room_master.capacity as room_capacity', 'courses.name as course_name', 'professors.name as professor_name', 'levels.name as level_name', 'notes.note as note', \DB::raw('(CASE WHEN time_table_display.is_signed_in = 1 THEN "YES" 
        WHEN time_table_display.is_signed_in = 0 THEN "NO" 
        ELSE "" END) AS is_signed_in'), \DB::raw('DATE_FORMAT(batch_time_slots.from_time, "%h:%i %p") as batch_from_time'), \DB::raw('DATE_FORMAT(batch_time_slots.to_time, "%h:%i %p") as batch_to_time'))
        ->leftjoin('branch_erp_online_map', 'branch_erp_online_map.id', '=', 'time_table_display.eom_id')
        ->join('days_master', 'days_master.id', '=', 'time_table_display.dm_id')
        ->leftJoin('branch', 'branch.id', '=', 'branch_erp_online_map.branch_id')
        ->leftJoin('batch', 'batch.id', '=', 'branch_erp_online_map.batch_id')
        ->leftJoin('room_master', 'room_master.id', '=', 'time_table_display.cr_id')
        ->leftJoin('batch_time_slots', 'batch_time_slots.id', '=', 'time_table_display.bts_id')
        ->leftJoin('levels', 'levels.id', '=', 'time_table_display.level_id')
        ->leftJoin('professors', 'professors.id', '=', 'time_table_display.professor_id')
        ->leftJoin('courses', 'courses.id', '=', 'time_table_display.course_id')
        ->leftJoin('notes', 'notes.id', '=', 'time_table_display.notes_id')
        // ->whereBetween('days_master.date', [$startDate, $endDate])
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('days_master.date', [$startDate, $endDate]);
        })
        ->where('branch.id', '=', $center_id)
        ->whereNotNull('time_table_display.is_signed_in')
        // ->where('days_master.date', '>=', $startDate)
        // ->where('days_master.date', '<=', $endDate)
        ->get();

        $time_table2 =  TimeTableDisplay::select('time_table_display.id', 'days_master.date as date', 'branch.branch_name', 'batch.name as batch_name', 'room_master.name as room_name', 'room_master.capacity as room_capacity', 'courses.name as course_name', 'professors.name as professor_name', 'levels.name as level_name','notes.note as note', \DB::raw('(CASE WHEN time_table_display.is_signed_in = 1 THEN "YES" 
        WHEN time_table_display.is_signed_in = 0 THEN "NO" 
        ELSE "" END) AS is_signed_in'), \DB::raw('DATE_FORMAT(batch_time_slots.from_time, "%h:%i %p") as batch_from_time'), \DB::raw('DATE_FORMAT(batch_time_slots.to_time, "%h:%i %p") as batch_to_time'))
        ->leftjoin('branch_erp_online_map', 'branch_erp_online_map.id', '=', 'time_table_display.eom_id')
        ->join('days_master', 'days_master.id', '=', 'time_table_display.dm_id')
        ->leftJoin('branch', 'branch.id', '=', 'branch_erp_online_map.branch_id')
        ->leftJoin('batch', 'batch.id', '=', 'branch_erp_online_map.batch_id')
        ->leftJoin('room_master', 'room_master.id', '=', 'time_table_display.cr_id')
        ->leftJoin('batch_time_slots', 'batch_time_slots.id', '=', 'time_table_display.bts_id')
        ->leftJoin('levels', 'levels.id', '=', 'time_table_display.level_id')
        ->leftJoin('professors', 'professors.id', '=', 'time_table_display.professor_id')
        ->leftJoin('courses', 'courses.id', '=', 'time_table_display.course_id')
        ->leftJoin('notes', 'notes.id', '=', 'time_table_display.notes_id')
        ->where('days_master.date', '=', $date)
        ->where('branch.id', '=', $center_id)
        ->whereNotNull('time_table_display.is_signed_in')
        ->get();


        //  dd($time_table);

        if(empty($date)) {
             $data['data'] = $time_table1;
             $data["msg"] = "Success.";
             $data["status"] = 1;
             $data["value"] = 200;
             return response()->json(['Timetable'=>$time_table1]);
         }else{
             $data['data'] = $time_table2;
             $data["msg"] = "Error";
             $data["status"] = 1;
             $data["value"] = 200;
             return response()->json(['Timetable'=>$time_table2]);
         }
        //  return $this->jsonResponse('Timetable',$time_table);
        
    }

    public function addNotes(Request $request)
    {
        // dd(1);
        $note =  $request->input('note');

        $validator = Validator::make($request->all(), [
            'note' => 'required',
        ]);

        if($validator->fails()){
            return response()->json("Please Enter Required Field.");
        }

        $data = new Note();
        $data->note = $note;
        $data->save();

        $result["data"] = $data;
        $result["msg"] = "Note Added Successfully.";
        $result["status"] = 1;
        $result["value"] = 200;
        return response()->json($result,$result["value"]);
        // return response()->json(['Timetable'=>$time_table]);
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
            $data1->content = $mail_content;
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

            echo $data; exit;
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

                $data1 = new SendEmail;
                $data1->mobile_no = $phone;
                $data1->sms = $mail_content;
                $data1->category = 'MOBILE_APP_REGISTRATION';
                $data1->status = 'Y';
                $data1-> save();

                $result["msg"] = "Please verify your email and mobile number";
                $result["status"] = 1;
                $result["value"] = 200;
                $result['user_id'] = $user_id;
                $result['mobile_otp'] = $mobile_otp;
                $result['email_otp'] = $mail_otp;

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
            Mail::send(new GetLeadMail($attributes));
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


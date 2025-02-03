<?php

namespace App\Http\Controllers\V1;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\VaibhavScholarRegistration;
use App\Models\VaibhavOtp;
use App\Mail\VaibhavRegMail;
use App\Mail\VaibhavRegMailAdmin;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use App\Models\EmailLog;

class VaibhavRegController extends Controller
{

    public function __construct()
    {
        $this->smsUsername = env('SMSUSERNAME');
        $this->smsApiKey = env('SMSAPIKEY');
        $this->smsApiRequest = env('SMSAPIREQUEST');
        $this->smsSender = env('SMSSENDER');
        // Route details
        $this->apiRoute = env('SMSAPIROUTE'); // Route Name (Promotional, Transactional, DND, Scrub)
        $this->templateidSms = env('TEMPLATEIDSMSOTP');
        $this->templateidSmsThankyou = env('TEMPLATEIDSMSTHANKYOU');
        $this->power = env('PWD_ENCRYPT_VAL');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $phone = $request->student_contact;
        $reg = new VaibhavScholarRegistration();
        $reg->student_name = $request->name;
        $reg->student_contact_no = $request->student_contact;
        $reg->parent_contact_no = $request->parent_contact;
        $reg->email_id = $request->email;
        $reg->aggr_per_tenth = $request->agg_X;
        $reg->aggr_per_eleventh = $request->agg_XI;
        $reg->junior_college = $request->college_name;
        $reg->address = $request->resaddr;
        $reg->city = $request->city;
        $reg->pincode = $request->pincode;
        $reg->junior_college_address = $request->college_addr;
        $reg->income = $request->income;
        $reg->is_verified = 0;
        $reg->status = 1;
        $reg->save();

        $mobile_otp =  rand(1000, 9999);
        $data = new VaibhavOtp();
        $data->student_id = $reg->id;
        $data->mobile = $request->student_contact;
        $x = $this->power;
        $data->otp = pow($mobile_otp,$x);
        $data->save();

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
           // return 0;
           return $this->jsonResponse('Inserted', $reg);
        } else {

            return $this->jsonResponse('Inserted', $reg);
        } 

        

    }

    public function getVerifiedOtp(Request $request)
    {
        $mobile_otp = '';
        $mobile_otp = $request->mobile_otp;
        $student_id = $request->student_id;
        $x = $this->power;
        $check_mobile = pow($mobile_otp,$x);

        $matchThese = ['otp' => $check_mobile,'student_id'=>$student_id, 'is_verified' => 0];

        $user_otp = VaibhavOtp::where($matchThese)->get()->toarray();

        if(count($user_otp)==1){
            $data = VaibhavScholarRegistration::where('id',$request->student_id)->first();
            $affected = VaibhavScholarRegistration::where('id', $request->student_id)->update(['is_verified' => 1]);
            $affected2 = VaibhavOtp::where('student_id',$request->student_id)->where('otp',$check_mobile)->update(['is_verified' => 1]);
            $admin_mail = Setting::where('key', 'admin_email')->first();
            //$admin_mail = Setting::where('key', 'email_bcc')->first();
            $bcc = $special_bcc ='';
            $bcc_ids=$special_bcc_ids= $email_bcc =[];
            $bcc_setting = Setting::where('key', 'email_bcc')->first();
            $bcc = $bcc_setting->value;
            if(!empty($bcc_setting->value)){
            $bcc_ids = explode(",",$bcc);
            }
            $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
            $special_bcc = $special_bcc_settings->value;
            if(!empty($special_bcc) && !empty($bcc_ids)){
                $special_bcc_ids = explode(",",$special_bcc);
                $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
            }else{
                $email_bcc = $bcc_ids;
            }
            
            try {
                $attributes = [
                    'admin_mail' => $admin_mail->value,
                    'email' => $data->email_id,
                    'email_bcc' => $bcc_ids,
                    'email_bcc_admin' => $email_bcc,
                    'student_phone' => $data->student_contact_no,
                    'parent_contact' => $data->parent_contact_no,
                    'name' => $data->student_name,
                    'agg_X' => $data->aggr_per_tenth,
                    'agg_XI' => $data->aggr_per_eleventh,
                    'college' => $data->junior_college,
                    'address' => $data->address,
                    'city' => $data->city,
                    'pincode' => $data->pincode,
                    'college_address' => $data->junior_college_address,
                    'income' => $data->income,
                    'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                    'web_url' => env('WEB_URL')
                ];
                Mail::send(new VaibhavRegMail($attributes));

                $email_log = new EmailLog();
                $email_log->email_to = $data->email_id;
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "JKSHAH ONLINE - Thane Vaibhav Registration";
                $email_log->save();

                Mail::send(new VaibhavRegMailAdmin($attributes));
                
                $email_log = new EmailLog();
                $email_log->email_to = $data->email_id;
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "JKSHAH ONLINE - Thane Vaibhav Registration";
                $email_log->save();
            } catch (\Exception $exception) {
                info($exception->getMessage());
            }

            $result["msg"] = "Mobile verified successfully.";
            $result["status"] = 1;
            $result["value"] = 200;
        }else{
            $result["msg"] = "Invalid Otps";
            $result["status"] = 2;
            $result["value"] = 200;
        }
        return response()->json($result);
    }

    public function validateEmail()
    {
        $emailExists = VaibhavScholarRegistration::query()->where('is_verified','1')->where('email_id', request('email'))->first();

        if (@$emailExists->id) {
            return 'false';
        }

        return 'true';
    }
    public function validatePhone()
    {
        $phoneExists = VaibhavScholarRegistration::query()->where('is_verified','1')->where('student_contact_no', request('student_contact'))->first();

        

        if (@$phoneExists->id) {
            return 'false';
        }

        return 'true';
    }

    
}

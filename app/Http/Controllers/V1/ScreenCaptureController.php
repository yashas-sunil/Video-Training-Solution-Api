<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\TechSupport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Mail\TechSupportMail;
use App\Models\EmailLog;
use App\Models\TechSupportAttachment;
use App\Models\User;
use App\Mail\TechSupportUserMail;

class ScreenCaptureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userId = auth('api')->id();
        // $img = imagegrabscreen();
        // $image_name = $userId.'_'.time().'.png';
        // imagepng($img, 'screenshots/'.$image_name);
        $image_name = $request->image;
        $techsupport = new TechSupport();
        $techsupport->user_id = $userId;
        $techsupport->description = $request->description;
        $techsupport->pageorcourse = $request->pageorcourse;
        $techsupport->save();

        $attachements = array();
        for($i=0;$i<count($request->attached_files);$i++){
            $attachements[] = env('WEB_URL').'screenshots/'.$request->attached_files[$i];
            
            $tech_attachments = new TechSupportAttachment();
            $tech_attachments->query_id = $techsupport->id;
            $tech_attachments->attachment= $request->attached_files[$i];
            $tech_attachments->save();
        }
       
        $admin_mail = Setting::where('key', 'admin_email')->first();
        $bcc ='';
        $bcc_ids=[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        $user = User::select('name','email')->where('id',$userId)->first();
        if(!empty($bcc_setting->value)){
            $bcc_ids = explode(",",$bcc);
        }
        
        try {
            $attributes = [
                'admin_mail' => $admin_mail->value,
                'email_bcc' => $bcc_ids,
                'image' => $attachements,
                'web_url' => env('WEB_URL'),
                'query' => $request->description,
                'user' => $user->name,
                'user_email' => $user->email,
            ];
            
            Mail::send(new TechSupportMail($attributes));

            $email_log = new EmailLog();
            $email_log->email_to = $admin_mail->value;
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "JKSHAH ONLINE - Tech Support";
            $email_log->save();

            Mail::send(new TechSupportUserMail($attributes));

            $email_log = new EmailLog();
            $email_log->email_to = $user->email;
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "JKSHAH ONLINE - Tech Support";
            $email_log->save();


        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
        return $this->jsonResponse('Screenshot Captured', $techsupport);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

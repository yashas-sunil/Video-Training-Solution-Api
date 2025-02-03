<?php

namespace App\Http\Controllers\V1;

use App\Mail\ContactUsMail;
use App\Models\Notification;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;
use App\Models\Setting;

class ContactUsController extends Controller
{
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
       $returnval= Otp::verify($request->otpcontactus_token, $request->otpcontactus_code, 'contactform');
    //    $testnew=new Notification;
    //    $testnew->title='token'.$request->otpcontactus_token;
    //    $testnew->notification_body='code'.$request->otpcontactus_code.'email'.$returnval;
    //    $testnew->save();

       if($returnval==1){
           try {
               Mail::send(new ContactUsMail($request->all()));
                $email_log = new EmailLog();
                $email_log->email_to = env('APP_ENV')=='production'?'helpdesk@jkshahclasses.com':'testing.team@datavoice.co.in';
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "JKSHAH ONLINE - MESSAGE";
                $email_log->save();
           }
           catch (\Exception $exception) {
               info($exception->getMessage(), ['exception' => $exception]);
           }

           return $this->success('success', 1);

       }else{
           return $this->error('error', 0);

       }
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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

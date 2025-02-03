<?php

namespace App\Http\Controllers\V1;

use App\Mail\CanNotFindEnquireAdminMail;
use App\Mail\CanNotFindEnquireStudentMail;
use App\Mail\EmailSupportMail;
use App\Models\EmailLog;
use App\Models\EmailSupport;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CanNotFindEnquireController extends Controller
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(auth('api')->id()){
            $validated = $request->validate([
                'cannotfind_fname' => 'required',
                'cannotfind_lname' => 'required',
                'email' => 'required|email',
                'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'descript' => 'required|max:255',
                'form' => 'required|string'
            ]);
                DB::beginTransaction();
                $emailSupport = new EmailSupport();
                $emailSupport->first_name = $request->cannotfind_fname;
                $emailSupport->last_name = $request->cannotfind_lname;
                $emailSupport->email = $request->email;
                $emailSupport->phone = $request->mobile;
                $emailSupport->query = $request->descript;
                $emailSupport->approved_status = 0;
                $emailSupport->status = 1;
                $emailSupport->save();
                try {
                   
                    $attributes = [
                        'phone'=> $request->mobile,
                        'email' => $request->email,
                        'fname' => $request->cannotfind_fname,
                        'lname' => $request->cannotfind_lname,
                        'query' => $request->descript,
                        'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                        'web_url' => env('WEB_URL')
                    ];
                    $email_log = new EmailLog();
                    $email_log->email_to = $request->email;
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "JKSHAH ONLINE - ENQUIRY";
                    $email_log->save();
                    Mail::send(new CanNotFindEnquireStudentMail($attributes));
                    Mail::send(new CanNotFindEnquireAdminMail($attributes));
                    DB::commit();
                    return $this->success('Email Support created', 'Enquiry has submitted successfully');
                } catch (\Exception $exception) {
                   
                    DB::rollBack();
                    info($exception->getMessage());
                    return $this->error('error', 'Error in support email creation' . $exception->getMessage());
                }
        }else{
            $validated = $request->validate([
                'cannotfind_otp_token' => 'required',
                'cannotfind_otp_code' => 'required|numeric',
                'cannotfind_fname' => 'required',
                'cannotfind_lname' => 'required',
                'email' => 'required|email',
                'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'descript' => 'required|max:255',
                'form' => 'required|string'
            ]);
            $returnval = Otp::verify($request->cannotfind_otp_token, $request->cannotfind_otp_code, $request->form);

            if ($returnval == 1) {
                DB::beginTransaction();
                $emailSupport = new EmailSupport();
                $emailSupport->first_name = $request->cannotfind_fname;
                $emailSupport->last_name = $request->cannotfind_lname;
                $emailSupport->email = $request->email;
                $emailSupport->phone = $request->mobile;
                $emailSupport->query = $request->descript;
                $emailSupport->approved_status = 0;
                $emailSupport->status = 1;
                $emailSupport->save();
                try {
                   
                    $attributes = [
                        'phone'=> $request->mobile,
                        'email' => $request->email,
                        'fname' => $request->cannotfind_fname,
                        'lname' => $request->cannotfind_lname,
                        'query' => $request->descript,
                        'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
                        'web_url' => env('WEB_URL')
                    ];
                    $email_log = new EmailLog();
                    $email_log->email_to = $request->email;
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "JKSHAH ONLINE - ENQUIRY";
                    $email_log->save();
                    Mail::send(new CanNotFindEnquireStudentMail($attributes));
                    Mail::send(new CanNotFindEnquireAdminMail($attributes));
                    DB::commit();
                    return $this->success('Email Support created', 'Enquiry has submitted successfully');
                } catch (\Exception $exception) {
                   
                    DB::rollBack();
                    info($exception->getMessage());
                    return $this->error('error', 'Error in support email creation' . $exception->getMessage());
                }
            } else {
                return $this->error('error', 'Error in support email creation');
            }
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

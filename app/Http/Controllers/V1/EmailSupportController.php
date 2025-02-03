<?php

namespace App\Http\Controllers\V1;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\EmailSupport;
use App\Mail\EmailSupportMail;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;


class EmailSupportController extends Controller
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
        
        $emailSupport = new EmailSupport();
        $emailSupport->first_name = $request->fname;
        $emailSupport->last_name = $request->lname;
        $emailSupport->email = $request->email;
        $emailSupport->phone = $request->mobile;
        $emailSupport->query = $request->description;
        $emailSupport->approved_status = 0;
        $emailSupport->status = 1;
        $emailSupport->save();
        try{
        $attributes = [
            'email' => $request->email,
            'fname' => $request->fname,
            'lname' => $request->lname,
            'query' => $request->description,
            'logo_url' => env('WEB_URL') . '/assets/images/logo.png',
            'web_url' => env('WEB_URL')
        ];
        Mail::send(new EmailSupportMail($attributes));
        $email_log = new EmailLog();
        $email_log->email_to = $request->email;
        $email_log->email_from = env('MAIL_FROM_ADDRESS');
        $email_log->content = "JKSHAH ONLINE - MESSAGE";
        $email_log->save();
        }catch (\Exception $exception) {
            info($exception->getMessage());
        }
        return $this->jsonResponse('Email Support created', $emailSupport);
    }

   

    
}

<?php

namespace App\Http\Controllers\V1;

use App\Mail\UpdateEmailOtp;
use App\Models\Otp;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
class UpdateEmailController extends Controller
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
        DB::beginTransaction();
        $user = User::findOrFail(Auth::id());
        $user->email = $request->email;
        $user->update();

        $student = Student::where('user_id','=',$user->id)->first();
        $student->email = $user->email;
        $student->update();
        DB::commit();

        return $this->jsonResponse('Student email updated');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editEmailOtp(Request $request)
    {
        $otp = Otp::where('action','=','email_update')
            ->where('action_id',Auth::id())
            ->where('verified_at',null)
            ->first();
        $user = User::find(Auth::id());
        if(!$otp){
            $verify_otp = new Otp();
            $verify_otp->action = 'email_update';
            $verify_otp->action_id = Auth::id();
            $verify_otp->mobile = $user->phone;
            $verify_otp->code = mt_rand(1000, 9999);
            $verify_otp->save();
            $attributes['otp'] =  $verify_otp->code;
            $attributes['email'] =  $user->email;
        }
        else{
            $otp->action = 'email_update';
            $otp->action_id = Auth::id();
            $otp->mobile = $user->phone;
            $otp->code = mt_rand(1000, 9999);
            $otp->save();
            $attributes['otp'] =  $otp->code;
        }
        $attributes['email'] =  $request->email;

        try{
            Mail::send(new UpdateEmailOtp($attributes));
        }
        catch (\Exception $exception) {
            info($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function verifyEmailOtp(Request $request)
    {
        $otp = Otp::where('action','=','email_update')
            ->where('action_id',Auth::id())
            ->where('verified_at',null)
            ->first();

        if($request->otp == $otp->code){
            if($otp->isExpired() || $otp->isVerified()) {
                return 0;
            }
            $otp->markAsVerified();
            return 1;
        }
        else{
            return 0;
        }

    }


}

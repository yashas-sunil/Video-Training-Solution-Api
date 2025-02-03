<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentLogsController extends Controller
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
         
         
        
           // $browser=request()->server();
            $student_log=new StudentLog();
            $loged_in_user=$request->user_id ?? auth('api')->id();
            $student_log->user_id= $loged_in_user;
            $student_log->package_id=$request->package_id;
            $student_log->ip_address=$request->ip_address;
            $student_log->video_id=$request->video_id ?? NULL;
            $student_log->log_type=$request->log_type;
            if($request->log_type==2){
                $student_log->login_time=Carbon::now();  
            }
            $student_log->browser_agent	=$request->browser??NULL;
            $student_log->session_token=$request->session_token??NULL;
            $student_log->save();
            if($request->log_type==5){

                $update_log=StudentLog::where('user_id',$loged_in_user)->where('session_token',$request->session_token)->where('log_type',2)->first();
                
                $logIn_time = $update_log->login_time;
                $log_update=StudentLog::where('user_id',$loged_in_user)->where('session_token',$request->session_token)->get();
                foreach($log_update as $log_updat){
                $log_updat->logout_time=Carbon::now(); 
                $log_updat->login_time=$logIn_time;
                $log_updat->save();
                }
                
            }

        return true;
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

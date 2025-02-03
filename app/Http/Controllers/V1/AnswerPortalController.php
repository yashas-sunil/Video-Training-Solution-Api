<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Mail\AnswerMail;
use App\Models\Answer;
use App\Models\AskAQuestion;
use App\Models\Package;
use App\Models\Professor;
use App\Models\User;
use App\Models\Video;
use App\Models\Setting;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class AnswerPortalController extends Controller
{
    public function index(Request $request)
    {
        $question = AskAQuestion::query()
            ->with('answer.user.professor', 'user')
            ->find($request->input('question_id'));
     
        $video = Video::query()->find($question->video_id);
        $professor = Professor::query()->find($video->professor_id);
        $user = User::query()->find($professor->user_id);
        $question['prof'] = $user;
        return $this->jsonResponse('Question', $question ?? null);
    }

    public function store(Request $request)
    {
        $question = AskAQuestion::query()
            ->find($request->input('question_id'));
        $video = Video::query()->find($question->video_id);
        $professor = Professor::query()->find($video->professor_id);
        $user = User::query()->find($question->user_id);
        $package = Package::query()->find($question->package_id);
        // $admin_mail = Setting::where('key', 'admin_email')->first();
        //$admin_mail = Setting::where('key', 'email_bcc')->first();
        $bcc = $special_bcc ='';
        $bcc_ids=$special_bcc_ids= $email_bcc =[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if(!empty($bcc_setting->value)){
        $bcc_ids = explode(",",$bcc);
        }
        $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
        $special_bcc = @$special_bcc_settings->value;
        if(!empty($special_bcc) && !empty($bcc_ids)){
            $special_bcc_ids = explode(",",$special_bcc);
            $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
        }else{
            $email_bcc = $bcc_ids;
        }
        $isAnswerAlreadyExist = true;
        $answer = Answer::query()
            ->where('question_id', $request->input('question_id'))
            ->first();

        if (! $answer) {
            $isAnswerAlreadyExist = false;
            $answer = new Answer();
        }
        $answer->user_id =Auth::id();
        $answer->question_id = $question->id;
        $answer->answer = nl2br($request->input('answer'));
        $answer->save();

        $question->is_answered = 1;
       
        $question->save();

        $user_details = User::find(Auth::id());
        $answer_by ='';
        if($user_details->role == 6){
            $prof = Professor::where('user_id',Auth::id())->first();
            $answer_by = $prof->name;
        }else{
            $answer_by = $user_details->name;
        }
        
        if (! $isAnswerAlreadyExist) {
            try {
                $attributes = [
                    'user_name' => $user->name,
                    'video_name' => $video->title,
                    'package_name' => $package->name,
                    'professor_name' =>  $answer_by,
                    'student_email' => $user->email,
                    'question' => stripslashes($question->question),
                    'answer' => $answer->answer,
                    'logo_url' => env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png'),
                    'web_url' => env('WEB_URL'),
                    'admin_mail' => $email_bcc
                ];

                Mail::send(new AnswerMail($attributes));
                $email_log = new EmailLog();
                $email_log->email_to = $user->email;
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "ANSWER - JKSHAH ONLINE";
                $email_log->save();
            } catch (\Exception $exception) {
//                info($exception->getTraceAsString());
            }
        }

        return $this->jsonResponse('Answer', $answer);
    }
}

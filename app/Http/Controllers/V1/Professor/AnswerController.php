<?php

namespace App\Http\Controllers\V1\Professor;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Mail\AnswerMail;
use App\Models\AskAQuestion;
use App\Models\Package;
use App\Models\Professor;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\EmailLog;
use App\Services\Professor\AnswerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnswerController extends Controller
{
    /** @var AnswerService $answerService */
    var $answerService;

    public function __construct(AnswerService $answerService)
    {
        $this->answerService = $answerService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $answers = Answer::with('user','question.video','question.package.level','question.package.course','question.package.subject','question.package.chapter')->ofProfessor()->orderby('created_at','desc')->get();

        return $this->jsonResponse('Answers', $answers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $response = $this->answerService->create($request->input());
        $question = AskAQuestion::query()
            ->find($request->input('question_id'));
        $question->is_answered = true;
        $question->save();
        $video = Video::query()->find($question->video_id);
        $user = User::query()->find($question->user_id);
        $package = Package::query()->find($question->package_id);
        $professor= Professor::where('user_id',Auth::id())->first();
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
        $special_bcc = $special_bcc_settings->value;
        if(!empty($special_bcc) && !empty($bcc_ids)){
            $special_bcc_ids = explode(",",$special_bcc);
            $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
        }else{
            $email_bcc = $bcc_ids;
        }
       try {
            $attributes = [
                'user_name' => $user->name,
                'video_name' => $video->title,
                'package_name' => $package->name,
                'professor_name' => $professor->name,
                'student_email' => $user->email,
                'question' => stripslashes($question->question),
                'answer' => $response->answer,
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

        return $this->jsonResponse('Answer created', $response);
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
    public function get_question_answer(Request $request){
        $id=$request->input('answer');
        $answer=Answer::where('id',$id)->first();
        return $this->jsonResponse('Answer', $answer);


    }
}

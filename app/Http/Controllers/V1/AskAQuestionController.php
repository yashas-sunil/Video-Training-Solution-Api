<?php

namespace App\Http\Controllers\V1;

use App\Mail\QuestionMail;
use App\Models\Package;
use App\Models\Professor;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AskAQuestion;
use App\Models\Setting;
use App\Services\AskAQuestionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;
use App\Rules\MaxWordsRule;

class AskAQuestionController extends Controller
{
    /** @var AskAQuestionService */
    var $askAQuestionService;

    /**
     * AskAQuestionController constructor.
     * @param AskAQuestionService $askAQuestionService
     */
    public function __construct(AskAQuestionService $askAQuestionService)
    {
        $this->askAQuestionService = $askAQuestionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = AskAQuestion::getAll(null,
            $request->input('type'),
            $request->input('limit'),
            $request->input('video_id'),
            $request->input('package_id'),
            $request->input('subject'),
            $request->input('recent'),
            $request->input('professor'));

        return $this->jsonResponse('Questions', $response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'video_id' => 'required',
            'package_id' => 'required',
            'question' => ['required',new MaxWordsRule],
            'time' => ''
        ]);
        $attributes['question'] = nl2br($attributes['question']);
        //$attributes['question'] = strip_tags($attributes['question']);
        $attributes['user_id'] = Auth::id();

        $question = $this->askAQuestionService->create($attributes);

        $user = User::query()->find($attributes['user_id']);
        $video = Video::query()->find($attributes['video_id']);
        $package = Package::query()->find($attributes['package_id']);
        $professor = Professor::query()->find($video->professor_id);
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
       
       try {
           $attributes = [
               'user_name' => $user->name,
               'video_name' => $video->title,
               'package_name' => $package->name,
               'professor_name' => $professor->name,
               'professor_email' => $professor->email,
               'video_position' => Video::formatDuration($attributes['time']),
               'question' => $attributes['question'],
               'logo_url' => env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png'),
               'web_url' => env('WEB_URL'),
               'answer_portal_url' => env('WEB_URL') . 'answer-portal?question_id=' . $question->id,
               'admin_mail' => $email_bcc
           ];

           Mail::send(new QuestionMail($attributes));
           $email_log = new EmailLog();
           $email_log->email_to = $professor->email;
           $email_log->email_from = env('MAIL_FROM_ADDRESS');
           $email_log->content = "QUESTION - JKSHAH ONLINE";
           $email_log->save();
          
         
       } catch (\Exception $exception) {
//            info($exception->getTraceAsString());
       }

        return $this->jsonResponse('Question successfully created', $question);
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
        $attributes = $request->validate([
            'video_id' => 'required',
            'question' => 'required|max:200',
            'time' => ''
        ]);

        $attributes['user_id'] = Auth::id();

        $response = $this->askAQuestionService->update($id, $attributes);

        return $this->jsonResponse('Question successfully updated', $response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->askAQuestionService->delete($id);

        return $this->jsonResponse('Question deleted');
    }
}

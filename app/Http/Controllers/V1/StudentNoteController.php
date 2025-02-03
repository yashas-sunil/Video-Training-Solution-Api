<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Services\StudentNoteService;
use App\Models\StudentNote;
use Illuminate\Support\Facades\Auth;

class StudentNoteController extends Controller
{
    /** @var  StudentNoteService */
    var $studentNoteService;

    /**
     * StudentNoteController constructor.
     * @param StudentNoteService $service
     */
    public function __construct(StudentNoteService $service)
    {
        $this->studentNoteService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        $studentNotes = StudentNote::getAll();
//
//        return $this->jsonResponse('Student Notes', $studentNotes);

//        $response = StudentNote::getAll(request('video_id'), ['video.course', 'video.level', 'video.subject', 'video.chapter']);

        $response = StudentNote::query();

        $response = $response->ofUser();
        $response = $response->ofVideo(request('video_id'));

        $response->with('video.course', 'video.level', 'video.subject', 'video.chapter');

        if(request('language')){
            $response = $response->whereHas('video', function ($response){
                $response->where('language_id', request('language'));
            });
        }
        if(request('course')){
            $response = $response->whereHas('video', function ($response){
                $response->where('course_id', request('course'));
            });
        }
        if(request('subject')){
            $response = $response->whereHas('video', function ($response){
                $response->where('subject_id', request('subject'));
            });
        }
        if(request('chapter')){
            $response = $response->whereHas('video', function ($response){
                $response->where('chapter_id', request('chapter'));
            });
        }
        if(request('package_id')){
            $response = $response->where('package_id', request('package_id'));
        }

        $response = $response->latest()->get();

        return $this->jsonResponse('Student Notes', $response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'video_id' => 'required',
            'package_id' => 'required',
            'name' => 'required|max:191|regex:/^[\.a-zA-Z0-9,!?\-\ ]*$/',
            'description' => 'max:250|regex:/^[\.a-zA-Z0-9,!?@$%&*()={}\-+*_\n\ ]*$/',
            'time' => ''
        ]);

//        regex:/(^[A-Za-z0-9 ]+$)+/
//        |regex:/^[\.a-zA-Z0-9,!?@$%&*()={}\-+*_\ ]*$/
        $validatedData['user_id'] = Auth::id();

        $studentNote = $this->studentNoteService->create($validatedData);

        return $this->jsonResponse('Student Note successfully created', $studentNote);
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
        $validatedData = $request->validate([
            'video_id' => '',
            'package_id' => '',
            'name' => 'max:191|regex:/^[\.a-zA-Z0-9,!?\-\ ]*$/',
            'description' => 'max:250|regex:/^[\.a-zA-Z0-9,!?@$%&*()={}\-+*_\ ]*$/',
            'time' => ''
        ]);
        $attributes = array(
            'name' => $request->input('name'),
            'description' => $request->input('description')
        );
       
        $validatedData['user_id'] = Auth::id();

        $response = $this->studentNoteService->update($id, $attributes);

        return $this->jsonResponse('Student Note successfully updated.', $response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $studentNote = StudentNote::where('id',$id)->where('user_id',Auth::id())->first();
        if($studentNote){
            $success=1;
        $studentNote = $this->studentNoteService->delete($studentNote);

        return $this->jsonResponse('Student Note successfully deleted', $success);
        }
    }
}

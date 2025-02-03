<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreProfessorNoteRequest;
use App\Models\OrderItem;
use App\Models\PackageVideo;
use App\Models\ProfessorNote;
use Illuminate\Http\Request;
use App\Services\ProfessorNoteService;
use Illuminate\Support\Facades\Auth;

class ProfessorNoteController extends Controller
{
    /** @var ProfessorNoteService */
    var $professorNoteService;

    /**
     * ProfessorNoteController constructor.
     * @var ProfessorNoteService $service
     */
    public function __construct(ProfessorNoteService $service) {
        $this->professorNoteService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        $response = ProfessorNote::getAll(request('video_id'), ['video.course', 'video.level', 'video.subject', 'video.chapter']);

        $purchasedPackageIds = OrderItem::where('user_id', Auth::id())->pluck('package_id')->unique();

        $packagevideoIds = PackageVideo::whereIn('package_id', $purchasedPackageIds)->pluck('video_id');

//        $response = ProfessorNote::getAll(request('video_id'), $packagevideoIds , [
//            'video.course',
//            'video.level',
//            'video.subject',
//            'video.chapter'
//        ]);

        $response = ProfessorNote::query()->whereIn('video_id', $packagevideoIds);
        $response->ofVideo(request('video_id'));

        $response->with('video.course', 'video.level', 'video.subject', 'video.chapter');

        if(request('language')){
            $response = $response->whereHas('video', function ($response){
                $response->where('language_id', request('language'));
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
        $response = $response->get();

        return $this->jsonResponse('Professor Notes', $response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreProfessorNoteRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProfessorNoteRequest $request)
    {
        $professorNote = $this->professorNoteService->create($request->validated());

        return $this->jsonResponse('Professor Note successfully created', $professorNote);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProfessorNote  $professorNote
     * @return \Illuminate\Http\Response
     */
    public function show(ProfessorNote $professorNote)
    {
        return $this->jsonResponse('Professor Note', $professorNote);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProfessorNote  $professorNote
     * @return \Illuminate\Http\Response
     */
    public function update(StoreProfessorNoteRequest $request, ProfessorNote $professorNote)
    {
        $professorNote = $this->professorNoteService->update($professorNote, $request->validated());

        return $this->jsonResponse('Professor Note successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProfessorNote  $professorNote
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProfessorNote $professorNote)
    {
        $professorNote = $this->professorNoteService->delete($professorNote);

        return $this->jsonResponse('Professor Note successfully deleted');
    }
}

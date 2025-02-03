<?php

namespace App\Http\Controllers\V1;

use App\Models\Chapter;
use http\Env\Response;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $chapters = Chapter::getAll(request('course_id'), request('level_id'), request('subject_id'), request('is_purchased'), request('exclude_chapter'), request('with'), request('search'));

        return $this->jsonResponse('Chapters', $chapters);
    }

    /**
     * Display the specified resource.
     *
     * @param integer $id
     * @return mixed
     */
    public function show($id)
    {
        $response = Chapter::where('id', $id)->with('videos.professor')->first();

        return $this->jsonResponse('Chapter', $response);
    }

    public function getChapterBySubjects(Request $request)
    {
        $chapters = Chapter::whereIn('subject_id', request('subjects'))->orderBy('name')->where('is_enabled',true)->get();

        return $this->jsonResponse('Chapters', $chapters);
    }
}

<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Professor;
use App\Models\Video;
use App\Services\CourseService;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    /** @var  CourseService */
    var $courseService;

    /**
     * CourseController constructor.
     * @param CourseService $service
     */
    public function __construct(CourseService $service)
    {
        $this->courseService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $professors = Professor::getAll(request('search'), request('is_published'));

        return $this->jsonResponse('Professors', $professors);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Professor  $professor
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $professor = Professor::findOrFail($id);

        return $this->jsonResponse('Professor', $professor);
    }

    public function getProfessorByChapter()
    {
        $professorIds = Video::whereIn('chapter_id', request('chapters'))->pluck('professor_id');

        $professorIds = collect($professorIds);

        $professorIds = $professorIds->unique();

        $professors = Professor::whereIn('id', $professorIds)->orderBy('name')->get();

        return $this->jsonResponse('Professors', $professors);
    }
    
/* Added By TE 0n May 24,2022 */
public function professorsBYSubject(Request $request){
    
    $professorIds = Video::where('subject_id', request('subject_id'))->pluck('professor_id');

    $professorIds = collect($professorIds);

    $professorIds = $professorIds->unique();

    $professors = Professor::whereIn('id', $professorIds)->orderBy('name')->get();

    return $this->jsonResponse('Professors', $professors);

}

public function professorBYSubject(Request $request){
    
    $professorIds = Video::whereIn('subject_id', request('subject_id'))->pluck('professor_id');

    $professorIds = collect($professorIds);

    $professorIds = $professorIds->unique();

    $professors = Professor::whereIn('id', $professorIds)->orderBy('name')->get();

    return $this->jsonResponse('Professors', $professors);

}




 public function professorsByExperience()
    {
        $professors = Professor::query()->orderBy('name');
        if (request('is_published')) {
            $professors->ofPublished();
        }
        $professors = $professors->get();

        return $this->jsonResponse('Professors', $professors);
    }
}

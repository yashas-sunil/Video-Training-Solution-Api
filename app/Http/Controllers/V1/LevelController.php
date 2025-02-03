<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Level;
use App\Services\CourseService;
use Illuminate\Http\Request;

class LevelController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $levels = Level::getAll(request('course_id'), request('search'));

        return $this->jsonResponse('Levels', $levels);
    }

    public function getLevelById($id)
    {
        $course = Level::with('course')->where('id', $id)->first();

        return $this->jsonResponse('Courses', $course);
    }

    public function getLevelByCourse($id)
    {
        $levels = Level::with('course')->where('course_id', $id)->where('is_enabled',true)->where('display',true)->orderBy('order')->pluck('id')->toArray();

        return $this->jsonResponse('Levels', $levels);
    }

    public function get_Level_By_Course($id)
    {
        $levels = Level::with('course')->where('course_id', $id)->where('is_enabled',true)->where('display',true)->orderBy('order')->get();

        return $this->jsonResponse('Levels', $levels);
    }

    public function levels_by_courses(Request $request){
 
        $levels = Level::where('course_id', request('courses'))->where('is_enabled',true)->where('display',true)->orderBy('order')->get();
        return $this->jsonResponse('Levels', $levels);

    }

}

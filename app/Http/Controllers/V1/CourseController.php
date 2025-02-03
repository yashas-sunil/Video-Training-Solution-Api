<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Services\CourseService;

class CourseController extends Controller
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
        $courses = Course::getAll(request('search'), request('with'));
//        $courses = Course::query();
//        $courses->with('levels');
//        $courses = $courses->get();

        return $this->jsonResponse('Courses', $courses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreCourseRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCourseRequest $request)
    {
//        $course = Course::create($request->validated());
        $course = $this->courseService->create($request->validated());

        return response()->json(['data', $course]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Course  $course
     * @return \Illuminate\Http\Response
     */
    public function show(Course $course)
    {
        return response()->json(['data', $course]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateCourseRequest  $request
     * @param  Course  $course
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course = $this->courseService->update($course, $request->validated());

        return response()->json(['data', $course]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Course  $course
     * @return \Illuminate\Http\Response
     */
    public function getCourseById($id)
    {
        $course = Course::find($id);

        return $this->jsonResponse('Courses', $course);
    }
}

<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Level;
use App\Models\State;
use App\Services\CourseService;

class StateController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $states = State::getAll(request('country_id'), request('search'));

        return $this->jsonResponse('States', $states);
    }


}

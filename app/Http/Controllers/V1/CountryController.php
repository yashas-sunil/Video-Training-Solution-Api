<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Country;
use App\Models\Course;
use App\Models\Level;
use App\Services\CourseService;

class CountryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $countries = Country::getAll(request('search'));

        return $this->jsonResponse('Countries', $countries);
    }


}

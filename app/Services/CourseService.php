<?php

namespace App\Services;

use App\Models\Course;

class CourseService
{

    /**
     * @param $attributes array
     * @return Course
     */
    public function create($attributes) {
        return Course::create($attributes);
    }

    /**
     * @param $attributes array
     * @return Course
     */
    public function update($course, $attributes) {
        return $course->update($attributes);
    }

}
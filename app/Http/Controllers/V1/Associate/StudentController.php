<?php

namespace App\Http\Controllers\V1\Associate;

use App\Http\Controllers\V1\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Services\Associate\StudentService;

class StudentController extends Controller
{
    /** @var StudentService */
    var $studentService;

    /**
     * StudentController constructor.
     * @param StudentService $studentService
     */
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Student::query();

        if (request()->filled('is_verified') && request()->input('is_verified') == 'true') {
            $query->ofVerified();
        }

        $response = $query->ofAssociate()
            ->with('user', 'addresses', 'course', 'level')
            ->latest()
            ->paginate();

        return $this->jsonResponse('Students', $response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $response = $this->studentService->create($request->input());

        return $this->jsonResponse('Student created', $response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $response = Student::with('addresses', 'course', 'level', 'country', 'state')->findOrFail($id);

        return $this->jsonResponse('Student', $response);
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
        $response = $this->studentService->update($request->input(), $id);

        return $this->jsonResponse('Student successfully updated', $response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function sendVerificationMail()
    {
        $response = $this->studentService->sendVerificationMail(request()->input());

        return $this->jsonResponse('Verification mail successfully send', $response);
    }
}

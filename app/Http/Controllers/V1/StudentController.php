<?php

namespace App\Http\Controllers\V1;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Services\StudentService;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /** @var Student */
    var $studentService;


    /**
     * StudentController constructor.
     * @param StudentService $service
     */
    public function __construct(StudentService $service)
    {
        $this->studentService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //info($request->all());
        $student = Student::where('user_id', Auth::id())->first();

        $student = $this->studentService->updatePersonalDetails($student, $request->input());

        return $this->jsonResponse('Student successfully updated', $student);
    }

    public function updateAcademicInformation(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first();

        $student = $this->studentService->updateAcademicDetails($student, $request->input());

        return $this->jsonResponse('Student successfully updated', $student);
    }

    public function updateStudentAddress(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first();

        $student = $this->studentService->updateStudentAddress($student, $request->input());

        return $this->jsonResponse('Student successfully updated', $student);
    }

    public function attemptYearUpdate(Request $request) {

        $student = Student::where('user_id', Auth::id())->first();
        //info("attempt year");
        // info($student);
        // info($request->input('attempt_year'));

        $student->attempt_year = $request->attempt_year;
        $student->save();

        return $this->jsonResponse('Student successfully updated', $student);
    }



    /**
     * Display the specified resource.
     *
     * @param  Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        return $this->jsonResponse('Student', $student);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        return 1;
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

    public function uploadProfileImage(Request $request) {

        $request->validate([
            'image' => 'required|mimes:jpeg,jpg,png'
        ]);

        $image = $request->file('image');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $request->file('image')->storeAs('public/students/images', $filename);
            $student = Student::where('user_id', Auth::id())->first();

            $student->image = $filename;
            $student->save();
        }

        return $this->jsonResponse('Profile Image successfully uploaded');
    }


    public function getOrderDetails(Request  $request){

//        $student_orders = Order::with('orderItems.package')
//            ->whereHas('orderItems.package')
//            ->where('user_id',Auth::id())
//            ->where('payment_status',Order::STATUS_SUCCESS)
//            ->orderBy('id','desc')
//            ->paginate(5);
        $student_orders = Payment::with('order.orderItems.package')
            ->where('user_id',Auth::id())
            ->where('payment_status',Order::STATUS_SUCCESS)
            ->orderBy('id','desc')
            ->paginate(5);

        return $this->jsonResponse('Orders', $student_orders);
    }

    public function getInvoiceDetails(Request  $request){

       $student_orders = Payment::with(['orderItems.package' => function($query) {
           $query->withTrashed();
       }])
            ->where('id', $request->id)
            ->first();

       $student_orders['net_amount_without_tax'] = $student_orders->net_amount - (( mb_strtoupper($student_orders->order->state) == 'MAHARASHTRA') ?  ($student_orders['cgst_amount'] +$student_orders['sgst_amount'] ) : $student_orders['igst_amount']);
       $gst = Setting::where('key','=','gstn')->first();
       $gstn = $gst->value;
       $pendrive_price = Setting::where('key','=','pendrive_price')->first();
       return $this->jsonResponse('Invoice',['student_orders'=>$student_orders,'gstn'=>$gstn,'pendrive_price'=>$pendrive_price] );
    }

}

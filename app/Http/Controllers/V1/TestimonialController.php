<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\Testimonial;
use App\Models\CustomTestimonial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $allTestimonials = Testimonial::getAll();
        // $allCustomTestimonials = CustomTestimonial::getAll();

        // $testimonials = [];

        // foreach($allTestimonials as $testimonial) {
        //     $testimonials[] = [
        //         'name' => $testimonial['student']['name'],
        //         'image' => $testimonial['student']['image'],
        //         'testimonial' => $testimonial['testimonial']
        //     ];
        // }

        // foreach($allCustomTestimonials as $testimonial) {
        //     $testimonials[] = [
        //         'name' => $testimonial['name'],
        //         'image' => $testimonial['image'],
        //         'testimonial' => $testimonial['testimonial']
        //     ];
        // }

        $allTestimonials = Testimonial::getAll();
        $testimonials = [];
        foreach($allTestimonials as $testimonial){
            $testimonials[] = [
                'name' => $testimonial['first_name'].' '.$testimonial['last_name'],
                'image' => $testimonial['image'],
                'testimonial' => $testimonial['testimonial']
            ];

        }

        return $this->jsonResponse('Testimonials', $testimonials);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $role= auth('api')->user()->role;
       $user_id = auth('api')->id();
       $testimonial = new Testimonial();
       $testimonial->first_name = $request->fname;
       $testimonial->last_name = $request->lname;
       $testimonial->email = $request->email;
       $testimonial->phone = $request->mobile;
       $testimonial->publish = 1;
       $testimonial->testimonial = $request->description;
       if($role == 5){
            $testimonial->student_id = $user_id; 
            $testimonial->professor_id = 0;
       }
       if($role == 6){
            $testimonial->student_id = 0; 
            $testimonial->professor_id =  $user_id;
       }
       $testimonial->save();
       
       return $this->jsonResponse('Testimonial created', $testimonial);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
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
}

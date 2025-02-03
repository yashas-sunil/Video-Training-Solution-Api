<?php

namespace App\Http\Controllers\V1;

use App\Models\FreeResource;
use App\Models\FreeResourcePackage;
use Illuminate\Http\Request;

class FreeResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $free_resources = FreeResource::getAll(
            $request->input('type'),
            $request->input('selected_type'),
            $request->input('course'),
            $request->input('level'),
            $request->input('professor'),
            $request->input('search'),
            $request->input('page'),
            $request->input('limit'),
            $request->input('levels'),
            $request->input('professors'),
            $request->input('sort')
        );

        return $this->jsonResponse('Free Resources', $free_resources);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
        //
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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

    /**********Added by TE ************/

    public function linkedPackages(Request $request){

            $linked_package_ids= FreeResourcePackage::where('free_resource_id',request('free_resource_id'))->get();
            return $this->jsonResponse('Packages', $linked_package_ids);


    }

    public function getDemoVideo(Request $request){
        $demo_video=FreeResourcePackage::with('FreeResource')->where('package_id',request('id'))->first();
        return $this->jsonResponse('Demo', $demo_video);
    }

    public function getcoursevideo(Request $request){
       // info(request('updated_at'));
        if(request('updated_date') != NULL){
            $course_video=FreeResourcePackage::with('FreeResource')->where('package_id',request('id'))->where('updated_at','>',request('updated_date'))->first();
        }else{
            $course_video=FreeResourcePackage::with('FreeResource')->where('package_id',request('id'))->first();
        }
        
        return $this->jsonResponse('course_video', $course_video);
    }
    
    /******* TE end *******************/
}

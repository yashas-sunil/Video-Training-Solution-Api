<?php

namespace App\Http\Controllers\V1;

use App\Models\Subject;
use App\Models\PackageType;
use App\Models\LevelType;
use Mockery\Matcher\Type;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = Subject::getAll(request('course'), request('level'), request('limit'), request('with'));

        return $this->jsonResponse('Subjects', $response);
    }

    public function getSubjectByLevels()
    {
        
        if(request('types')){
            $response = Subject::with('level','package_type')->whereIn('level_id', request('levels'))->where('package_type_id', request('types'))->where('is_enabled',true)->orderBy('name')->get();

        }else{
             $response = Subject::with('level','package_type')->whereIn('level_id', request('levels'))->where('is_enabled',true)->orderBy('name')->get();
        }
       

        return $this->jsonResponse('Subjects', $response);
    }

    public function getSubjectByLanguages()
    {
        $response = Subject::whereIn('level_id', request('levels'))->orWhereIn('language_id', request('languages'))->where('is_enabled',true)->orderBy('name')->get();

        return $this->jsonResponse('Subjects', $response);
    }
    public function getTypeByLevels(){
        $response = LevelType::whereIn('level_id', request('levels'))->get();
        
        foreach($response as $row){
            $type_id[]=$row->package_type_id;
        }
        $data=PackageType::whereIn('id', $type_id)->where('is_enabled',true)->orderBy('name')->get();
        return $this->jsonResponse('Types', $data);
       


    }

}

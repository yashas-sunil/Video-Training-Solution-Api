<?php

namespace App\Http\Controllers\V1;

use App\Models\Package;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $packages = Package::search($request->input('keyword'))->get();
        return $packages;
    }

}

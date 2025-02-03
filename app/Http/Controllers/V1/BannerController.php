<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Models\Banner;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $banners = Banner::getAll();

        return $this->jsonResponse('Banners', $banners);
    }
}

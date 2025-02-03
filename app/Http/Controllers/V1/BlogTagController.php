<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\BlogTag;
use Illuminate\Http\Request;

class BlogTagController extends Controller
{
    public function index()
    {
        $query = BlogTag::query();
        $blogTags = $query->get();

//        info($blogTags);

        return $this->jsonResponse('Blog Tags', $blogTags);
    }
}

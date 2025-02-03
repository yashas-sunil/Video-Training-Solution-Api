<?php

namespace App\Http\Controllers\V1;

use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::get();

        return $this->jsonResponse('Languages', $languages);
    }

}

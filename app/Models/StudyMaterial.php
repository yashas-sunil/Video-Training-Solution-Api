<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyMaterial extends BaseModel
{
    use SoftDeletes;

    protected $appends = [
        'file_url',
    ];

    public function getFileUrlAttribute() {
        if ($this->file_name) {
            return env('IMAGE_URL').'/study_materials/'.$this->file_name;
        }
        return null;
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

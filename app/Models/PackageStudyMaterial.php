<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageStudyMaterial extends BaseModel
{
    public function studyMaterial()
    {
        return $this->belongsTo(StudyMaterialV1::class, 'study_material_id');
    }
}

<?php

namespace App\Models;

use App\PackageStudyMaterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class StudyMaterialV1 extends BaseModel
{
    use SoftDeletes;

    protected $table = 'study_materials_v1';

    protected $appends = [
        'file_url',
        'answer_file_url'
    ];
    const STUDY_MATERIALS = 1;
    const STUDY_MATERIALS_TEXT = 'STUDY MATERIAL';
    const STUDY_PLAN = 2;
    const STUDY_PLAN_TEXT = 'STUDY PLAN';
    const TEST_PAPER = 3;
    const TEST_PAPER_TEXT = 'TEST PAPER';

    public function getFileUrlAttribute()
    {
        if ($this->file_name) {
            return env('IMAGE_URL') . '/study_materials/' . $this->file_name;
        }
        return null;
    }

    public function getAnswerFileUrlAttribute()
    {
        if ($this->answer_file_name) {
            return env('IMAGE_URL') . '/study_materials/answers/' . $this->answer_file_name;
        }
        return null;
    }

    public function package_study_material()
    {
        return $this->belongsTo(\App\Models\PackageStudyMaterial::class, 'id', 'study_material_id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function scopeOfType($query, $type)
    {
        if ($type) {
            return $query->where('type', $type);
        }

        return $query;
    }

    public function scopeOfSubject($query, $subjectId)
    {
        if ($subjectId) {
            return $query->where('subject_id', $subjectId);
        }

        return $query;
    }

    public function scopeOfProfessor($query, $professorID)
    {
        if ($professorID) {
            return $query->where('professor_id', $professorID);
        }
        return $query;
    }

    public function scopeOfChapter($query, $chapterId)
    {
        if ($chapterId) {
            return $query->where('chapter_id', $chapterId);
        }

        return $query;
    }

    public function scopeOfLanguage($query, $languageId)
    {
        if ($languageId) {
            return $query->where('language_id', $languageId);
        }
        return $query;
    }

    public function scopeOfUser($query)
    {
        $packageIDs = OrderItem::where('user_id', Auth::id())
            ->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->pluck('package_id')
            ->unique()
            ->values();

        $query->whereHas('package_study_material', function($query) use ($packageIDs) {
            $query->whereIn('package_id', $packageIDs);
        });
    }

    public function scopeOfSearch($query, $search)
    {
        if ($search) {
            return $query->where('title','LIKE','%'.$search.'%')
                ->orWhereHas('professor', function($query) use($search) {
                    $query->where('name','LIKE','%'.$search.'%');
                })
                ->orWhereHas('subject', function($query) use($search) {
                    $query->where('name','LIKE','%'.$search.'%');
                })
                ->orWhereHas('chapter', function($query) use($search) {
                    $query->where('name','LIKE','%'.$search.'%');
                });
        };

        return $query;
    }

    public static function getAll(
        $type = null,
        $subjectId = null,
        $chapterId = null,
        $professorId = null,
        $languageId = null,
        $search= null,
        $page = null,
        $limit = null
    ) {
        $page = $page ?: 1;
        $limit = $limit ?: 10;

        $query = StudyMaterialV1::with(
            'subject',
            'chapter',
            'professor',
            'language',
            'course'
        )
        ->orderBy('created_at', 'desc')
        ->ofUser()
        ->ofType($type)
        ->ofSubject($subjectId)
        ->ofChapter($chapterId)
        ->ofProfessor($professorId)
        ->ofLanguage($languageId)
        ->ofSearch($search)
        ->paginate($limit, ['*'], 'page', $page);

        return $query;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Answer extends BaseModel
{
    protected $guarded = ['id'];

//    protected $casts = [
//        'created_at' => 'datetime:d-m-Y',
//    ];

    public function question()
    {
        if (auth('api')->user()->role == 6) {
            return $this->belongsTo('App\Models\AskAQuestion')->withTrashed();
        }

        return $this->belongsTo('App\Models\AskAQuestion');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfProfessor($query)
    {
        return $query->where('user_id', Auth::id())->latest()->with('question.user', 'question.video');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function video()
    {
        return $this->hasOne('App\Models\video', 'question_id');
    }

    public function package()
    {
        return $this->hasOne('App\Models\Package','question_id');
    }

}

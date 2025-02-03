<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Address extends BaseModel
{
    use SoftDeletes;
    protected $guarded = ['id'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id', 'user_id');
    }

    /**
     * @param  Builder $query
     */
    public function scopeAuthenticated($query) {
        $query->where('user_id', Auth::id())->orderBy('id','DESC');
    }
}

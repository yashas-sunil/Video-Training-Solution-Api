<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Student extends BaseModel
{
    use SoftDeletes;
    protected $guarded = ['id'];

    public function getImageAttribute($value) {
        if (! $value) {
            return null;
        }

        return url('storage/students/images') . '/' . $value;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function level() {
        return $this->belongsTo(Level::class);
    }

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function state() {
        return $this->belongsTo(State::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfAssociate($query)
    {
        return $query->where('associate_id', Auth::id());
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfVerified($query)
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->where('is_verified', true);
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfBranchManager($query)
    {
        return $query->where('branch_manager_id', auth('api')->id());
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFreemium extends BaseModel
{
    use SoftDeletes;

    public $table="user_freemium";

    protected $guarded = ['id'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeUser($query)
    {
        return $query->where('user_id', Auth::id())->with('package')->get();
    }

    public function packages(){
        return $this->belongsTo(Package::class);
    }

    /**
     * @param Builder $query
     * @param integer $packageID
     * @return mixed
     */
    public function scopePackage($query, $packageID = null)
    {
        return $this->where('package_id', $packageID)
            ->whereHas('user_freemium', function($query) {
                $query->where('user_id', Auth::id());
            });
    }
    
}

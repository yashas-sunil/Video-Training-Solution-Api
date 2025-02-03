<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class WishList extends BaseModel
{
    use SoftDeletes;

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

    public static function findByUserOrUuid($userId = null, $uuid = null) {

        if (empty($userId) && empty($uuid)) {
            return null;
        }
        $package=Package::where('is_archived',1)->pluck('id');
        /** @var Builder $query */
        $query = WishList::all();
        $query->whereNotIn('package_id', $package);
        if ($userId) {
            $query = $query->where('user_id', $userId);
        }
        else if ($uuid) {
            $query = $query->where('uuid', $uuid);
        }

        return $query;
    }

    public function packages(){
        return $this->belongsTo(Package::class);
    }
}

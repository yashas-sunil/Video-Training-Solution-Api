<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends BaseModel
{
    use SoftDeletes;
    public function states() {
        return $this->hasMany(State::class);
    }

    /**
     * @param Builder $query
     * @param string $search
     * @return mixed
     */
    public function scopeOfSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where("name", "LIKE", "%$search%");
    }

    public static function getAll($search = null)
    {
        $query = Country::query();
        $query->ofSearch($search);
        return $query->get();
    }
}

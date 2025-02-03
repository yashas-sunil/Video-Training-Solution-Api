<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Setting extends BaseModel
{
    protected $guarded = ['id'];

//    protected $casts = [
//        'value' => 'double',
//    ];


    /**
     * @param Builder $query
     * @param string $key
     * @return Builder
     */
    public function scopeOfKey($query, $key)
    {
        return $query->where('key', $key);
    }

    public static function getAll($key = null)
    {
        $query = Setting::query();

        if ($key) {
            $query->ofKey($key);
        }

        return $query->get()->mapWithKeys(function ($item) {
            return [$item['key'] => $item['key'] != 'gstn' ? floatval($item['value']) : $item['value']];
        });
    }
}

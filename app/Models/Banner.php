<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends BaseModel
{
    use SoftDeletes;
    protected $appends = ['image_url'];

    public function getImageUrlAttribute() {
        if ($this->image) {
            return env('IMAGE_URL') . '/banners/' . $this->image;
        }

        return null;
    }

    public static function getAll()
    {
        /** @var Banner|Builder $query */
        $query = self::query();

        $query->orderBy('order');

        return $query->get();
    }
}

<?php

namespace App\Models;

use App\PrivateCoupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends BaseModel
{
    use SoftDeletes;
    /**
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    const PUBLIC = 1;
    const PRIVATE = 2;
    const DRAFT = 1;
    const PUBLISH = 2;
    const UNPUBLISH = 3;
    const FLAT = 1;
    const PERCENTAGE = 2;

    // FIXED PRICE COUPON

    const TYPE_FIXED_PRICE = 3;
    const FIXED_PRICE_PACKAGE_COUNT = 5;

    // FIXED PRICE COUPON [END]

    public function scopeSearch($query, $search) {
        return $query->where('name', $search);
    }

    public function privateCoupon()
    {
        return $this->hasOne(PrivateCoupon::class);
    }
}

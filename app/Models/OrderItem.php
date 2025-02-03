<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderItem extends BaseModel
{
    protected $guarded = ['id'];

    const PAYMENT_STATUS_FAILED = 0;
    const PAYMENT_STATUS_PARTIALLY_PAID = 1;
    const PAYMENT_STATUS_FULLY_PAID = 2;

    const ITEM_TYPE_PACKAGE = 1;
    const ITEM_TYPE_STUDY_MATERIAL = 2;

    const STATUS_ORDER_PLACED = 1;
    const STATUS_ORDER_PLACED_TEXT = 'Order Placed';

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function package()
    {
        return $this->belongsTo('App\Models\Package');
    }

    public function videoHistories()
    {
        return $this->hasMany(VideoHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function packageExtensions()
    {
        return $this->hasMany(PackageExtension::class);
    }

    /**
     * @param Builder $query
     * @param integer $packageID
     * @return mixed
     */
    public function scopePackage($query, $packageID = null)
    {
        return $this->where('package_id', $packageID)
            ->whereHas('order', function($query) {
                $query->where('user_id', Auth::id());
            });
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeOfAssociate($query)
    {
        $query->whereHas('order', function($query) {
            $query->where('associate_id', Auth::id());
        });
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeOfPaid($query)
    {
        return $query->whereIn('payment_status', [
            OrderItem::PAYMENT_STATUS_PARTIALLY_PAID,
            OrderItem::PAYMENT_STATUS_FULLY_PAID
        ]);
    }

    public static function getAll($packageID = null, $with = '') {
        $qyery = OrderItem::with('package')
            ->Package($packageID)
            ->ofPaid();

        if ($with) {
            $qyery->with($with);
        }

        return $qyery->get();
    }

    public function markAsPaid()
    {
        $this->payment_status = self::PAYMENT_STATUS_FULLY_PAID;
        $this->save();
    }

}

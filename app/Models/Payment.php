<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Payment extends BaseModel
{
    const PAYMENT_STATUS_SUCCESS = 1;
    const PAYMENT_STATUS_FAILURE = 0;

    const UPDATE_METHOD_CCAVENUE = 1;
    const UPDATE_METHOD_MANUAL = 2;
    const UPDATE_METHOD_CRON = 3;

    const UPDATE_METHOD_EASEBUZZ = 4;

    const STATE_ID_MAHARASHTRA = 22;
    const STATE_MAHARASHTRA = 'Maharashtra';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'payment_order_items')->withPivot('is_balance_payment');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfAuth($query)
    {
        return $query->where('user_id', Auth::id())->where('payment_status', self::PAYMENT_STATUS_SUCCESS);
    }

    public function scopeOfRecent($query, $recent)
    {
        if ($recent) {
                if ($recent == 1) {
                    $query->whereDate('created_at', '>', Carbon::now()->subWeek());
                }
                if ($recent == 2) {
                    $query->whereDate('created_at', '>', Carbon::now()->subMonth());
                }
                if ($recent == 3) {
                    $query->whereDate('created_at', '>', Carbon::now()->subMonth(3));
                }
        }
        else{
            return $query;
        }
    }

    public static function getAll($with = [],$page = null,$limit = null, $recent = null)
    {
        $query = Payment::query();

        $page = $page ?: 1;
        $limit = $limit ?: 7;

        $query->ofAuth()
              ->ofRecent($recent);

        if ($with) {
            if ($with == ['orderItems.package']) {
                $query->with(['orderItems.package' => function($query) {
                    $query->withTrashed();
                }]);
            } else {
                $query->with($with);
            }
        }

        return $query->orderBY('id','desc')
        //->paginate($limit, ['*'], 'page', $page);
        ->get();
    }
}

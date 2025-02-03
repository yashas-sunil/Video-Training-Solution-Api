<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Order extends BaseModel
{
    const STATUS_RECEIVED = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_DELIVERED = 4;
    const STATUS_PENDING = 5;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_RETURNED = 3;
    const STATUS_ABORTED = 4;
    const STUDENT_TYPE_NEW = 'new';
    const STUDENT_TYPE_EXISTING = 'existing';

    const PAYMENT_STATUS_SUCCESS = 1;
    const PAYMENT_STATUS_FAILED = 2;
    const PAYMENT_STATUS_ABORTED = 3;
    const PAYMENT_STATUS_INVALID = 4;
    const PAYMENT_STATUS_INITIATED = 5;

    const UPDATE_METHOD_CCAVENUE = 1;
    const UPDATE_METHOD_MANUAL = 2;
    const UPDATE_METHOD_CRON = 3;

    const UPDATE_METHOD_EASEBUZZ = 4;

    protected $guarded = ['id'];

    protected $appends = [
        'associate_payment_mode',
        'order_status',
    ];

    public function orderItems()
    {
        return $this->hasMany('App\Models\OrderItem');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Student', 'user_id', 'user_id');
    }

    public function private_coupons()
    {
        return $this->belongsToMany(Coupon::class,'private_coupons','coupon_id','coupon_id');
    }


    public function getAssociatePaymentModeAttribute()
    {
        if ($this->associate_id) {
            if ($this->payment_url) {
                return 'Url';
            }

            return 'Self';
        }

        return null;
    }

    public function getOrderStatusAttribute()
    {
        switch ($this->status) {
            case self::STATUS_RECEIVED: return 'Order Received';
            break;
            case self::STATUS_PROCESSED: return 'Order Processed';
            break;
            case self::STATUS_SHIPPED: return 'Order Shipped';
            break;
            case self::STATUS_DELIVERED: return 'Order Delivered';
            break;
            case self::STATUS_PENDING: return 'Order Pending';
            break;
            default: return 'Unknown Status';
            break;
        }
    }

    /**
     * @param Builder $query
     * @param integer $id
     * @return Builder
     */
    public function scopeOfAssociate($query, $id = null)
    {
        return $query->where('associate_id', auth('api')->id());
    }

    /**
     * @param Builder $query
     * @param integer $id
     * @return Builder
     */
    public function scopeOfBranchManager($query, $id = null)
    {
        return $query->where('branch_manager_id', auth('api')->id());
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfStatus($query)
    {
        return $query->where('status', '!=', Order::STATUS_PENDING);
    }

    /**
     * @param Builder $query
     * @param integer $from
     * @param integer $to
     * @return Builder
     */
    public function scopeOfMonths($query, $from = null, $to = null) {
        if (!$from || !$to) {
            return $query;
        }

        $query->whereBetween('created_at', [Carbon::create(null, $from), Carbon::create(null, $to)]);
    }

    public static function getCommission($monthFrom = null, $monthTo = null)
    {
        /** @var Order|Builder $query */
        $query = Order::query();

        $query->ofAssociate();

        $query->ofStatus();

        $query->ofMonths($monthFrom, $monthTo);

        $commission = $query->sum('commission');

        return $commission;
    }

    public static function getSales($studentType = null, $monthFrom = null, $monthTo = null)
    {
        /** @var Order|Builder $query */
        $query = Order::query();

        $query->ofAssociate();
        $query->ofStatus();
        $query->ofStudentType($studentType);

        return $query->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get();
    }

    public function scopeOfStudentType($query, $type = null)
    {
        if (!$type) {
            return $query;
        }

        if ($type == Order::STUDENT_TYPE_NEW) {
            return $query->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) = 1')->get();
        }

        if ($type == Order::STUDENT_TYPE_EXISTING) {
            return $query->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get();
        }
    }
    

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}

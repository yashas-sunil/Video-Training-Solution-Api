<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class JMoney extends BaseModel
{
    protected $table = 'j_money';
    protected $dates = ['expire_at'];
    protected $guarded = ['id'];

    const SIGN_UP = 1;
    const FIRST_PURCHASE = 2;
    const PROMOTIONAL_ACTIVITY = 3;
    const REFERRAL_ACTIVITY = 4;
    const REFUND = 5;
    const SPIN_WHEEL_REWARD=3;
    const CASHBACK=6;
    const PURCHASE=8;


    const SIGN_UP_VAL = 'SIGN UP';
    const FIRST_PURCHASE_VAL = 'FIRST PURCHASE';
    const PROMOTIONAL_ACTIVITY_VAL='PROMOTIONAL';
    const SPIN_WHEEL_REWARD_VAL = 'SPIN WHEEL REWARD';
    const REFERRAL_ACTIVITY_VAL = 'REFERRAL ACTIVITY';
    const REFUND_VAL = 'REFUND';
    const CASHBACK_VAL='CASHBACK';
    const PURCHASE_VAL= 'PURCHASE';

    const NOT_USED = 0;
    const USED = 1;

    public function getActivityAttribute($val)
    {
        switch ($val) {
            case self::SIGN_UP: return self::SIGN_UP_VAL;
            break;
            case self::FIRST_PURCHASE: return self::FIRST_PURCHASE_VAL;
            break;
            case self::PROMOTIONAL_ACTIVITY: return self::PROMOTIONAL_ACTIVITY_VAL;
            break;
            case self::REFERRAL_ACTIVITY: return self::REFERRAL_ACTIVITY_VAL;
            break;
            case self::REFUND: return self::REFUND_VAL;
            break;
            case self::CASHBACK: return self::CASHBACK_VAL;
            break;
            case self::PURCHASE: return self::PURCHASE_VAL;
            break;
            default: return 'UNKNOWN ACTIVITY';
            break;
        }
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}

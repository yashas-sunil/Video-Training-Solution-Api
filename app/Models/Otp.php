<?php

namespace App\Models;

use App\Notifications\VerifyOTP;
use App\Exceptions\InvalidOtpException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/** @noinspection PhpHierarchyChecksInspection */

/**
 * App\Models\Otp
 *
 * @property int $id
 * @property string $action
 * @property int|null $action_id
 * @property string $mobile
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @method static Builder|Otp newModelQuery()
 * @method static Builder|Otp newQuery()
 * @method static Builder|Otp query()
 * @method static Builder|Otp whereAction($value)
 * @method static Builder|Otp whereActionId($value)
 * @method static Builder|Otp whereCode($value)
 * @method static Builder|Otp whereCreatedAt($value)
 * @method static Builder|Otp whereId($value)
 * @method static Builder|Otp wherePhone($value)
 * @method static Builder|Otp whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Otp extends BaseModel
{

    const ACTION_DEFAULT = 'default';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'verified_at',
    ];

    public function __construct(array $attributes = [])
    {
        $this->code = mt_rand(1000, 9999);
        $this->action = self::ACTION_DEFAULT;

        parent::__construct($attributes);
    }

    /**
     * Find a otp by its token.
     *
     * @param $token
     * @return Otp
     */
    public static function findByToken($token) {
        $id = decrypt($token);

        return self::find($id);
    }

    public static function verify($token, $code, $action = self::ACTION_DEFAULT) {
        $otp = self::findByToken($token);

        if(is_null($otp)) {
            throw new InvalidOtpException("Verification Code entered is incorrect");
        }

        if($otp->code != $code || $otp->action != $action) {
            throw new InvalidOtpException("Verification Code entered is incorrect");
        }

        if($otp->isExpired() || $otp->isVerified()) {
            throw new InvalidOtpException("Verification code has expired. resend again");
        }

        $otp->markAsVerified();

        return true;
    }

    public function send() {

        try{
            $notification = new VerifyOTP($this);

            Notification::route('sms', $this->mobile)->notify($notification);
        }
        catch (\Exception $exception) {
            info($exception->getMessage(), ['exception' => $exception]);
        }

    }

    public function getToken() {
        return $token = encrypt($this->id);
    }

    public function isVerified() {
        return !is_null($this->verified_at);
    }

    public function isExpired() {
        return Carbon::now()->gt($this->updated_at->addMinutes(10));
    }

    public function markAsVerified($save = true) {
        $this->verified_at = Carbon::now();

        if($save) {
            $this->save();
        }
    }
}

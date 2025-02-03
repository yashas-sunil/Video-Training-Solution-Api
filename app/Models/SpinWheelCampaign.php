<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpinWheelCampaign extends Model
{
    public function spinWheelSegments()
    {
        return $this->hasMany(SpinWheelSegment::class);
    }
}

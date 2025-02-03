<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempCampaignPoint extends Model
{
    public function campaignRegistration()
    {
        return $this->belongsTo(CampaignRegistration::class);
    }
}

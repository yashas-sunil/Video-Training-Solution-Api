<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BaseModel extends Model
{
    /**
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate($date)
    {
        return $date->toIso8601String();
    }
}

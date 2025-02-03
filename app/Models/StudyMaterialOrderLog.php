<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyMaterialOrderLog extends Model
{
    protected $guarded = ['id'];

    const STATUS_ORDER_PLACED = 1;
    const STATUS_ORDER_PLACED_TEXT = 'Order Placed';
}

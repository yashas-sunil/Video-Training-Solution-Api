<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfessorPayout extends Model
{
    protected $fillable = [
       'professor_id','order_id','package_id','amount','percentage'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function getAll($limit=null)
    {
        $query = ProfessorPayout::query();
        return $query->paginate((int)$limit);

    }
}

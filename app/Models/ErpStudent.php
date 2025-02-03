<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpStudent extends Model
{
    protected $connection = 'erp_db';
    protected $table = 'student';
}

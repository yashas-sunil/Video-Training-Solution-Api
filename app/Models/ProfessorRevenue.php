<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorRevenue extends Model
{
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'invoice_id', 'receipt_no');
    }
}

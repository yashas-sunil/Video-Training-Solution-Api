<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceAccessLog extends Model
{
    protected $table = 'invoice_access_log';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->updated_at = null;
        });
    }
}
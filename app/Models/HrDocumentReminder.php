<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** When a staff member was last reminded about missing HR documents. */
class HrDocumentReminder extends Model
{
    protected $fillable = ['user_id', 'reminders_sent', 'last_reminded_at'];

    protected $casts = ['last_reminded_at' => 'datetime'];
}

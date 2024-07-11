<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanNotification extends Model
{
    use HasFactory;
    protected $table = "craftsman_notifications";
    protected $fillable = [
        'title',
        'msg',
        'craftsman_id',
    ];
}

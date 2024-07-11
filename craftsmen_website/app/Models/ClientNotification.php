<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientNotification extends Model
{
    use HasFactory;
    protected $table = "client_notifications";
    protected $fillable = [
        'title',
        'msg',
        'client_id',
    ];
}

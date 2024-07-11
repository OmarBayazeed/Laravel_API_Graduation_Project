<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientsRating extends Model
{
    use HasFactory;

    protected $table = "clients_ratings";
    protected $fillable = [
        'rating',
        'comment',
        'craftsman_id',
        'client_id',
    ];

    public function craftsman(){
        return $this->belongsTo(Craftsman::class, 'craftsman_id', 'id');
    }


}

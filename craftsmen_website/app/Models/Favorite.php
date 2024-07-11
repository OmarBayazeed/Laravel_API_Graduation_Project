<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $table = "favorites";
    protected $fillable = [
        'client_id',
        'craftsman_id',
        'craft',
        'list_id',
    ];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function favoriteList(){
        return $this->belongsTo(Favoritelist::class, 'list_id', 'id');
    }
}

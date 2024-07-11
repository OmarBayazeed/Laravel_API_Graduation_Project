<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteList extends Model
{
    use HasFactory;
    protected $table = "favoriteLists";
    protected $fillable = [
        'title',
        'description',
        'client_id',
    ];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function favorites(){
        return $this->hasMany(Favorite::class, 'list_id', 'id');
    }
}

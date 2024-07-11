<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchImages extends Model
{
    use HasFactory;

    protected $table = "search_images";
    protected $fillable = [
        'image',
        'craftsman_id',
    ];


    public function craftsman(){
        return $this->belongsTo(Craftsman::class, 'craftsman_id', 'id');
    }
}

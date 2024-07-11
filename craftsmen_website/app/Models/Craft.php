<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Craft extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image'
    ];

    public function craftsmen(){
        return $this->hasMany(Craftsman::class, 'craft_id', 'id');
    }

    public function job_offer(){
        return $this->hasMany(JobsOffer::class, 'craft_id', 'id');
    }
}

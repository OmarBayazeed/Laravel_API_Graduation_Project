<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'client_price',
        'address',
        'phone',
        'city',
        'start_date',
        'end_date',
        'craft_id',
        'client_id'
    ];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function craft(){
        return $this->belongsTo(Craft::class, 'craft_id', 'id');
    }

    public function job_offer_images(){
        return $this->hasMany(JobsOfferImage::class, 'job_offer_id', 'id');
    }

    public function replies(){
        return $this->hasMany(JobsOfferReply::class, 'job_offer_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsOfferReply extends Model
{
    use HasFactory;

    protected $table = "jobs_offer_replies";
    protected $fillable = [
        'offered_price',
        'description',
        'type_of_pricing',
        'job_offer_id',
        'craftsman_id',
    ];

    public function craftsman(){
        return $this->belongsTo(Craft::class, 'craftsman_id', 'id');
    }

    public function job_offer(){
        return $this->belongsTo(JobsOffer::class, 'job_offer_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsOfferInspection extends Model
{
    use HasFactory;

    protected $table = "jobs_offer_inspections";
    protected $fillable = [
        'offered_price',
        'inspection_price',
        'description',
        'type_of_pricing',
        'start_date',
        'end_date',
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

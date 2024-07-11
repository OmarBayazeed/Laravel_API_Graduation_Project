<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsOfferImage extends Model
{
    use HasFactory;

    protected $table = "jobs_offer_images";
    protected $fillable = [
        'image',
        'job_offer_id',
    ];
    public function craft(){
        return $this->belongsTo(JobsOffer::class, 'job_offer_id', 'id');
    }
}

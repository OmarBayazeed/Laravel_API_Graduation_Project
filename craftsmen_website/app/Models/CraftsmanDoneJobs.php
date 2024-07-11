<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanDoneJobs extends Model
{
    use HasFactory;

    protected $table = "craftsman_done_jobs";
    protected $fillable = [
        'title',
        'description',
        'status',
        'price',
        'city',
        'address',
        'phone',
        'craftsman_id',
        'client_id',
    ];

    public function craftsman(){
        return $this->belongsTo(Craftsman::class, 'craftsman_id', 'id');
    }

    public function done_job_images(){
        return $this->hasMany(CraftsmanDoneJobsimage::class, 'craftsmanDoneJob_id', 'id');
    }


    public function done_job_ratings(){
        return $this->hasOne(CraftsmanDoneJobsRating::class, 'craftsmanDoneJob_id', 'id');
    }

    public function client_rating(){
        return $this->hasOne(ClientsRating::class, 'done_job_id', 'id');
    }
}

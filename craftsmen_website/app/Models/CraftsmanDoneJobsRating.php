<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanDoneJobsRating extends Model
{
    use HasFactory;

    protected $table = "craftsman_done_jobs_ratings";
    protected $fillable = [
        'rating',
        'comment',
        'craftsmanDoneJob_id',
        'client_id',
    ];


    public function craftsman_done_job(){
        return $this->belongsTo(CraftsmanDoneJobs::class, 'craftsmanDoneJob_id', 'id');
    }

    public function clients_rating_done_jobs(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}

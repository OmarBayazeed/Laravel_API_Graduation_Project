<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanDoneJobsimage extends Model
{
    use HasFactory;

    protected $table = "craftsman_done_jobs_images";
    protected $fillable = [
        'image',
        'craftsmanDoneJob_id',
    ];

    public function craftsman_done_job(){
        return $this->belongsTo(CraftsmanDoneJobs::class, 'craftsmanDoneJob_id', 'id');
    }
}

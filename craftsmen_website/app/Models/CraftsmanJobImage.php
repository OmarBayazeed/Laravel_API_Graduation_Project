<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanJobImage extends Model
{
    use HasFactory;
    protected $table = "craftsman_jobs_images";
    protected $fillable = [
        'image',
        'job_id',
    ];

    public function craftsman_job(){
        return $this->belongsTo(CraftsmanJob::class, 'job_id', 'id');
    }
}

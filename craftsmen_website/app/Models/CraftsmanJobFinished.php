<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanJobFinished extends Model
{
    use HasFactory;

    protected $table = "craftsman_jobs_finished";
    protected $fillable = [
        'CraftsmanStatus',
        'ClientStatus',
        'active_job_id',
        'craftsman_id',
        'client_id',
    ];


}

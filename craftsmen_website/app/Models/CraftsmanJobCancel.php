<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanJobCancel extends Model
{
    use HasFactory;
    protected $table = "job_cancellation_craftsman";
    protected $fillable = [
        'status',
        'craftsman_id',
        'active_job_id',
    ];

    public function craftsman(){
        return $this->belongsTo(Craftsman::class, 'craftsman_id', 'id');
    }

    public function active_job(){
        return $this->belongsTo(CraftsmanJob::class, 'active_job_id', 'id');
    }
}

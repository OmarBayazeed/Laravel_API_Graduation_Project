<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientJobCancel extends Model
{
    use HasFactory;
    protected $table = "job_cancellation_client";
    protected $fillable = [
        'status',
        'client_id',
        'active_job_id',
    ];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function active_job(){
        return $this->belongsTo(CraftsmanJob::class, 'active_job_id', 'id');
    }
}

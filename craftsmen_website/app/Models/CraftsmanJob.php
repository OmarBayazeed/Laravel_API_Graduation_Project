<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftsmanJob extends Model
{
    use HasFactory;

    protected $table = "craftsman_jobs";
    protected $fillable = [
        'title',
        'description',
        'price',
        'city',
        'address',
        'phone',
        'start_date',
        'end_date',
        'craftsman_id',
        'client_id',
        'type_of_pricing',
    ];

    public function craftsman(){
        return $this->belongsTo(Craftsman::class, 'craftsman_id', 'id');
    }

    public function job_images(){
        return $this->hasMany(CraftsmanJobImage::class, 'job_id', 'id');
    }

}

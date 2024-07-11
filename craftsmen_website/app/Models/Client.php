<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'phone',
        'image',
        'social_id',
        'social_type',
    ];

    protected $hidden = [
        'password',
    ];


    public function job_offer(){
        return $this->hasMany(JobsOffer::class, 'client_id', 'id');
    }

    public function favoriteCraftsmen(){
        return $this->belongsToMany(Craftsman::class, 'favorites', 'client_id', 'craftsman_id', 'id', 'id');
    }

    public function clients_rating_done_jobs(){
        return $this->hasOne(Craftsman::class, 'client_id', 'id');
    }

    public function xxcraftsmen_rating_clients(){
        return $this->belongsToMany(Craftsman::class, 'clients_ratings', 'client_id', 'craftsman_id', 'id', 'id');
    }


    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }
}

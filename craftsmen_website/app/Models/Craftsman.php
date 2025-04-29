<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Craftsman extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'status',
        'availability',
        'description',
        'image',
        'craft_id',
        'social_id',
        'social_type',
        'mobile_token',
    ];
    protected $hidden = [
        'password',
        'mobile_token',
    ];

    public function phones(){
        return $this->hasMany(Phone::class, 'craftsman_id', 'id');
    }

    public function cities(){
        return $this->hasMany(City::class, 'craftsman_id', 'id');
    }

    public function done_jobs(){
        return $this->hasMany(CraftsmanDoneJobs::class, 'craftsman_id', 'id');
    }

    public function craft(){
        return $this->belongsTo(Craft::class, 'craft_id', 'id');
    }

    public function jobs(){
        return $this->hasMany(CraftsmanJob::class, 'craftsman_id', 'id');
    }

    public function reply(){
        return $this->hasOne(JobsOfferReply::class, 'craftsman_id', 'id');
    }

    public function f_clients(){
        return $this->belongsToMany(Client::class, 'favorites', 'craftsman_id', 'client_id', 'id', 'id');
    }


    public function craftsmen_rating_clients(){
        return $this->hasMany(Client::class, 'clients_ratings', 'craftsman_id', 'id');
    }

    public function searchImages(){
        return $this->hasMany(SearchImages::class, 'craftsman_id', 'id');
    }


    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

    public function canceled_active_jobs(){
        return $this->hasMany(CraftsmanJobCancel::class, 'craftsman_id', 'id');
    }
}

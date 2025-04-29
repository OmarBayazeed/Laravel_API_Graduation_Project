<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $table = "chats";
    protected $fillable = [
        'craftsman_id',
        'client_id',
    ];
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }
}

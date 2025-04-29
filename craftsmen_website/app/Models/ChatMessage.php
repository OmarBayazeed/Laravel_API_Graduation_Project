<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    protected $table = 'chat_messages';
    protected $fillable = [
        'chat_id',
        'sender',
        'msg',
        'type',
    ];
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }
}

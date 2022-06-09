<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'chat_id', 'sender_id', 'message', 'seen'
    ];

    public function sender(){
        return $this->belongsTo(User::class,'sender_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    const USER_FIO_STATUS = 'getUserByFIO';
    const USER_PHONE_STATUS = 'getUserByPhone';
    const USER_DIRECTION_STATUS = 'getUserByDirection';
    const USER_QUESTION_STATUS = "userAsksQuestion";

    protected $fillable = [
        'chat_id',
        'status',
        'message',
    ];
}

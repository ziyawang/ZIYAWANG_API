<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $connection='mysql_talk';
    protected $table = 'Message';
    protected $primaryKey = 'MessageId';
    
}

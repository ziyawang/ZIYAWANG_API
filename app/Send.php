<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Send extends Model
{
    protected $connection='mysql_talk';
    protected $table = 'send';
    protected $primaryKey = 'sendId';
   
   }
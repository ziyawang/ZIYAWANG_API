<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'T_P_PROJECTINFO';
    protected $primaryKey = 'ProjectID';

    protected $fillable = ['TypeID', 'UserID','ProArea','WordDes','VoiceDes','PictureDes'];
}

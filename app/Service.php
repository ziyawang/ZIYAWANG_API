<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
	protected $table = 'T_U_SERVICEINFO';
    protected $primaryKey = 'ServiceID';

    protected $fillable = ['ServiceName', 'ServiceIntroduction','ServiceLocation','ServiceType','ServiceLevel','ConnectPerson','ConnectPhone','ServiceArea','ConfirmationP1','ConfirmationP2','ConfirmationP3','UserID'];
}

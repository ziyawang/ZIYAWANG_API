<?php

/**
 * 临时控制器
 */
namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use App\User;
use App\Project;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;

class TempController extends BaseController
{
    public function addQuestion(){
        $payload = app('request')->all();
        $data['Question'] = $payload['Question'];
        $data['Choices'] = $payload['Choices'];
        $data['Paper'] = $payload['Paper'];
        $res = DB::table('T_Q_PAPER')->insert($data);
        if($res){
            return ['status_code'=>'200'];
        } else {
            return ['status_code'=>'400'];
        }
    }
}

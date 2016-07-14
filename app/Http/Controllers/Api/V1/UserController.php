<?php

/**
 * 用户控制器
 */
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use App\User;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;

class UserController extends BaseController
{
    /**
     * 获取用户信息
     *
     * @return mixed
     */
    public function me()
    {   
        // $user = JWTAuth::parseToken()->authenticate()->toArray();
        // dd($user);
        return $this->response->array($this->auth->user());
    }

    /**
     * 服务方信息完善
     *
     * @return mixed
     */
    public function confirm()
    {   
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        $payload['UserID'] = $UserID;
        $res = Service::create($payload);
        // 发送结果
        if ($res) {
            return $this->response->array(['success' => 'Service Confirm Success']);
        } else {
            return $this->response->array(['error' => 'Service Confirm Error']);
        }
    }

    /**
     * 重置用户密码
     */
    public function changePassword()
    {

        // 获取用户id和新密码
        $payload = app('request')->only('password');
        $password = $payload['password'];

        // 更新用户密码
        $user = $this->auth->user();
        $user->password = bcrypt($password);
        $res = $user->save();

        // 发送结果
        if ($res) {
            return $this->response->array(['success' => 'Password Change Success']);
        } else {
            return $this->response->array(['error' => 'Password Change Error']);
        }
    }
}

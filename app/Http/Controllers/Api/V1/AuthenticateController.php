<?php

/**
 * 用户验证，获取 token
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

class AuthenticateController extends Controller
{
    use Helpers;

    /**
     * 用户注册
     *
     * @param Request $request
     */
    public function register()
    {
        // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11', 'unique:users'],
            'password' => ['required', 'min:6'],
            'smscode' => ['required', 'min:6']
        ];

        $payload = app('request')->only('phonenumber', 'password', 'smscode');
        $validator = app('validator')->make($payload, $rules);

        // 手机验证码验证
        if (Cache::has($payload['phonenumber'])) {
           $smscode = Cache::get($payload['phonenumber']);
        
           if ($smscode != $payload['smscode']) {
               return $this->response->array(['status_code' => '402', 'msg' => 'phonenumber smscode error']);//402 手机或者验证码错误，401数据格式验证不通过，501服务端错误，403手机验证码发送失败,404登录失败
           }
        } else {
           return $this->response->array(['status_code' => '402', 'msg' => 'phonenumber smscode error']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['status_code' => '401', 'msg' => $validator->errors()]);
        }

        // 创建用户
        $res = User::create([
            'phonenumber' => $payload['phonenumber'],
            'password' => bcrypt($payload['password']),
        ]);

        // 创建用户成功
        if ($res) {
            //给客服发送邮件
            $phonenumber = $payload['phonenumber'];
            $email = '3153679024@qq.com';    
            $title = '新会员注册成功，请查看！';
            $message = '手机号为' . $phonenumber . '的用户注册成为资芽网会员';

            $this->_sendMail($email, $title, $message);

            //生成token
            $user = User::where('phonenumber', $payload['phonenumber'])->first();
            $token = JWTAuth::fromUser($user);
            return $this->response->array(['status_code' => '200', 'msg' => 'Create User Success', 'token' => $token, 'role' => '0']);
        } else {
            return $this->response->array(['status_code' => '501', 'msg' => 'Create User Error']);
        }
    }

    /**
    * 找回密码
    * 
    */
    public function resetPassword()
    {
        // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11'],
            'password' => ['required', 'min:6'],
            'smscode' => ['required', 'min:6']
        ];

        $payload = app('request')->only('phonenumber', 'password', 'smscode');
        $validator = app('validator')->make($payload, $rules);

        // 手机验证码验证
        if (Cache::has($payload['phonenumber'])) {
           $smscode = Cache::get($payload['phonenumber']);
        
           if ($smscode != $payload['smscode']) {
               return $this->response->array(['status_code' => '402', 'msg' => 'phonenumber smscode error']);
           }
        } else {
           return $this->response->array(['status_code' => '402', 'msg' => 'phonenumber smscode error']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['status_code' => '401', 'msg' => $validator->errors()]);
        }

        $user = User::where('phonenumber', $payload['phonenumber'])->first();
        $user->password = bcrypt($payload['password']);
        $res = $user->save();

        $hascertify = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$user->userid, 'T_P_SERVICECERTIFY.State'=>1])->count();

        $isservice = Service::where('UserID', $user->userid)->count();

        if($isservice == 0 && $hascertify == 0){
            $role = 0;
        } elseif( $isservice == 1 && $hascertify == 0) {
            $role = 2;
        } elseif( $isservice == 1 && $hascertify == 1) {
            $role = 1;
        }

        // 发送结果
        if ($res) {
            // 通过用户实例，获取jwt-token
            $token = JWTAuth::fromUser($user);
            return $this->response->array(['status_code' => '200', 'token' => $token, 'role' => $role]);
        } else {
            return $this->response->array(['status_code' => '504', 'msg' => 'Password Change Error']);
        }
    }

    /**
    * 获取用户手机验证码
    */
    public function getSmsCode()
    {
        // 获取手机号码
        $payload = app('request')->only('phonenumber');
        $phonenumber = $payload['phonenumber'];

        $action = app('request')->get('action');
        if ($action == 'register') {
            $user = User::where('phonenumber', $payload['phonenumber'])->first();
            if($user) {
                return $this->response->array(['status_code' => '405', 'msg' => 'phonenumber is exist']);
            }
        } elseif ($action == 'login') {
            $user = User::where('phonenumber', $payload['phonenumber'])->first();
            if(!$user) {
                return $this->response->array(['status_code' => '406', 'msg' => 'phonenumber does not exist']);
            }
        } else {
            return $this->response->array(['status_code' => '401', 'msg' => 'lose argument action']);
        }


        // 获取验证码
        $randNum = $this->__randStr(6, 'NUMBER');

        // 验证码存入缓存 10 分钟
        $expiresAt = 20;

        Cache::put($phonenumber, $randNum, $expiresAt);

        // // 短信内容
        // $smsTxt = '验证码为：' . $randNum . '，请在 10 分钟内使用！';

        // 发送验证码短信
        $res = $this->_sendSms($phonenumber, $randNum, $action);

        // 发送结果
        if ($res) {
            return $this->response->array(['status_code' => '200', 'msg' => 'Send Sms Success']);
        } else {
            return $this->response->array(['status_code' => '503', 'msg' => 'Send Sms Error']);
        }
    }

    /**
     * 登录验证
     *
     * @param Request $request
     * @return mixed
     */
    public function authenticate(Request $request)
    {   
        // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11'],
            'password' => ['required', 'min:6'],
        ];

        //验证格式
        $payload = app('request')->only('phonenumber', 'password');
        $validator = app('validator')->make($payload, $rules);

        //验证手机号是否存在
        $user = User::where('phonenumber', $payload['phonenumber'])->first();
        if(!$user) {
            return $this->response->array(['status_code' => '406', 'msg' => 'phonenumber does not exist']);
        }


        //判断用户状态是否冻结，如果冻结，不能登录
        if($user->Status == 1) {
            return $this->response->array(['status_code' => '404', 'msg' => 'illegal operation']);
        }

        // grab credentials from the request
        $credentials = $request->only('phonenumber', 'password');


        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                // return response()->json(['error' => 'invalid_credentials'], 401);
                return $this->response->array(['status_code' => '404', 'msg' => 'invalid_credentials']);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return $this->response->array(['status_code' => '502', 'msg' => 'could_not_create_token']);
        }

        $hascertify = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$user->userid, 'T_P_SERVICECERTIFY.State'=>1])->count();

        $isservice = Service::where('UserID', $user->userid)->count();

        if($isservice == 0 && $hascertify == 0){
            $role = 0;
        } elseif( $isservice == 1 && $hascertify == 0) {
            $role = 2;
        } elseif( $isservice == 1 && $hascertify == 1) {
            $role = 1;
        }

        // all good so return the token
        return $this->response->array(['status_code' => '200', 'token' => $token, 'role' => $role]);
    }

    /**
     * 更新用户 token
     *
     * @return mixed
     */
    public function upToken()
    {
        $token = Users::refresh();

        return $this->response->array(compact('token'));
    }

    /**
     * 手机验证码登录
     *
     * @return mixed
     */
    public function smsLogin()
    {
         // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11'],
            'smscode' => ['required', 'min:6']
        ];

        $payload = app('request')->only('phonenumber', 'smscode');
        $validator = app('validator')->make($payload, $rules);

        //验证手机号是否存在
        $user = User::where('phonenumber', $payload['phonenumber'])->first();
        if(!$user) {
            return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber does not exist']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['status_code' => '401', 'msg' => $validator->errors()]);
        }

        // 手机验证码验证
        if (Cache::has($payload['phonenumber'])) {
           $smscode = Cache::get($payload['phonenumber']);
        
           if ($smscode != $payload['smscode']) {
               return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber smscode error']);
           }
        } else {
           return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber smscode error']);
        }

        // 通过用户实例，获取jwt-token
        $token = JWTAuth::fromUser($user);
        return $this->response->array(['status_code' => '200', 'token' => $token]);
    }

    /**
     * 随机产生六位数
     *
     * @param int $len
     * @param string $format
     * @return string
     */
    private function __randStr($len = 6, $format = 'ALL')
    {
        switch ($format) {
            case 'ALL':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
            case 'CHAR':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
                break;
            case 'NUMBER':
                $chars = '0123456789';
                break;
            default :
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
        }
        mt_srand((double)microtime() * 1000000 * getmypid());
        $password = "";
        while (strlen($password) < $len)
            $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
        return $password;
    }

}

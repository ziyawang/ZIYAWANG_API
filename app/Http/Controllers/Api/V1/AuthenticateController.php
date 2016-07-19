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
               return $this->response->array(['error' => 'phonenumber smscode error']);
           }
        } else {
           return $this->response->array(['error' => 'phonenumber smscode error']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['error' => $validator->errors()]);
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
            $email = 'zll@ziyawang.com';    
            $title = '新会员注册成功，请查看！';
            $message = '手机号为' . $phonenumber . '的用户注册成为资芽网会员';

            $this->_sendMail($email, $title, $message);

            //生成token
            $user = User::where('phonenumber', $payload['phonenumber'])->first();
            $token = JWTAuth::fromUser($user);
            return $this->response->array(['success' => 'Create User Success', 'token' => $token]);
        } else {
            return $this->response->array(['error' => 'Create User Error']);
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

        if(empty($phonenumber)) return $this->response->array(['error' => 'Send Sms Error']);

        // 获取验证码
        $randNum = $this->__randStr(6, 'NUMBER');

        // 验证码存入缓存 10 分钟
        $expiresAt = 20;

        Cache::put($phonenumber, $randNum, $expiresAt);

        // // 短信内容
        // $smsTxt = '验证码为：' . $randNum . '，请在 10 分钟内使用！';

        // 发送验证码短信
        $res = $this->_sendSms($phonenumber, $randNum);

        // 发送结果
        if ($res) {
            return $this->response->array(['success' => 'Send Sms Success']);
        } else {
            return $this->response->array(['error' => 'Send Sms Error']);
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
        require('/org/code/Code.class.php');
        // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11'],
            'password' => ['required', 'min:6'],
        ];

        $payload = app('request')->only('phonenumber', 'password');
        $validator = app('validator')->make($payload, $rules);

        // grab credentials from the request
        $credentials = $request->only('phonenumber', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                // return response()->json(['error' => 'invalid_credentials'], 401);
                return $this->response->array(['error' => 'invalid_credentials']);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return $this->response->array(['error' => 'could_not_create_token']);
        }

$curlPost = 'phonenumber='.$payload['phonenumber'] . '&password=' . $payload['password'];
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'http://ziyawang.com/session');
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
$data = curl_exec($ch);
curl_close($ch);
// dd($data);

        // all good so return the token
        return $this->response->array(compact('token'));
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
            return $this->response->array(['error' => 'phonenumber does not exist']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['error' => $validator->errors()]);
        }

        // 手机验证码验证
        if (Cache::has($payload['phonenumber'])) {
           $smscode = Cache::get($payload['phonenumber']);
        
           if ($smscode != $payload['smscode']) {
               return $this->response->array(['error' => 'phonenumber smscode error']);
           }
        } else {
           return $this->response->array(['error' => 'phonenumber smscode error']);
        }

        // 通过用户实例，获取jwt-token
        $token = JWTAuth::fromUser($user);
        return $this->response->array(compact('token'));
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

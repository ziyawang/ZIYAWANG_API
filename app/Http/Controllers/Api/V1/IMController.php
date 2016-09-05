<?php

/**
 * 项目发布、展示、详情控制器
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
use DB;

class IMController extends BaseController
{

    /**
     * 获取token
     */
    public function get_token(){
        // 获取用户id
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        if($UserID){
            // 获取token
            $token = $this->get_rongcloud_token($UserID);
            return $this->response->array(['status_code'=>'200','rcToken'=>$token]);
        } else {
            $this->response->array(['status_code'=>'411','msg'=>'error']);
        }
    }


    /**
     * 获取融云token
     * @param  integer $uid 用户id
     * @return integer      token
     */
    function get_rongcloud_token($UserID){

        $user = User::where('userid', $UserID)->first();
        // 用户不存在
        if (empty($user)) {
            return false;
        }

        // 从数据库中获取token
        $token = $user->logintoken;
        // 如果有token就返回
        if ($token) {
            return $token;
        }

        // 获取用户头像
        $UserPicture = $user->UserPicture;
        // 获取用户手机号
        $phonenumber = $user->phonenumber;
        $phonenumber = substr_replace($phonenumber,'****',3,4);
        // 获取头像url格式
        $avatar = 'http://images.ziyawang.com' . $UserPicture;
        // 实例化融云
        $rong_cloud = new \App\RCServer(env('RC_APPKEY'),env('RC_APPSECRET'));
        // 获取token
        $token_json = $rong_cloud->getToken($UserID,$phonenumber,$avatar);
        $token_array = json_decode($token_json,true);
        // 获取token失败
        if ($token_array['code']!=200) {
            return false;
        }
        $token = $token_array['token'];
        // 插入数据库
        $user->rctoken = $token;
        $result = $user->save();
        if ($result) {
            return $token;
        }else{
            return false;
        }
    }

    /**
     * 获取 APP 内指定某天某小时内的所有会话消息记录的下载地址
     * @param $date     指定北京时间某天某小时，格式为：2014010101,表示：2014年1月1日凌晨1点。（必传）
     * @return json|xml
     */
    public function messageHistory() {
        $payload = app('request')->all();
        $date = $payload['date'];
        // 实例化融云
        $rong_cloud = new \App\RCServer(env('RC_APPKEY'),env('RC_APPSECRET'));
        try{
            if(empty($date))
                throw new Exception('时间不能为空');
            $ret = $rong_cloud->curl('/message/history', array('date' => $date));
            if(empty($ret))
                throw new Exception('请求失败');
            return $ret;
        }catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
    
}

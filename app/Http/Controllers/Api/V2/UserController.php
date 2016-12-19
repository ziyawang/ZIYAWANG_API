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
use PDO;

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
        $UserID = $this->auth->user()->toArray()['userid'];
        $this->_upMember($UserID);
        $role = Service::where('UserID', $UserID)->first();
        $MyProCount = Project::where('UserID', $UserID)->where('CertifyState', '<>', 3)->where('DeleteFlag', 0)->count();
        $MyColCount = DB::table('T_P_COLLECTION')->where('UserID', $UserID)->count();
        $MyMsgCount = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID,'Status'=>0])->count();

        $userinfo = User::where('userid',$UserID)->first()->toArray();
        $userinfo['showright'] = json_decode($userinfo['showright'],true);
        $arr = array_keys($userinfo['showright']);
        foreach ($arr as $v) {
            $rong_cloud = new \App\RCServer(env('RC_APPKEY'),env('RC_APPSECRET'));
            $userinfo['showrightios'][] = \App\Tool::getPY($v);
        }
        if(count($userinfo['showright']) == 0){
            $userinfo['showright'] = json_decode("{}");
        }
        foreach ($userinfo['showright'] as $key => $value) {
            $userinfo['showrightarr'][] = [$key,$value];
        }
        if($role){
            $hascertify = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$UserID, 'T_P_SERVICECERTIFY.State'=>1])->count();
            $type = $role->ServiceType;
            $type = explode(',', $type);
            $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
            $role->ServiceType = implode('、', $type);
            if($hascertify == 0){
                $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->count();
                return $this->response->array(['user'=>$userinfo,'role'=>'2','service'=>$role,'MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
            } else {
                $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->orWhere(['PublishState'=>1,'ServiceID'=>$role['ServiceID']])->count();
                return $this->response->array(['user'=>$userinfo,'role'=>'1','service'=>$role,'MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
            }
        } else {
            $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->count();
            return $this->response->array(['user'=>$userinfo,'status_code'=>'200','role'=>'0','MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
        }
    }

    //更新会员状态
    protected function _upMember($userid){
        DB::table('T_U_MEMBER')->where('EndTime','<',date('Y-m-d H:i:s',time()))->update(['Over'=>1]);
        DB::setFetchMode(PDO::FETCH_ASSOC);
        $arr =  DB::table('T_U_MEMBER')->join('T_CONFIG_MEMBER','T_U_MEMBER.MEMBERID','=','T_CONFIG_MEMBER.MEMBERID')->where(['UserID'=>$userid,'Over'=>0,'PayFlag'=>1])->select('EndTime','TypeID','MemberName')->orderBy('MemPayID','desc')->get();
        DB::setFetchMode(PDO::FETCH_CLASS);
        $rightarr = [];
        $showrightarr = [];
        foreach ($arr as $a) {
            $tmp = explode(',', $a['TypeID']);
            $rightarr = array_merge($rightarr, $tmp);
            if(!isset($showrightarr[$a['MemberName']])){
                $showrightarr[$a['MemberName']] = $a['EndTime'];                 
            }
        }
        $right = implode(',', array_unique($rightarr));
        $showright = json_encode($showrightarr);
        DB::table('users')->where('userid',$userid)->update(['right'=>$right,'showright'=>$showright]);
    }
}

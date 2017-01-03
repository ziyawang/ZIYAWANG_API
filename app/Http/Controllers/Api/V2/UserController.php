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
        if($role){
            $role = Service::where('UserID', $UserID)->first()->toArray();
            $role['showlevel'] = explode(',',$role['Level']);
            $star = ['1'=>$this->_starState($UserID,1),'2'=>$this->_starState($UserID,2),'3'=>$this->_starState($UserID,3),'4'=>$this->_starState($UserID,4),'5'=>$this->_starState($UserID,5)];
            $role['showlevelarr'] = $star;
        }

        $MyProCount = Project::where('UserID', $UserID)->where('CertifyState', '<>', 3)->where('DeleteFlag', 0)->count();
        $MyColCount = DB::table('T_P_COLLECTION')->where('UserID', $UserID)->count();
        $MyMsgCount = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID,'Status'=>0])->count();

        $userinfo = User::where('userid',$UserID)->first()->toArray();
        $userinfo['showright'] = json_decode($userinfo['showright'],true);
        $arr = array_keys($userinfo['showright']);
        foreach ($arr as $v) {
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
            $type = $role['ServiceType'];
            $type = explode(',', $type);
            $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
            $role['ServiceType'] = implode('、', $type);
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

    //获取星级认证状态
    protected function _starState($userid,$starid){
        $tmp = DB::table('T_U_STAR')->where(['UserID'=>$userid, 'StarID'=>$starid])->count();
        if($tmp > 0){
            $temp = DB::table('T_U_STAR')->where(['UserID'=>$userid, 'StarID'=>$starid])->orderBy('State','desc')->first();
            return $temp->State;
        } else {
            return 0;
        }
    }

        /**
    *获取服务商列表
    *
    */
    public function serList()
    {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $ServiceType = (isset($payload['ServiceType']) && $payload['ServiceType'] != 'null' && $payload['ServiceType'] != '') ?  $payload['ServiceType'] : null;
        $ServiceArea = (isset($payload['ServiceArea']) && $payload['ServiceArea'] != 'null' && $payload['ServiceArea'] != '')?  $payload['ServiceArea'] : null;
        $ServiceLevel = (isset($payload['ServiceLevel']) && $payload['ServiceLevel'] != 'null' && $payload['ServiceLevel'] != '')?  $payload['ServiceLevel'] : null;
        $members = Service::leftJoin('users','T_U_SERVICEINFO.UserID','=','users.userid')->lists('ServiceID');
        if($ServiceLevel){
            $members = Service::leftJoin('users','T_U_SERVICEINFO.UserID','=','users.userid')->where('right','<>','')->lists('ServiceID');
        }

        if(!$ServiceType && !$ServiceArea) {
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->where('ServiceID', '<>', '839')->whereIn('ServiceID',$members)->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && !$ServiceArea) {
            $services = Service::where('ServiceType','like','%'.$ServiceType.'%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->whereIn('ServiceID',$members)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('ServiceID','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif (!$ServiceType && $ServiceArea) {
            $services = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orWhere('ServiceArea','like','%全国%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->whereIn('ServiceID',$members)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && $ServiceArea) {
            $services1 = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orWhere('ServiceArea','like','%全国%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID')->toArray();
            $services2 = Service::where('ServiceType','like','%'.$ServiceType.'%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->whereIn('ServiceID',$members)->lists('ServiceID')->toArray();
            $services = array_intersect($services1, $services2);
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('ServiceID','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        }

        $data = [];
        foreach ($services as $id) {
            $item = $this->getInfo($id);
            $item['ServiceNumber'] = 'FW' . sprintf("%05d", $item['ServiceID']);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
        // return json_encode([['test'=>1],['test'=>2]]);
        // return $this->response->array([['test'=>1],['test'=>2]]);
    }

    /**
    *基础服务商详情
    *
    */
    public function getInfo($id)
    {   
        $service = Service::where('T_U_SERVICEINFO.ServiceID',$id)->first()->toArray();
        // $service['ServiceID'] = 'FW' . sprintf("%05d", $service['ServiceID']);
        $type = explode(',', $service['ServiceType']);
        $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
        $conumber = DB::table('T_P_RUSHPROJECT')->where(['ServiceID' => $id, 'CooperateFlag' => 1])->count();
        $service['ServiceType'] = implode('、', $type);
        $servicearea = explode(' ', $service['ServiceArea']);

        //收藏人数统计
        $CollectCount = DB::table('T_P_COLLECTION')->where(['Type'=>4, 'ItemID'=>$id])->count();
        $service['CollectCount'] = $CollectCount;

        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $service['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $id, 'UserID' => $UserID])->get();
             if ($tmp) {
                $service['CollectFlag'] = 1;
             } else {
                $service['CollectFlag'] = 0;
             }
        }

        $service['showlevel'] = explode(',',$service['Level']);
        $UserID = Service::where('ServiceID',$id)->pluck('UserID');
        $star = ['1'=>$this->_starState($UserID,1),'2'=>$this->_starState($UserID,2),'3'=>$this->_starState($UserID,3),'4'=>$this->_starState($UserID,4),'5'=>$this->_starState($UserID,5)];
        $service['showlevelarr'] = $star;

        $userinfo = User::where('userid',$UserID)->first()->toArray();
        $service['right'] = $userinfo['right'];
        $service['showright'] = json_decode($userinfo['showright'],true);
        $service['showrightios'] = [];
        $service['showrightarr'] = [];
        $service['showrightiosStr'] = '';
        if($service['showright']){
            $arr = array_keys($service['showright']);
            foreach ($arr as $v) {
                $service['showrightios'][] = \App\Tool::getPY($v);
            }
            $service['showrightiosStr'] = implode(',', $service['showrightios']);
            if(count($service['showright']) == 0){
                $service['showright'] = json_decode("{}");
            }
            foreach ($service['showright'] as $key => $value) {
                $service['showrightarr'][] = [$key,$value];
            }
        }else{
            $service['showright'] = '';
        }

        $service['ServiceLevel'] = 'VIP1';
        $service['CoNumber'] = $conumber;
        $picture = User::where('userid',$service['UserID'])->pluck('UserPicture');
        $service['UserPicture'] = $picture;
        return $service;
    }

    /**
    *服务商详情
    *
    */
    public function serInfo($id)
    {   
        $service = $this->getInfo($id);
        $service['ServiceNumber'] = 'FW' . sprintf("%05d", $service['ServiceID']);
        $service['created_at'] = substr($service['created_at'], 0,10);
        $service['starvideo'] = DB::table('T_U_STAR')->where(['ServiceID'=>$id, 'StarID'=>3, 'State'=>2])->pluck('Resource');
        $service['starvideo'] = $service['starvideo']? 'http://videos.ziyawang.com'.$service['starvideo']:'';
        $service['starcns'] = DB::table('T_U_STAR')->where(['ServiceID'=>$id, 'StarID'=>4, 'State'=>2])->pluck('Resource');
        $service['starcns'] = $service['starcns']? 'http://images.ziyawang.com'.$service['starcns']:'';
        $service['starsz'] = DB::table('T_U_STAR')->where(['ServiceID'=>$id, 'StarID'=>5, 'State'=>2])->pluck('Resource');
        $picarr = explode(',', $service['starsz']);
        $service['starsz'] = '';
        if($picarr[0] != ''){
            foreach ($picarr as $v) {
                $service['starsz'][] = 'http://images.ziyawang.com'.$v;
            }
        } else {
            $service['starsz'] = [];
        }

        $service['starsd'] = DB::table('T_U_STAR')->where(['ServiceID'=>$id, 'StarID'=>2, 'State'=>2])->pluck('Resource');
        $sdarr = explode(',', $service['starsd']);
        $service['starsd'] = '';
        if($sdarr[0] != ''){
            foreach ($sdarr as $v) {
                $service['starsd'][] = 'http://images.ziyawang.com'.$v;
            }
        } else {
            $service['starsd'] = [];
        }

        Service::where('ServiceID',$id)->increment('ViewCount');
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $service['CollectFlag'] = 0;


        $userId = DB::table("T_U_SERVICEINFO")->where('ServiceID',$id)->pluck('UserID');
        $counts=DB::table("T_U_MEMBER")->where("UserID",$userId)->where("PayFlag",1)->where("Over",0)->count();
        $service['insider']=0;
        if($counts){
            $service['insider']=1;
        }else{
            $service['insider']=0;
        }

        if ($UserID) {
            $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $id, 'UserID' => $UserID])->get();
            if ($tmp) {
            $service['CollectFlag'] = 1;
            } else {
            $service['CollectFlag'] = 0;
            }

            //写查看信息log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'check.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = $UserID;
            $log['type'] = 4;
            $log['itemid'] = $id;
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        } else {
            //写查看信息log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'check.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = 0;
            $log['type'] = 4;
            $log['itemid'] = $id;
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        }
        return $service;
    }

}

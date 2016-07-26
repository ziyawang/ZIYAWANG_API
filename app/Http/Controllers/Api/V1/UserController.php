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
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;
use App\Service;
use App\Project;

class UserController extends BaseController
{
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
        $ServiceType = (isset($payload['ServiceType']) && $payload['ServiceType'] != 'null' ) ?  $payload['ServiceType'] : null;
        $ServiceArea = (isset($payload['ServiceArea']) && $payload['ServiceArea'] != 'null' )?  $payload['ServiceArea'] : null;

        if(!$ServiceType && !$ServiceArea) {
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && !$ServiceArea) {
            $services = Service::where('ServiceType','like','%'.$ServiceType.'%')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif (!$ServiceType && $ServiceArea) {
            $services = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && $ServiceArea) {
            $services1 = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->lists('ServiceID')->toArray();
            $services2 = Service::where('ServiceType','like','%'.$ServiceType.'%')->lists('ServiceID')->toArray();
            $services = array_intersect($services1, $services2);
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        }

        $data = [];
        foreach ($services as $id) {
            $item = $this->getInfo($id);
            $item['ServiceNumber'] = 'FW' . sprintf("%05d", $item['ServiceID']);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data]);
        // return json_encode([['test'=>1],['test'=>2]]);
        // return $this->response->array([['test'=>1],['test'=>2]]);
    }

    /**
    *获取服务商详情
    *
    */
    public function getInfo($id)
    {   
        $service = Service::where('T_U_SERVICEINFO.ServiceID',$id)->join('T_P_SERVICECERTIFY', 'T_U_SERVICEINFO.ServiceID', '=', 'T_P_SERVICECERTIFY.ServiceID')->first()->toArray();
        // $service['ServiceID'] = 'FW' . sprintf("%05d", $service['ServiceID']);
        $type = explode(',', $service['ServiceType']);
        $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
        $conumber = DB::table('T_P_RUSHPROJECT')->where(['ServiceID' => $id, 'CooperateFlag' => 1])->count();
        $service['ServiceType'] = implode('、', $type);
        $service['ServiceLevel'] = 'VIP1';
        $service['CoNumber'] = $conumber;
        $picture = User::where('UserID',$service['UserID'])->pluck('UserPicture');
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
        Service::where('ServiceID',$id)->increment('ViewCount');
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $id, 'UserID' => $UserID])->get();
             if ($tmp) {
                $service['CollectFlag'] = 1;
             } else {
                $service['CollectFlag'] = 0;
             }
        }
        $service['CollectFlag'] = 0;
        return $service;
    }

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

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $Service = new Service();
            $Service->ServiceName = $payload['ServiceName'];
            $Service->ServiceIntroduction = $payload['ServiceIntroduction'];
            $Service->ServiceLocation = $payload['ServiceLocation'];
            $Service->ServiceType = implode(',', $payload['ServiceType']);
            $Service->ConnectPerson = $payload['ConnectPerson'];
            $Service->ConnectPhone = $payload['ConnectPhone'];
            $Service->ServiceArea = $payload['ServiceArea'];
            $Service->ConfirmationP1 = isset($payload['ConfirmationP1'])? $payload['ConfirmationP1']:'';
            $Service->ConfirmationP2 = isset($payload['ConfirmationP2'])? $payload['ConfirmationP2']:'';
            $Service->ConfirmationP3 = isset($payload['ConfirmationP3'])? $payload['ConfirmationP3']:'';
            $Service->UserID = $UserID;
            $Service->created_at = date('Y-m-d H:i:s',time());
            $res = $Service->save();
    
            DB::table("T_P_SERVICECERTIFY")->insert(['State'=>0, 'created_at'=>date('Y-m-d H:i:s',time()), 'ServiceID'=>$Service->ServiceID]);

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

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
        // 验证规则
        $rules = [
            'password' => ['required', 'min:6'],
        ];

        $payload = app('request')->only('password');
        $validator = app('validator')->make($payload, $rules);

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

    /**
    *用户发布的信息
    *
    *
    */
    public function myPro()
    {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $Project = new ProjectController();
        $UserID = $this->auth->user()->toArray()['userid'];
        $where = ['UserID'=>$UserID, 'CertifyState'=>0, 'DeleteFlag'=>0];

        $projects = Project::where($where)->lists('ProjectID');
        $counts = count($projects);
        $pages = ceil($counts/$pagecount);

        $projects = Project::where($where)->skip($skipnum)->take($pagecount)->lists('ProjectID');

        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data]);
    }


    /**
     * 抢单列表
     *
     * @param Request $request
     */
    public function proRushList($id) {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->lists('ServiceID');
        $counts = count($services);
        $pages = ceil($counts/$pagecount);
        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->skip($skipnum)->take($pagecount)->lists('ServiceID');
        $data = [];
        foreach ($services as $id) {
            $item = $this->serInfo($id);
            $data[] = $item;
        }
        
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data]);
    }

    /**
     * 确认合作
     *
     * @param Request $request
     */
    public function proCooperate() {
        $payload = app('request')->all();
        $ServiceId = $payload['Serviceid'];
        $serviceId = $this->auth->user()->toArray()['userid'];
    }

    /**
     * 取消合作
     *
     * @param Request $request
     */
    public function proCancel() {
        $payload = app('request')->all();
        $ServiceId = $payload['Serviceid'];
        $serviceId = $serviceId;

    }
}

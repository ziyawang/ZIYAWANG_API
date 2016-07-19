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
        $ServiceType = isset($payload['ServiceType']) ?  $payload['ServiceType'] : null;
        $ServiceArea = isset($payload['ServiceArea']) ?  $payload['ServiceArea'] : null;
        $where = ['ServiceType'=>$ServiceType, 'ServiceArea'=>$ServiceArea];

        $services = Service::skip($skipnum)->take($pagecount)->get()->toArray();
        dd($services);
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

    /**
    *用户发布信息
    *
    *
    */
    public function myPro()
    {
        $PROJECT = new ProjectController();
        $UserID = $this->auth->user()->toArray()['userid'];
        $where = ['UserID'=>$UserID, 'CertifyState'=>0, 'DeleteFlag'=>0];

        $projects = Project::where($where)->lists('ProjectID');
        $data = [];
        foreach ($projects as $pro) {
            $item = $PROJECT->getInfo($pro);
            $data[] = $item;
        }
        dd($data);
    }


    /**
     * 抢单列表
     *
     * @param Request $request
     */
    public function proRushList($id) {
        $lists = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->lists('ServiceID');
        dd($lists);
        $ServiceID = $this->auth->user()->toArray()['userid'];

        
        return '抢单成功';
    }

    /**
     * 确认合作
     *
     * @param Request $request
     */
    public function proCooperate() {
        $payload = app('request')->all();
        $projectId = $payload['projectid'];
        $serviceId = $this->auth->user()->toArray()['userid'];
    }

    /**
     * 取消合作
     *
     * @param Request $request
     */
    public function proCancel() {
        $payload = app('request')->all();
        $projectId = $payload['projectid'];
        $serviceId = $serviceId;

    }
}

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
        $ServiceType = (isset($payload['ServiceType']) && $payload['ServiceType'] != 'null' && $payload['ServiceType'] != '') ?  $payload['ServiceType'] : null;
        $ServiceArea = (isset($payload['ServiceArea']) && $payload['ServiceArea'] != 'null' && $payload['ServiceArea'] != '')?  $payload['ServiceArea'] : null;
        
        if(!$ServiceType && !$ServiceArea) {
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->orderBy('updated_at','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && !$ServiceArea) {
            $services = Service::where('ServiceType','like','%'.$ServiceType.'%')->orderBy('updated_at','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('updated_at','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif (!$ServiceType && $ServiceArea) {
            $services = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orwhere('ServiceArea','like','%全国%')->orderBy('updated_at','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('updated_at','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && $ServiceArea) {
            $services1 = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orwhere('ServiceArea','like','%全国%')->orderBy('updated_at','desc')->lists('ServiceID')->toArray();
            $services2 = Service::where('ServiceType','like','%'.$ServiceType.'%')->orderBy('updated_at','desc')->lists('ServiceID')->toArray();
            $services = array_intersect($services1, $services2);
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('updated_at','desc')->lists('ServiceID');
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
    *获取服务商详情
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
        $service['ServiceLevel'] = 'VIP1';
        $service['CoNumber'] = $conumber;
        $picture = User::where('UserID',$service['UserID'])->pluck('UserPicture');
        $service['UserPicture'] = $picture;
        return $service;
    }



    /**
    *获取服务商详情通过USERID
    *
    */
    public function getInfouid($id)
    {   
        $service = Service::where('T_U_SERVICEINFO.UserID',$id)->first()->toArray();
        // $service['ServiceID'] = 'FW' . sprintf("%05d", $service['ServiceID']);
        $type = explode(',', $service['ServiceType']);
        $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
        $conumber = DB::table('T_P_RUSHPROJECT')->where(['ServiceID' => $service['ServiceID'], 'CooperateFlag' => 1])->count();
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
        $service['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $id, 'UserID' => $UserID])->get();
             if ($tmp) {
                $service['CollectFlag'] = 1;
             } else {
                $service['CollectFlag'] = 0;
             }
        }
        return $service;
    }

    /**
    *服务商详情
    *
    */
    public function getServiceInfo($id)
    {   
        $service = $this->getInfouid($id);
        $service['ServiceNumber'] = 'FW' . sprintf("%05d", $service['ServiceID']);
        Service::where('UserID',$id)->increment('ViewCount');
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $service['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $service['ServiceID'], 'UserID' => $UserID])->get();
             if ($tmp) {
                $service['CollectFlag'] = 1;
             } else {
                $service['CollectFlag'] = 0;
             }
        }
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
        $UserID = $this->auth->user()->toArray()['userid'];
        $role = Service::where('UserID', $UserID)->first();
        // dd($role);
        if($role){
            $type = $role->ServiceType;
            $type = explode(',', $type);
            $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
            $role->ServiceType = implode('、', $type);

            return $this->response->array(['user'=>$this->auth->user(),'service'=>$role]);
        } else {
            $this->response->array(['user'=>$this->auth->user()]);
        }
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

        $tmp = Service::where('UserID', $UserID)->count();
        if($tmp > 0){
            return $this->response->array(['status_code'=>'200', 'msg'=>'修改资料请联系客服！']);
        }

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

            $UserPicture = isset($payload['UserPicture']) ? $payload['UserPicture']:'/user/defaltoux.jgp';
            User::where('userid', $UserID)->update(['UserPicture'=>$UserPicture]);
            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        // 发送结果
        if ($res) {

            //给客服发送邮件 有用户完成服务方注册
            $servicename = $payload['ServiceName'];
            $email = '3153679024@qq.com';    
            $title = '新服务方完善资料，请查看！';
            $message = '公司名为' . $servicename . '完善成为服务方，请及时审核！';

            $this->_sendMail($email, $title, $message);

            return $this->response->array(['status_code' => '200', 'role' => '1', 'success' => 'Service Confirm Success']);
        } else {
            return $this->response->array(['error' => 'Service Confirm Error']);
        }
    }

    /**
     * 服务方信息修改
     *
     * @return mixed
     */
    public function reconfirm()
    {   
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];

        $Service = Service::where('UserID', $UserID)->first();

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
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

            DB::table('T_P_SERVICECERTIFY')->where('ServiceID',$Service->ServiceID)->update(['State'=>0]);
            $UserPicture = isset($payload['UserPicture'])? $payload['UserPicture']:'/user/defaltoux.jgp';
            User::where('userid', $UserID)->update(['UserPicture'=>$UserPicture]);
            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        // 发送结果
        if ($res) {

            //给客服发送邮件 有用户完成服务方注册
            $servicename = $payload['ServiceName'];
            $email = '3153679024@qq.com';    
            $title = '服务方重新提交资料，请查看！';
            $message = '公司名为' . $servicename . '重新提交资料，请及时审核！';

            $this->_sendMail($email, $title, $message);

            return $this->response->array(['status_code' => '200', 'role' => '1', 'success' => 'Service Confirm Success']);
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
        $where = ['UserID'=>$UserID, 'DeleteFlag'=>0];

        $projects = Project::where($where)->orderBy('updated_at','desc')->lists('ProjectID');
        $counts = count($projects);
        $pages = ceil($counts/$pagecount);

        $projects = Project::where($where)->skip($skipnum)->take($pagecount)->orderBy('updated_at','desc')->lists('ProjectID');

        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }


    /**
     * 抢单列表
     *
     * @param Request $request
     */
    public function proRushList($id) {
        $UserID = $this->auth->user()->toArray()['userid'];

        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->lists('ServiceID');
        $counts = count($services);
        $pages = ceil($counts/$pagecount);
        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->skip($skipnum)->take($pagecount)->orderBy('updated_at','desc')->lists('ServiceID');
        $data = [];
        // dd($services);
        foreach ($services as $sid) {
            $item = $this->serInfo($sid);
            $item['RushTime'] = DB::table('T_P_RUSHPROJECT')->where(['ProjectID' => $id, 'ServiceID' => $sid])->pluck('RushTime');
            $item['RushTime'] = substr($item['RushTime'], 0,10);
            $data[] = $item;
        }
        
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    /**
     * 确认合作
     *
     * @param Request $request
     */
    public function proCooperate() {
        $payload = app('request')->all();
        $ServiceID = $payload['ServiceID'];
        $ProjectID = $payload['ProjectID'];
        $UserID = $this->auth->user()->toArray()['userid'];
        DB::beginTransaction();
        try {
            Project::where('ProjectID', $ProjectID)->update(['PublishState'=>1, 'ServiceID'=>$ServiceID]);
            DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID])->update(['CooperateFlag'=>1]);
            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }
        // 项目合作成功
        if (!isset($e)) {
            return $this->response->array(['status_code' => '200', 'msg' => 'Coopearte Success']);
        } else {
            return $this->response->array(['status_code' => '407', 'msg' => 'Coopearte Error']);
        }
    }

    /**
     * 取消合作
     *
     * @param Request $request
     */
    public function proCancel() {
        $payload = app('request')->all();
        $ProjectID = $payload['ProjectID'];
        $UserID = $this->auth->user()->toArray()['userid'];
        DB::beginTransaction();
        try {
            Project::where('ProjectID', $ProjectID)->update(['PublishState'=>2, 'ServiceID'=>0]);
            DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'CooperateFlag'=>1])->update(['CooperateFlag'=>2]);
            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        // 项目合作成功
        if (!isset($e)) {
            return $this->response->array(['status_code' => '200', 'msg' => 'Cancel Success']);
        } else {
            return $this->response->array(['status_code' => '408', 'msg' => 'Cancel Error']);
        }
    }

    /**
     * 我合作的列表
     *
     * @param Request $request
     */
    public function cooList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $Project = new ProjectController();

        $UserID = $this->auth->user()->toArray()['userid'];

        $isser = Service::where('UserID',$UserID)->get();
        if($isser){
            $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
            $projects = Project::where(['PublishState' => 1, "ServiceID" => $ServiceID])->orderBy('updated_at','desc')->lists('ProjectID');
            $counts = count($projects);
            $pages = ceil($counts/$pagecount);

            $projects = Project::skip($skipnum)->take($pagecount)->where(['PublishState' => 1, "ServiceID" => $ServiceID])->orderBy('updated_at','desc')->lists('ProjectID');
        } else {
            $projects = Project::where(['PublishState' => 1, "UserID" => $UserID])->orderBy('updated_at','desc')->lists('ProjectID');
            $counts = count($projects);
            $pages = ceil($counts/$pagecount);

            $projects = Project::skip($skipnum)->take($pagecount)->where(['PublishState' => 1, "UserID" => $UserID])->orderBy('updated_at','desc')->lists('ProjectID');
        }



        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);

    }

    /**
     * 我的抢单
     *
     * @param Request $request
     */
    public function rushList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $Project = new ProjectController();
        $UserID = $this->auth->user()->toArray()['userid'];
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
        $projects = DB::table('T_P_RUSHPROJECT')->where('ServiceID',$ServiceID)->orderBy('updated_at','desc')->lists('ProjectID');
        $counts = count($projects);
        $pages = ceil($counts/$pagecount);

        $projects = DB::table('T_P_RUSHPROJECT')->skip($skipnum)->take($pagecount)->where("ServiceID", $ServiceID)->orderBy('updated_at','desc')->lists('ProjectID');

        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);

    }



}

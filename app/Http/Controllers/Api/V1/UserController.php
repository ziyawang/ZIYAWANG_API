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
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->where('ServiceID', '<>', '839')->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && !$ServiceArea) {
            $services = Service::where('ServiceType','like','%'.$ServiceType.'%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('ServiceID','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif (!$ServiceType && $ServiceArea) {
            $services = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orWhere('ServiceArea','like','%全国%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->lists('ServiceID');
            $counts = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->count();
            $services = DB::table('T_P_SERVICECERTIFY')->skip($skipnum)->take($pagecount)->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->orderBy('ServiceID','desc')->lists('ServiceID');
            $pages = ceil($counts/$pagecount);
        } elseif ($ServiceType && $ServiceArea) {
            $services1 = Service::where('ServiceArea','like','%'.$ServiceArea.'%')->orWhere('ServiceArea','like','%全国%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->lists('ServiceID')->toArray();
            $services2 = Service::where('ServiceType','like','%'.$ServiceType.'%')->where('ServiceID', '<>', '839')->orderBy('ServiceID','desc')->lists('ServiceID')->toArray();
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
        $servicearea = explode(' ', $service['ServiceArea']);
        // $areacount = count($servicearea);
        // if($areacount > 5){
        //     $service['ServiceArea'] = '全国';
        // }

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

        $service['ServiceLevel'] = 'VIP1';
        $service['CoNumber'] = $conumber;
        $picture = User::where('userid',$service['UserID'])->pluck('UserPicture');
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
        $servicearea = explode(' ', $service['ServiceArea']);
        $areacount = count($servicearea);
        if($areacount > 5){
            $service['ServiceArea'] = '全国';
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
                $usrIds=DB::table("T_U_SERVICEINFO")->select("UserID")->where('ServiceID',$id)->get();
                foreach($usrIds as $value){
                    $userId=$value->UserID;
                }
            $counts=DB::table("T_U_MEMBER")->where("UserID",$userId)->where("PayFlag",1)->where("Over",0)->count();
             $service['insider']=0;
             if($counts){
                $service['insider']=1;
             }else{
                  $service['insider']=0;
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
        $MyProCount = Project::where('UserID', $UserID)->where('CertifyState', '<>', 3)->where('DeleteFlag', 0)->count();
        $MyColCount = DB::table('T_P_COLLECTION')->where('UserID', $UserID)->count();
        $MyMsgCount = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID,'Status'=>0])->count();

        // dd($role);
        if($role){
            $hascertify = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$UserID, 'T_P_SERVICECERTIFY.State'=>1])->count();
            $type = $role->ServiceType;
            $type = explode(',', $type);
            $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
            $role->ServiceType = implode('、', $type);
            if($hascertify == 0){
                $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->count();
                return $this->response->array(['user'=>$this->auth->user(),'role'=>'2','service'=>$role,'MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
            } else {
                $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->orWhere(['PublishState'=>1,'ServiceID'=>$role['ServiceID']])->count();
                return $this->response->array(['user'=>$this->auth->user(),'role'=>'1','service'=>$role,'MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
            }
        } else {
            $MyCooCount = Project::where(['PublishState'=>1,'UserID'=>$UserID])->count();
            return $this->response->array(['user'=>$this->auth->user(),'status_code'=>'200','role'=>'0','MyProCount'=>$MyProCount,'MyColCount'=>$MyColCount,'MyCooCount'=>$MyCooCount,'MyMsgCount'=>$MyMsgCount]);
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
            $Service->Size = $payload['Size'];
            $Service->RegTime = $payload['RegTime'];
            $Service->Founds = $payload['Founds'];
            $Service->created_at = date('Y-m-d H:i:s',time());
            $res = $Service->save();
    
            DB::table("T_P_SERVICECERTIFY")->insert(['State'=>0, 'created_at'=>date('Y-m-d H:i:s',time()), 'ServiceID'=>$Service->ServiceID]);

            $UserPicture = isset($payload['UserPicture']) ? $payload['UserPicture']:'/user/defaltoux.jpg';
            if($UserPicture == ''){
                $UserPicture = '/user/defaltoux.jpg';
            }
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

            // $this->_sendMail($email, $title, $message);  发送邮件

            return $this->response->array(['status_code' => '200', 'role' => '2', 'success' => 'Service Confirm Success']);
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
            $Service->Size = $payload['Size'];
            $Service->RegTime = $payload['RegTime'];
            $Service->Founds = $payload['Founds'];
            $Service->created_at = date('Y-m-d H:i:s',time());
            $res = $Service->save();

            DB::table('T_P_SERVICECERTIFY')->where('ServiceID',$Service->ServiceID)->update(['State'=>0]);
            $UserPicture = isset($payload['UserPicture'])? $payload['UserPicture']:'/user/defaltoux.jpg';
            if($UserPicture == ''){
                $UserPicture = '/user/defaltoux.jpg';
            }
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

            // $this->_sendMail($email, $title, $message);   发送邮件

            return $this->response->array(['status_code' => '200', 'role' => '2', 'success' => 'Service Confirm Success']);
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
            return $this->response->array(['status_code'=>'200','msg' => 'Password Change Success']);
        } else {
            return $this->response->array(['status_code'=>'410','msg' => 'Password Change Error']);
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

        $Project = new \App\Http\Controllers\Api\V2\ProjectController();
        $UserID = $this->auth->user()->toArray()['userid'];
        $where = ['UserID'=>$UserID, 'DeleteFlag'=>0];

        $projects = Project::where($where)->where('CertifyState','<>',3)->lists('ProjectID');
        $counts = count($projects);
        $pages = ceil($counts/$pagecount);

        $projects = Project::where($where)->where('CertifyState','<>',3)->skip($skipnum)->take($pagecount)->orderBy('PublishTime','desc')->lists('ProjectID');

        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['Corpore'] = isset($item['Corpore']) ? $item['Corpore'] : '';
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
            // $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $item['InvestType'] = isset($item['InvestType']) ? $item['InvestType'] : '';
            $item['Year'] = isset($item['Year']) ? $item['Year'] : '';
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

        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->whereIn('CooperateFlag',[0,1,2])->lists('ServiceID');
        $counts = count($services);
        $pages = ceil($counts/$pagecount);
        $services = DB::table('T_P_RUSHPROJECT')->where('ProjectID',$id)->whereIn('CooperateFlag',[0,1,2])->skip($skipnum)->take($pagecount)->orderBy('RushTime','desc')->lists('ServiceID');
        $data = [];
        // dd($services);
        foreach ($services as $sid) {
            $item = $this->serInfo($sid);
            $item['RushTime'] = DB::table('T_P_RUSHPROJECT')->where(['ProjectID' => $id, 'ServiceID' => $sid])->pluck('RushTime');
            $item['CooperateFlag'] = DB::table('T_P_RUSHPROJECT')->where(['ProjectID' => $id, 'ServiceID' => $sid])->pluck('CooperateFlag');
            $item['RushTime'] = substr($item['RushTime'], 0,10);
            $item['CollectFlag'] = 0;
            $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 4, 'ItemID' => $sid, 'UserID' => $UserID])->get();
            if ($tmp) {
                $item['CollectFlag'] = 1;
            } else {
                $item['CollectFlag'] = 0;
            }

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
        $project = Project::where('ProjectID', $ProjectID)->first();
        if($project->PublishState == 0){
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
                //编写消息
                $Text = [];
                $Message = [];
                $Text['Title'] = '您有新的合作！';
                $Text['Text'] = '您申请的编号为:' . 'FB' . sprintf("%05d", $ProjectID) . '， 发布方已选择与您合作。您可以到<我的合作>中查看信息！或者直接点击此链接，查看信息的详情。<a href="http://ziyawang.com/project/' . $ProjectID . '">http://ziyawang.com/project/'.$ProjectID.'</a>。如果不能点击，请复制此链接，在浏览器地址栏粘贴访问。';
                $Text['Time'] = date("Y-m-d H:i:s",strtotime('now'));
                //发送消息
                $Message['TextID'] = DB::table('T_M_MESSAGETEXT')->insertGetId($Text);
                $Message['SendID'] = 0;
                $Message['RecID'] = Service::where('ServiceID', $ServiceID)->pluck('UserID');
                $Message['Status'] = 0;
                DB::table('T_M_MESSAGE')->insert($Message);


                return $this->response->array(['status_code' => '200', 'msg' => 'Coopearte Success']);
            } else {
                return $this->response->array(['status_code' => '407', 'msg' => 'Coopearte Error']);
            }
        } else if($project->PublishState == 1){
            return $this->response->array(['status_code' => '414', 'msg' => 'Coopearte has Done']);
        } else if($project->PublishState == 2){
            return $this->response->array(['status_code' => '415', 'msg' => 'Coopearte is Canceling']);
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
            Project::where('ProjectID', $ProjectID)->update(['PublishState'=>2]);
            DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'CooperateFlag'=>1])->update(['CooperateFlag'=>2]);
            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        // 项目取消成功
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
            $projects = Project::where(["ServiceID"=>$ServiceID, 'PublishState'=>1])->orWhere(["ServiceID"=>$ServiceID, 'PublishState'=>2])->orWhere(['UserID'=>$UserID, 'PublishState'=>1])->orWhere(['UserID'=>$UserID, 'PublishState'=>2])->lists('ProjectID');
            $counts = count($projects);
            $pages = ceil($counts/$pagecount);

            $projects = Project::where(["ServiceID"=>$ServiceID, 'PublishState'=>1])->orWhere(["ServiceID"=>$ServiceID, 'PublishState'=>2])->orWhere(['UserID'=>$UserID, 'PublishState'=>1])->orWhere(['UserID'=>$UserID, 'PublishState'=>2])->orderBy('PublishTime','desc')->skip($skipnum)->take($pagecount)->lists('ProjectID');
        } else {
            $projects = Project::where("UserID", $UserID)->whereIn('PublishState', [1,2])->lists('ProjectID');
            $counts = count($projects);
            $pages = ceil($counts/$pagecount);

            $projects = Project::where("UserID", $UserID)->whereIn('PublishState', [1,2])->orderBy('PublishTime','desc')->skip($skipnum)->take($pagecount)->lists('ProjectID');
        }



        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['Corpore'] = isset($item['Corpore']) ? $item['Corpore'] : '';
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
            $item['InvestType'] = isset($item['InvestType']) ? $item['InvestType'] : '';
            $item['Year'] = isset($item['Year']) ? $item['Year'] : '';
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

        $Project = new \App\Http\Controllers\Api\V2\ProjectController();
        $UserID = $this->auth->user()->toArray()['userid'];
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
        $projects = DB::table('T_P_RUSHPROJECT')->where('ServiceID',$ServiceID)->where('CooperateFlag','0')->orderBy('RushTime','desc')->lists('ProjectID');
        $counts = count($projects);
        $pages = ceil($counts/$pagecount);

        $projects = DB::table('T_P_RUSHPROJECT')->skip($skipnum)->take($pagecount)->where('CooperateFlag','0')->where("ServiceID", $ServiceID)->orderBy('RushTime','desc')->lists('ProjectID');

        $data = [];
        foreach ($projects as $pro) {
            $item = $Project->getInfo($pro);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['Corpore'] = isset($item['Corpore']) ? $item['Corpore'] : '';
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
            $item['InvestType'] = isset($item['InvestType']) ? $item['InvestType'] : '';
            $item['Year'] = isset($item['Year']) ? $item['Year'] : '';
            $item['RushTime'] = DB::table('T_P_RUSHPROJECT')->where(['ServiceID'=>$ServiceID,'ProjectID'=>$pro])->pluck('RushTime');
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);

    }

  /**
     * app服务方信息完善
     *
     * @return mixed
     */
    public function appConfirm()
    {   
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        $payload['ServiceType'] = str_replace(',,', ',', $payload['ServiceType']);

        $tmp = Service::where('UserID', $UserID)->count();
        if($tmp > 0){
            return $this->response->array(['status_code'=>'200', 'msg'=>'修改资料请联系客服！']);
        }
        $image_path=dirname(base_path()).'/ziyaupload/images/services/';
        if(!is_dir($image_path)){  
            mkdir($image_path,0777,true);  
        }  
        foreach($_FILES as  $key=>$file){
            if(isset($_FILES[$key])){
                $baseName=basename($file['name']);
                $extension=strrchr($baseName, ".");
                $newName=time() . mt_rand(1000, 9999).$extension;
                $target_path = $image_path . $newName;  
                $filePath="/services/".$newName;
                if(move_uploaded_file($_FILES[$key]["tmp_name"],$target_path)){
                    $payload[$key]=$filePath;
                }else{
                    return $this->response->array(['status_code' => '480','msg'=>"文件上传失败"]);
                } 
            }
        }

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $Service = new Service();
            $Service->ServiceName = $payload['ServiceName'];
            $Service->ServiceIntroduction = $payload['ServiceIntroduction'];
            $Service->ServiceLocation = $payload['ServiceLocation'];
            $Service->ServiceType =$payload['ServiceType'];
            $Service->ConnectPerson = $payload['ConnectPerson'];
            $Service->ConnectPhone = $payload['ConnectPhone'];
            $Service->ServiceArea = $payload['ServiceArea'];
            $Service->ConfirmationP1 = isset($payload['ConfirmationP1'])? $payload['ConfirmationP1']:'';
            $Service->ConfirmationP2 = isset($payload['ConfirmationP2'])? $payload['ConfirmationP2']:'';
            $Service->ConfirmationP3 = isset($payload['ConfirmationP3'])? $payload['ConfirmationP3']:'';
            $Service->UserID = $UserID;
            $Service->Size = $payload['Size'];
            $Service->RegTime = $payload['RegTime'];
            $Service->Founds = $payload['Founds'];
            $Service->created_at = date('Y-m-d H:i:s',time());
            $res = $Service->save();
    
            DB::table("T_P_SERVICECERTIFY")->insert(['State'=>0, 'created_at'=>date('Y-m-d H:i:s',time()), 'ServiceID'=>$Service->ServiceID]);

            // $UserPicture = isset($payload['UserPicture']) ? $payload['UserPicture']:'/user/defaltoux.jpg';
            // User::where('userid', $UserID)->update(['UserPicture'=>$UserPicture]);
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

            // $this->_sendMail($email, $title, $message);

            return $this->response->array(['status_code' => '200', 'role' => '2', 'success' => 'Service Confirm Success']);
        } else {
            return $this->response->array(['error' => 'Service Confirm Error']);
        }
    }

     /**
     * app服务方信息修改
     *
     * @return mixed
     */
    public function  appReconfirm()
    {   
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        $payload['ServiceType'] = str_replace(',,', ',', $payload['ServiceType']);

        $Service = Service::where('UserID', $UserID)->first();
        $image_path =  dirname(base_path()).'/ziyaupload/images/services/';
        if(!is_dir($image_path)){  
            mkdir($image_path,0777,true);  
        }  
        foreach($_FILES as  $key=>$file){
            if(isset($_FILES[$key])){
                $baseName=basename($file['name']);
                $extension=strrchr($baseName, ".");
                $newName=time() . mt_rand(1000, 9999).$extension;
                $target_path = $image_path . $newName;  
                $filePath="/services/".$newName;

                if(move_uploaded_file($_FILES[$key]["tmp_name"],$target_path)){
                    $payload[$key]=$filePath;
                }else{
                    return $this->response->array(['status_code' => '480','msg'=>"文件上传失败"]);
                } 
            }
        }

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $Service->ServiceName = $payload['ServiceName'];
            $Service->ServiceIntroduction = $payload['ServiceIntroduction'];
            $Service->ServiceLocation = $payload['ServiceLocation'];
            $Service->ServiceType =$payload['ServiceType'];
            $Service->ConnectPerson = $payload['ConnectPerson'];
            $Service->ConnectPhone = $payload['ConnectPhone'];
            $Service->ServiceArea = $payload['ServiceArea'];
            $Service->ConfirmationP1 = isset($payload['ConfirmationP1'])? $payload['ConfirmationP1']:'';
            $Service->ConfirmationP2 = isset($payload['ConfirmationP2'])? $payload['ConfirmationP2']:'';
            $Service->ConfirmationP3 = isset($payload['ConfirmationP3'])? $payload['ConfirmationP3']:'';
            $Service->UserID = $UserID;
            $Service->Size = $payload['Size'];
            $Service->RegTime = $payload['RegTime'];
            $Service->Founds = $payload['Founds'];
            $Service->created_at = date('Y-m-d H:i:s',time());
            $res = $Service->save();

            DB::table('T_P_SERVICECERTIFY')->where('ServiceID',$Service->ServiceID)->update(['State'=>0]);
            // $UserPicture = isset($payload['UserPicture'])? $payload['UserPicture']:'/user/defaltoux.jpg';
            // User::where('userid', $UserID)->update(['UserPicture'=>$UserPicture]);
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

            // $this->_sendMail($email, $title, $message);

            return $this->response->array(['status_code' => '200', 'role' => '2', 'success' => 'Service Confirm Success']);
        } else {
            return $this->response->array(['error' => 'Service Confirm Error']);
        }
    }

    public function chpicture(){
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        $UserPicture = $payload['UserPicture'];
        $res = User::where('userid', $UserID)->update(['UserPicture'=>$UserPicture]);
        if($res){
            return $this->response->array(['status_code'=>'200', 'msg'=>'头像修改成功！']);
        } else {
            return $this->response->array(['status_code'=>'409', 'msg'=>'头像修改失败！']);
        }
    }

    /*
        App意见反馈与帮助
    */
    public function advice(){
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        if(isset($_FILES['Picture'])){
            $image_path=dirname(base_path()).'/ziyaupload/images/feedback/';
            if(!is_dir($image_path)){  
                mkdir($image_path,0777,true);  
            } 
            $baseName=basename($_FILES['Picture']['name'] );
            $extension=strrchr($baseName, ".");
            $newName=time() . mt_rand(1000, 9999).$extension;
            $target_path = $image_path . $newName;  
            $filePath="/feedback/".$newName;
            if(move_uploaded_file($_FILES['Picture']['tmp_name'], $target_path)){
                $payload['Picture']=$filePath;
                $dbs = DB::table("T_U_FEEDBACK")->insert([
                    "UserID"=>$UserID,
                    "Content"=>$payload['Content'],
                    "Picture"=>$payload['Picture']
                ]);
                if($dbs){
                    return $this->response->array(['status_code'=>'200','success' => 'insert feedback Success']); 
                }else{
                    return $this->response->array(['status_code'=>'412','success' => 'insert feedback Fail']); 
                }
            }else{
                return $this->response->array(['status_code' => '480','msg'=>"文件上传失败"]);   
            }
        }else{
            $dbs = DB::table("T_U_FEEDBACK")->insert([
            "UserID"=>$UserID,
            "Content"=>$payload['Content'],
            ]);
            if($dbs){
                return $this->response->array(['status_code'=>'200','success' => 'insert feedback Success']); 
            }else{
                return $this->response->array(['status_code'=>'412','success' => 'insert feedback Fail']); 
            }
        }
    }


    /**
     * RONGYUN个人信息
     *
     * @return mixed
     */
    public function userInfo(){
        $payload = app('request')->all();
        $UserID = $payload['UserID'];
        $data = [];
        $data['ServiceName'] = '';

        $hascertify = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$UserID, 'T_P_SERVICECERTIFY.State'=>1])->count();

        $isservice = Service::where('UserID', $UserID)->count();

        if($isservice == 0 && $hascertify == 0){
            $data['role'] = 0;
            $data['phonenumber'] = User::where('userid', $UserID)->pluck('phonenumber');
            $data['UserName'] = substr_replace($data['phonenumber'],'****',3,4);
        } elseif( $isservice == 1 && $hascertify == 0) {
            $data['role'] = 2;
            $data['phonenumber'] = User::where('userid', $UserID)->pluck('phonenumber');
            $data['UserName'] = substr_replace($data['phonenumber'],'****',3,4);
        } elseif( $isservice == 1 && $hascertify == 1) {
            $data['role'] = 1;
            $data['ServiceName'] = Service::where('UserID', $UserID)->pluck('ServiceName');
            $data['UserName'] = $data['ServiceName'];
        }

        $data['UserPicture'] = User::where('userid', $UserID)->pluck('UserPicture');
        $data['username'] = User::where('userid', $UserID)->pluck('username');

        return $this->response->array(['status_code' => '200', 'data'=>$data]);
    }

}

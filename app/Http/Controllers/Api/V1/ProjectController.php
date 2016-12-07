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
use App\Project;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;

class ProjectController extends BaseController
{

    /**
     * 项目发布
     *
     * @param Request $request
     */
    public function create()
    {
        // return $this->response->array(['success' => 'Create Pro Success']);
        // 验证规则
        $rules = [
            'TypeID' => ['required'],
            'password' => ['required', 'min:6'],
            'smscode' => ['required', 'min:6']
        ];

        // $validator = app('validator')->make($payload, $rules);

        // // 验证格式
        // if ($validator->fails()) {
        //     return $this->response->array(['error' => $validator->errors()]);
        // }

        $payload = app('request')->all();
        $Channel = isset($payload['Channel'])?$payload['Channel']:'PC';
        // $payload = app('request')->except('a');
        $proType = $payload['TypeID'];
        $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$proType)->pluck('TableName');
        $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','token','access_token','UserID');
        // dd($diffData);
        if($proType == 9 || $proType == '09' || $diffTableName == 'T_P_SPEC09') {
            $diffData['TotalMoney'] = $diffData['TotalMoney']/10000;
        }

        $user = $this->auth->user()->toArray();

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $project = new Project();

            // $project->UserID = 1;
            $project->UserID = $user['userid'];
            $project->TypeID = $payload['TypeID'];
            $project->ProArea = $payload['ProArea'];
            $project->WordDes = $payload['WordDes'];
            $project->VoiceDes = $payload['VoiceDes'];
            $project->PictureDes1 = $payload['PictureDes1'];
            $project->PictureDes2 = $payload['PictureDes2'];
            $project->PictureDes3 = $payload['PictureDes3'];
            $project->PublishTime = date('Y-m-d H:i:s',time());
            $project->Channel = $Channel;
            $project->save();
            
            // DB::table('T_P_PROJECTINFO')->create(['TypeID' => 1,'UserID' => 1, 'aaa' => '111']) db不能用create方法 公共的info表可以直接用create方法 spec要用排除没用的数据方法
            $diffData['ProjectID'] = $project->ProjectID;
            DB::table("$diffTableName")->insert($diffData);
            DB::table("T_P_PROJECTCERTIFY")->insert(['State'=>0, 'created_at'=>date('Y-m-d H:i:s',time()), 'ProjectID'=>$project->ProjectID]);

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        // 创建项目成功
        if (!isset($e)) {
            //编写消息
            $Text = [];
            $Message = [];
            $Text['Title'] = '温馨提示';
            $Text['Text'] = '资芽网温馨提示，任何关于合作的前期收费要求，请慎重选择，如有疑问，请咨询资芽网客服：400-8988-557。';
            $Text['Time'] = date("Y-m-d H:i:s",strtotime('now'));
            //发送消息
            $Message['TextID'] = DB::table('T_M_MESSAGETEXT')->insertGetId($Text);
            $Message['SendID'] = 0;
            $Message['RecID'] = $user['userid'];
            $Message['Status'] = 0;
            DB::table('T_M_MESSAGE')->insert($Message);
            //发送短信
            if($user['phonenumber'] != "18721748010"){
                require(base_path().'/vendor/alidayu/TopSdk.php');
                date_default_timezone_set('Asia/Shanghai');

                $c = new \TopClient;
                $c->appkey = '23401348';//需要加引号
                $c->secretKey = env('ALIDAYU_APPSECRET');
                $c->format = 'xml';
                $req = new \AlibabaAliqinFcSmsNumSendRequest;
                $req->setExtend("");//暂时不填
                $req->setSmsType("normal");//默认可用
                $req->setSmsFreeSignName("资芽网");//设置短信免费符号名(需在阿里认证中有记录的)
                $req->setSmsParam('');//设置短信参数
                $req->setRecNum($user['phonenumber']);//设置接受手机号
                $req->setSmsTemplateCode("SMS_21720318");
                $resp = $c->execute($req);//执行
            }

            return $this->response->array(['success' => 'Create Pro Success']);
        } else {
            return $this->response->array(['error' => 'Create Pro Error']);
        }
    }

    /**
     * 项目列表
     *
     * @param Request $request
     */
    public function proList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $TypeID = (isset($payload['TypeID']) && $payload['TypeID'] != 'null' &&  $payload['TypeID'] != '' ) ?  $payload['TypeID'] : null;
        $ProArea = (isset($payload['ProArea']) && $payload['ProArea'] != 'null' && $payload['ProArea'] != '' ) ?  $payload['ProArea'] : null;
        // $Vip = (isset($payload['Vip']) && $payload['Vip'] != 'null' && $payload['Vip'] != '' ) ?  $payload['Vip'] : 'default';
        // if($Vip == 'default' || $Vip == 'null' || $Vip == '') {
        //     $Vip = [0,1];
        // } else {
        //     $Vip = array($Vip);
        // }
        $Vip = [0];

        $where = app('request')->except('startpage','pagecount','access_token','TypeID', 'ProArea', '_', 'Vip','token');


        $Type = [1,6,12];
        if(!$TypeID && !$ProArea) {
            $projects = Project::skip($skipnum)->take($pagecount)->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $counts = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && !$ProArea) {
            if(count($where) == 0){
                $projects = Project::where('TypeID',$TypeID)->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            } else {
                $projects = Project::where('TypeID',$TypeID)->where('PublishState','<>','2')->whereIn('Member',$Vip)->where('CertifyState',1)->lists('ProjectID');
                $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            }
        } elseif (!$TypeID && $ProArea) {
            $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->where('CertifyState',1)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && $ProArea) {
            $projects = Project::where('TypeID',$TypeID)->where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->whereIn('Member',$Vip)->where('CertifyState',1)->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
            $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->where('PublishState','<>','2')->whereIn('Member',$Vip)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        }

        $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Corpore'] = isset($item['Corpore']) ? $item['Corpore'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectID'] = isset($item['ProjectID']) ? $item['ProjectID'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['InvestType'] = isset($item['InvestType']) ? $item['InvestType'] : '';
            $item['Year'] = isset($item['Year']) ? $item['Year'] : '';
            $endTime = time();
            $item['PublishTime'] = isset($item['PublishTime']) ? $item['PublishTime'] : '';
            $time = strtotime($item['PublishTime']) + 24*60*60;
                $item['NewFlag'] = 0;
            if($time > $endTime){
                $item['NewFlag'] = 1;
            }
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
         // dd($data);
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
        // return json_encode([['test'=>1],['test'=>2]]);
        // return $this->response->array([['test'=>1],['test'=>2]]);
    }

    /**
     * 项目列表
     *
     * @param Request $request
     */
    public function proList2() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $TypeID = (isset($payload['TypeID']) && $payload['TypeID'] != 'null' &&  $payload['TypeID'] != '' ) ?  $payload['TypeID'] : null;
        $ProArea = (isset($payload['ProArea']) && $payload['ProArea'] != 'null' && $payload['ProArea'] != '' ) ?  $payload['ProArea'] : null;
        $Vip = (isset($payload['Vip']) && $payload['Vip'] != 'null' && $payload['Vip'] != '' ) ?  $payload['Vip'] : 'default';
        if($Vip == 'default' || $Vip == 'null' || $Vip == '') {
            $Vip = [0,1,2];
        } else {
            $Vip = array($Vip);
        }

        $where = app('request')->except('startpage','pagecount','access_token','TypeID', 'ProArea', '_', 'Vip','token');

        $Type = [1,6,12];
        if(!$TypeID && !$ProArea) {
            $projects = Project::skip($skipnum)->take($pagecount)->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $counts = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && !$ProArea) {
            if(count($where) == 0){
                $projects = Project::where('TypeID',$TypeID)->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('Member',$Vip)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            } else {
                $projects = Project::where('TypeID',$TypeID)->where('PublishState','<>','2')->whereIn('Member',$Vip)->where('CertifyState',1)->lists('ProjectID');
                $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            }
        } elseif (!$TypeID && $ProArea) {
            $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->whereIn('Member',$Vip)->where('CertifyState',1)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->whereIn('Member',$Vip)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && $ProArea) {
            $projects = Project::where('TypeID',$TypeID)->where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID',$Type)->where('CertifyState',1)->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
            $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->where('PublishState','<>','2')->whereIn('Member',$Vip)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        }

        $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Corpore'] = isset($item['Corpore']) ? $item['Corpore'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['InvestType'] = isset($item['InvestType']) ? $item['InvestType'] : '';
            $item['Year'] = isset($item['Year']) ? $item['Year'] : '';
            $item['PublishTime'] = isset($item['PublishTime']) ? $item['PublishTime'] : '';
            $endTime = time();
            $time = strtotime($item['PublishTime']) + 24*60*60;
                $item['NewFlag'] = 0;
            if($time > $endTime){
                $item['NewFlag'] = 1;
            }
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
         // dd($data);
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
        // return json_encode([['test'=>1],['test'=>2]]);
        // return $this->response->array([['test'=>1],['test'=>2]]);
    }

    /**
     * 获取项目详情
     *
     * @param Request $request
     * @param int $id
     */
    public function getInfo($id) {
        $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->find($id);
        $diffTableName = $project->TableName;
        $type = $project->TypeID;
        $where = ['T_P_PROJECTINFO.ProjectID'=>$id, 'T_P_PROJECTINFO.DeleteFlag'=>0];

        switch ($type) {
            case '1':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','FromWhere','AssetType','AssetList','TransferMoney')->where($where)->get()->toArray();
                break;

            case '2':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney',$diffTableName.'.Status','AssetType','Rate')->where($where)->get()->toArray();
                break;

            case '3':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','AssetType','Requirement')->where($where)->get()->toArray();
                break;

            case '4':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','BuyerNature')->where($where)->get()->toArray();
                break;

            case '5':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','AssetType')->where($where)->get()->toArray();
                // dd($project);
                // $project['0']['TypeName'] = $project['0']['AssetType'].'信息';
                break;

            case '6':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','Rate','AssetType')->where($where)->get()->toArray();
                break;

            case '7':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','ServiceLife')->where($where)->get()->toArray();
                break;

            case '8':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','Date','AssetType')->where($where)->get()->toArray();
                break;

            case '9':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','AssetType')->where($where)->get()->toArray();
                break;

            case '10':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','Informant','AssetType')->where($where)->get()->toArray();
                break;

            case '11':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','Requirement','AssetType')->where($where)->get()->toArray();
                break;

            case '12':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','AssetType','Corpore','TransferMoney')->where($where)->get()->toArray();
                break;

            case '13':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','AssetType','Buyer')->where($where)->get()->toArray();
                break;

            case '14':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','AssetType','TotalMoney','TransferMoney')->where($where)->get()->toArray();
                break;

            case '15':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','Publisher','Price','Member','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','CompanyDes','PictureDes1','PictureDes2','PictureDes3','AssetType','InvestType','Rate','Year')->where($where)->get()->toArray();
                break;
        }
        $project = $project[0];
        //抢单人数统计
        $RushCount = DB::table('T_P_RUSHPROJECT')->where('ProjectID', $id)->where('CooperateFlag','<>',3)->count();
        //收藏人数统计
        $CollectCount = DB::table('T_P_COLLECTION')->where(['Type'=>1, 'ItemID'=>$id])->count();
        // $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")->select('ProjectID','ProArea')->where($where)->get();
        $project['RushCount'] = $RushCount;
        $project['CollectCount'] = $CollectCount;
        if($type == 9){
            $project['TotalMoney'] = $project['TotalMoney']*10000;
        }

        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $project['CollectFlag'] = 0;
        $project['RushFlag'] = 0;
        $project['PayFlag'] = 0;
        $project['Account'] = 0;
        if ($UserID) {
            $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 1, 'ItemID' => $id, 'UserID' => $UserID])->get();
            if ($tmp) {
                $project['CollectFlag'] = 1;
            } else {
                $project['CollectFlag'] = 0;
            }

            $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
            $tmpp = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$id, 'ServiceID'=>$ServiceID])->where('CooperateFlag','<>',3)->get();
            if ($tmpp) {
                $project['RushFlag'] = 1;
            } else {
                $project['RushFlag'] = 0;
            }

            //支付标记
            $tmppp = DB::table('T_U_MONEY')->where('UserID', $UserID)->where('ProjectID', $id)->get();
            $tmpppp = DB::table('T_P_RUSHPROJECT')->where(['ServiceID'=>$ServiceID, 'ProjectID'=>$id])->get();
            if ($tmppp || $tmpppp) {
                $project['PayFlag'] = 1;
            } else {
                $project['PayFlag'] = 0;
            }

            $project['Account'] = User::where('userid', $UserID)->pluck('Account');
        }
        $project['CompanyDes'] = isset($project['CompanyDes'])?$project['CompanyDes']:null;
        $project['CompanyDesPC'] = $project['CompanyDes'];
        $project['CompanyDes'] = str_replace('</p>', '', $project['CompanyDes']);
        $project['CompanyDes'] = str_replace('<p>', '', $project['CompanyDes']);
        $project['CompanyDes'] = str_replace('<br />', '', $project['CompanyDes']);
        $project['CompanyDes'] = str_replace('&nbsp;', ' ', $project['CompanyDes']);

        if($project['TypeName'] == '固定资产'){
            $project['TypeName'] = '固产转让';
        } 
        if($project['TypeName'] == '资产包'){
            $project['TypeName'] = '资产包转让';
        } 
        if($project['TypeName'] == '融资信息'){
            $project['TypeName'] = '融资需求';
        }

        return $project;
    }

    /**
    *项目详情
    *
    * @param Request $request
    * @param int $id
    */
    public function proInfo($id)
    {   
        $CertifyState = Project::where('ProjectID',$id)->pluck('CertifyState');
        if($CertifyState != 1){
            return;
        }
        $data = $this->getInfo($id);
        $data['ProArea'] = isset($data['ProArea']) ? $data['ProArea'] : '';
        $data['FromWhere'] = isset($data['FromWhere']) ? $data['FromWhere'] : '';
        $data['AssetType'] = isset($data['AssetType']) ? $data['AssetType'] : '';
        $data['TotalMoney'] = isset($data['TotalMoney']) ? $data['TotalMoney'] : '';
        $data['Corpore'] = isset($data['Corpore']) ? $data['Corpore'] : '';
        $data['TransferMoney'] = isset($data['TransferMoney']) ? $data['TransferMoney'] : '';
        $data['Status'] = isset($data['Status']) ? $data['Status'] : '';
        $data['Rate'] = isset($data['Rate']) ? $data['Rate'] : '';
        $data['Requirement'] = isset($data['Requirement']) ? $data['Requirement'] : '';
        $data['BuyerNature'] = isset($data['BuyerNature']) ? $data['BuyerNature'] : '';
        $data['Informant'] = isset($data['Informant']) ? $data['Informant'] : '';
        $data['Buyer'] = isset($data['Buyer']) ? $data['Buyer'] : '';
        $data['ProjectNumber'] = 'FB' . sprintf("%05d", $data['ProjectID']);
        $data['PublishTime'] = substr($data['PublishTime'], 0,10);
        $data['InvestType'] = isset($data['InvestType']) ? $data['InvestType'] : '';
        $data['Year'] = isset($data['Year']) ? $data['Year'] : '';
        Project::where('ProjectID',$id)->increment('ViewCount');
        
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $data['CollectFlag'] = 0;
        $data['RushFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 1, 'ItemID' => $id, 'UserID' => $UserID])->get();
             if ($tmp) {
                $data['CollectFlag'] = 1;
             } else {
                $data['CollectFlag'] = 0;
             }

             $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
             $tmpp = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$id, 'ServiceID'=>$ServiceID])->where('CooperateFlag','<>',3)->get();
             if ($tmpp) {
                $data['RushFlag'] = 1;
             } else {
                $data['RushFlag'] = 0;
             }
        }
        // $data['CollectFlag'] = 0;
        $picture = User::where('UserID',$data['UserID'])->pluck('UserPicture');
        $data['UserPicture'] = $picture;   
        $data['ProjectNumber'] = 'FB' . sprintf("%05d", $data['ProjectID']);
        if($UserID){
            //写查看信息log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'check.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = $UserID;
            $log['type'] = 1;
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
            $log['type'] = 1;
            $log['itemid'] = $id;
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        }
        return $this->response->array($data);
    }

    /**
     * 项目抢单
     *
     * @param Request $request
     */
    public function proRush() {
        $payload = app('request')->all();
        $ProjectID = $payload['ProjectID'];
        $puber = Project::where("ProjectID", $ProjectID)->pluck('UserID');
        $UserID = $this->auth->user()->toArray()['userid'];

        if($puber == $UserID){
            return $this->response->array(['status_code'=>'200','msg'=>'您不能抢自己发布的信息！']);
        }
        $isSer = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$UserID, 'T_P_SERVICECERTIFY.State'=>1])->count();
        if($isSer == 0){
            return $this->response->array(['status_code'=>'200','msg'=>'您还没有认证成为服务方，还不能抢单，快去认证吧！']);
        }
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');

        $RushTime = date("Y-m-d H:i:s",strtotime('now'));

        $tmp = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->count();
        if($tmp != 0){
            $status = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->pluck('CooperateFlag');
            if($status == 3){
                DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->update(['CooperateFlag'=>0]);

                //编写消息
                $Text = [];
                $Message = [];
                $Text['Title'] = '您有新的抢单人！';
                $Text['Text'] = '您发布的编号为:' . 'FB' . sprintf("%05d", $ProjectID) . '， 有新的服务方申请抢单。您可以到<我的发布>中查看抢单人，选择合适的服务方进行合作！或者直接点击此链接，查看服务方的详情。<a href="http://ziyawang.com/ucenter/mypro/rushlist/'.$ProjectID.'">http://ziyawang.com/ucenter/mypro/rushlist/'.$ProjectID.'</a>。如果不能点击，请复制此链接，在浏览器地址栏粘贴访问。';
                $Text['Time'] = date("Y-m-d H:i:s",strtotime('now'));
                //发送消息
                $Message['TextID'] = DB::table('T_M_MESSAGETEXT')->insertGetId($Text);
                $Message['SendID'] = 0;
                $Message['RecID'] = $puber;
                $Message['Status'] = 0;
                DB::table('T_M_MESSAGE')->insert($Message);
                return $this->response->array(['status_code'=>'200','msg'=>'抢单成功']);
            }
            return $this->response->array(['status_code'=>'200','msg'=>'您已抢单，请不要重复抢单！']);
        }
        DB::table('T_P_RUSHPROJECT')->insert(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID, 'RushTime'=>$RushTime]);


        //编写消息
        $Text = [];
        $Message = [];
        $Text['Title'] = '您有新的抢单人！';
        $Text['Text'] = '您发布的编号为:' . 'FB' . sprintf("%05d", $ProjectID) . '， 有新的服务方申请抢单。您可以到<我的发布>中查看抢单人，选择合适的服务方进行合作！或者直接点击此链接，查看服务方的详情。<a href="http://ziyawang.com/ucenter/mypro/rushlist/'.$ProjectID.'">http://ziyawang.com/ucenter/mypro/rushlist/'.$ProjectID.'</a>。如果不能点击，请复制此链接，在浏览器地址栏粘贴访问。';
        $Text['Time'] = date("Y-m-d H:i:s",strtotime('now'));
        //发送消息
        $Message['TextID'] = DB::table('T_M_MESSAGETEXT')->insertGetId($Text);
        $Message['SendID'] = 0;
        $Message['RecID'] = $puber;
        $Message['Status'] = 0;
        DB::table('T_M_MESSAGE')->insert($Message);

        return $this->response->array(['status_code'=>'200','msg'=>'抢单成功']);
    }


    /**
     * 取消抢单
     *
     * @param Request $request
     */
    public function proRushCancel() {
        $payload = app('request')->all();
        $ProjectID = $payload['ProjectID'];
        $UserID = $this->auth->user()->toArray()['userid'];
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');

        DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID])->update(['CooperateFlag'=>3]);
        return $this->response->array(['status_code'=>'200','msg'=>'取消抢单成功']);
    }
}

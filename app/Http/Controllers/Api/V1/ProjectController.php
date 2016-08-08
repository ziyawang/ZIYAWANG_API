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
        // $payload = app('request')->except('a');
        $proType = $payload['TypeID'];
        $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$proType)->pluck('TableName');
        $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','token','access_token','UserID');
        // dd($diffData);

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $project = new Project();

            // $project->UserID = 1;
            $project->UserID = $this->auth->user()->toArray()['userid'];
            $project->TypeID = $payload['TypeID'];
            $project->ProArea = $payload['ProArea'];
            $project->WordDes = $payload['WordDes'];
            $project->VoiceDes = $payload['VoiceDes'];
            $project->PictureDes1 = $payload['PictureDes1'];
            $project->PictureDes2 = $payload['PictureDes2'];
            $project->PictureDes3 = $payload['PictureDes3'];
            $project->PublishTime = date('Y-m-d H:i:s',time());
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

        $where = app('request')->except('startpage','pagecount','access_token','TypeID', 'ProArea', '_');

        if(!$TypeID && !$ProArea) {
            $projects = Project::skip($skipnum)->take($pagecount)->where('CertifyState',1)->where('PublishState','<>','2')->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $counts = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->where('CertifyState',1)->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && !$ProArea) {
            if(count($where) == 0){
                $projects = Project::where('TypeID',$TypeID)->where('CertifyState',1)->where('PublishState','<>','2')->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            } else {
                $projects = Project::where('TypeID',$TypeID)->where('PublishState','<>','2')->where('CertifyState',1)->lists('ProjectID');
                $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
            }
        } elseif (!$TypeID && $ProArea) {
            $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->where('CertifyState',1)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>','2')->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && $ProArea) {
            $projects = Project::where('TypeID',$TypeID)->where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>','2')->where('CertifyState',1)->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
            $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
            $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->where('PublishState','<>','2')->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
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
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
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
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','FromWhere','AssetType','AssetList','TransferMoney')->where($where)->get()->toArray();
                break;

            case '2':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney',$diffTableName.'.Status','AssetType','Rate')->where($where)->get()->toArray();
                break;

            case '3':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','AssetType','Requirement')->where($where)->get()->toArray();
                break;

            case '4':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','BuyerNature')->where($where)->get()->toArray();
                break;

            case '5':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','AssetType')->where($where)->get()->toArray();
                break;

            case '6':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','Rate','AssetType')->where($where)->get()->toArray();
                break;

            case '7':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','ServiceLife')->where($where)->get()->toArray();
                break;

            case '8':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','Date','AssetType')->where($where)->get()->toArray();
                break;

            case '9':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','TotalMoney','AssetType')->where($where)->get()->toArray();
                break;

            case '10':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','Informant','AssetType')->where($where)->get()->toArray();
                break;

            case '11':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','Requirement','AssetType')->where($where)->get()->toArray();
                break;

            case '12':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','AssetType','TransferMoney')->where($where)->get()->toArray();
                break;

            case '13':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','AssetType','Buyer')->where($where)->get()->toArray();
                break;

            case '14':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.TypeID','T_P_PROJECTINFO.UserID','PublishState','CertifyState','PublishTime','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','AssetType','TotalMoney','TransferMoney')->where($where)->get()->toArray();
                break;
        }
        $project = $project[0];
        //抢单人数统计
        $RushCount = DB::table('T_P_RUSHPROJECT')->where('ProjectID', $id)->count();
        // $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")->select('ProjectID','ProArea')->where($where)->get();
        $project['RushCount'] = $RushCount;
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
        $data = $this->getInfo($id);
        $data['ProArea'] = isset($data['ProArea']) ? $data['ProArea'] : '';
        $data['FromWhere'] = isset($data['FromWhere']) ? $data['FromWhere'] : '';
        $data['AssetType'] = isset($data['AssetType']) ? $data['AssetType'] : '';
        $data['TotalMoney'] = isset($data['TotalMoney']) ? $data['TotalMoney'] : '';
        $data['TransferMoney'] = isset($data['TransferMoney']) ? $data['TransferMoney'] : '';
        $data['Status'] = isset($data['Status']) ? $data['Status'] : '';
        $data['Rate'] = isset($data['Rate']) ? $data['Rate'] : '';
        $data['Requirement'] = isset($data['Requirement']) ? $data['Requirement'] : '';
        $data['BuyerNature'] = isset($data['BuyerNature']) ? $data['BuyerNature'] : '';
        $data['Informant'] = isset($data['Informant']) ? $data['Informant'] : '';
        $data['Buyer'] = isset($data['Buyer']) ? $data['Buyer'] : '';
        $data['ProjectNumber'] = 'FB' . sprintf("%05d", $data['ProjectID']);
        $data['PublishTime'] = substr($data['PublishTime'], 0,10);
        Project::where('ProjectID',$id)->increment('ViewCount');
        
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $data['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 1, 'ItemID' => $id, 'UserID' => $UserID])->get();
             if ($tmp) {
                $data['CollectFlag'] = 1;
             } else {
                $data['CollectFlag'] = 0;
             }
        }
        // $data['CollectFlag'] = 0;
        $picture = User::where('UserID',$data['UserID'])->pluck('UserPicture');
        $data['UserPicture'] = $picture;   
        $data['ProjectNumber'] = 'FB' . sprintf("%05d", $data['ProjectID']);
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
            return $this->response->array(['status_code'=>'200','msg'=>'您已抢单，请不要重复抢单！']);
        }
        DB::table('T_P_RUSHPROJECT')->insert(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID, 'RushTime'=>$RushTime]);
        return $this->response->array(['status_code'=>'200','msg'=>'抢单成功']);
    }

}

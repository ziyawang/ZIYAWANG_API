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
        $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes','token','access_token','UserID');
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
            $project->PictureDes = $payload['PictureDes'];
            $project->save();
            
            // DB::table('T_P_PROJECTINFO')->create(['TypeID' => 1,'UserID' => 1, 'aaa' => '111']) db不能用create方法 公共的info表可以直接用create方法 spec要用排除没用的数据方法
            $diffData['ProjectID'] = $project->ProjectID;
            DB::table("$diffTableName")->insert($diffData);
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
        $TypeID = isset($payload['TypeID']) ?  $payload['TypeID'] : null;
        $ProArea = isset($payload['ProArea']) ?  $payload['ProArea'] : null;

        $where = app('request')->except('startpage','pagecount','access_token','TypeID', 'ProArea');

        if(!$TypeID && !$ProArea) {
            $projects = Project::skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->lists('ProjectID');
            $counts = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->count();
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && !$ProArea) {
            $projects = Project::where('TypeID',$TypeID)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        } elseif (!$TypeID && $ProArea) {
            $projects = Project::where('ProArea',$ProArea)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        } elseif ($TypeID && $ProArea) {
            $projects = Project::where('TypeID',$TypeID)->where('ProArea',$ProArea)->lists('ProjectID');
            $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
            // dd($diffTableName);
            $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
            $counts = count($projects);
            $projects = Project::whereIn('ProjectID',$projects)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->lists('ProjectID');
            $pages = ceil($counts/$pagecount);
        }

        $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
            $data[] = $item;
        }
         // dd($data);
        return $this->response->array(['count'=>$counts, 'pages'=>$pages, 'data'=>$data]);
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
        $where = ['T_P_PROJECTINFO.ProjectID'=>$id, 'T_P_PROJECTINFO.CertifyState'=>0, 'T_P_PROJECTINFO.DeleteFlag'=>0];

        switch ($type) {
            case '1':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','FromWhere','AssetType','AssetList','TransferMoney')->where($where)->get()->toArray();
                break;

            case '2':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney',$diffTableName.'.Status','AssetType','Rate')->where($where)->get()->toArray();
                break;

            case '3':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','AssetType')->where($where)->get()->toArray();
                break;

            case '4':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','BuyerNature')->where($where)->get()->toArray();
                break;

            case '5':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','AssetType')->where($where)->get()->toArray();
                break;

            case '6':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','Rate','AssetType')->where($where)->get()->toArray();
                break;

            case '7':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','ServiceLife')->where($where)->get()->toArray();
                break;

            case '8':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','Date','AssetType')->where($where)->get()->toArray();
                break;

            case '9':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','TotalMoney','AssetType')->where($where)->get()->toArray();
                break;

            case '10':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','Infomant')->where($where)->get()->toArray();
                break;

            case '11':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','Requirement','AssetType')->where($where)->get()->toArray();
                break;

            case '12':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','AssetType','TransferMoney')->where($where)->get()->toArray();
                break;

            case '13':
                $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
                ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
                ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.UserID')
                ->select("T_P_PROJECTINFO.ProjectID",'T_P_PROJECTINFO.UserID','PhoneNumber','ServiceID','TypeName','ViewCount','CollectionCount','ProArea','WordDes','VoiceDes','PictureDes','AssetType')->where($where)->get()->toArray();
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
        Project::where('ProjectID',$id)->increment('ViewCount');
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
        $ServiceID = $this->auth->user()->toArray()['userid'];
        $RushTime = date("Y-m-d H:i:s",strtotime('now'));

        DB::table('T_P_RUSHPROJECT')->insert(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID, 'RushTime'=>$RushTime]);
        return '抢单成功';
    }

}

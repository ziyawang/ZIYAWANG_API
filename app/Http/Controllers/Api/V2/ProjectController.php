<?php

/**
 * 项目发布、展示、详情控制器
 */
namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use App\User;
use App\News;
use App\Project;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;
use PDO;

class ProjectController extends BaseController
{

    /**
     * 项目发布
     *
     * @param Request $request
     */
    public function create(){
        $payload = app('request')->all();
        $Channel = isset($payload['Channel'])?$payload['Channel']:'PC';
        $ProLabel = isset($payload['ProLabel'])?$payload['ProLabel']:'';
        $Promise = isset($payload['Promise'])?$payload['Promise']:'';
        $p1 = isset($payload['PictureDes1'])?$payload['PictureDes1']:'';
        $p2 = isset($payload['PictureDes2'])?$payload['PictureDes2']:'';
        $p3 = isset($payload['PictureDes3'])?$payload['PictureDes3']:'';
        $p4 = isset($payload['PictureDes4'])?$payload['PictureDes4']:'';
        $p5 = isset($payload['PictureDes5'])?$payload['PictureDes5']:'';
        $ProLabel = is_array($ProLabel)?implode(',', $ProLabel):$ProLabel;
        $proType = $payload['TypeID'];
        $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$proType)->pluck('TableName');
        $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','PictureDes4','PictureDes5','ConnectPhone','ConnectPerson','ProLabel','token','access_token','UserID','year1','month1','day1','Promise','ccfs');
        if($proType == 9 || $proType == '09' || $diffTableName == 'T_P_SPEC09') {
            $diffData['TotalMoney'] = $diffData['TotalMoney']/10000;
        }

        $user = $this->auth->user()->toArray();

        //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();

        foreach ($diffData as $key => $value) {
            if(is_array($value)){
                $diffData[$key] = implode(',', $value);
            }
        }
        try {
            $project = new Project();

            $project->UserID = $user['userid'];
            $project->TypeID = $payload['TypeID'];
            $project->ProArea = $payload['ProArea'];
            $project->WordDes = $payload['WordDes'];
            $project->VoiceDes = $payload['VoiceDes'];
            $project->PictureDes1 = $p1;
            $project->PictureDes2 = $p2;
            $project->PictureDes3 = $p3;
            $project->PictureDes4 = $p4;
            $project->PictureDes5 = $p5;
            $project->ProLabel = $ProLabel;
            $project->Promise = $Promise;
            $project->ConnectPerson = $payload['ConnectPerson'];
            $project->ConnectPhone = $payload['ConnectPhone'];
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
            $exceptarr = [
            '18721748010',
            '13381371646',
            '18610301049',
            '15210943996',
            '15510404321',
            '13521572560',
            '18810618364',
            '18518638832',
            '18611327073',
            '13488773066',
            '13381371646',
            '17319364569',
            '15801290616',
            '13423867541',
            '15261176097'];
            if(!in_array($user['phonenumber'], $exceptarr)){
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

    public function uploadFile(){
        $payload = app('request')->all();
        $Channel = isset($payload['Channel'])?$payload['Channel']:'Andriod';
        $Promise = isset($payload['Promise'])?$payload['Promise']:'';
        $ProLabel = isset($payload['ProLabel'])?$payload['ProLabel']:'';
        $ProLabel = is_array($ProLabel)?implode(',', $ProLabel):$ProLabel;
        $proType = $payload['TypeID'];
        $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$proType)->pluck('TableName');
        $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','PictureDes4','PictureDes5','ConnectPhone','ConnectPerson','ProLabel','token','access_token','UserID','year1','month1','day1','Promise','Channel');

        $image_path=dirname(base_path()).'/ziyaupload/images/user/';
        $voice_path=dirname(base_path()).'/ziyaupload/files/';
        if(!is_dir($image_path)){  
                 mkdir($image_path,0777,true);  
        }  
        if(!is_dir($voice_path)){
              mkdir($voice_path,0777,true);  
        }
        foreach($_FILES as  $key=>$file){
            if(isset($_FILES[$key])){
                $baseName=basename($file['name']);
                $extension=strrchr($baseName, ".");
                $newName=time() . mt_rand(1000, 9999).$extension;
                if($key=="VoiceDes"){
                    $target_path = $voice_path . $newName;  
                    $filePath="/".$newName;
                }else{
                    $target_path = $image_path . $newName;  
                    $filePath="/user/".$newName;
                }
                if(move_uploaded_file($_FILES[$key]["tmp_name"],$target_path)){
                    $payload[$key]=$filePath;
                }else{
                    return $this->response->array(['status_code' => '480','msg'=>"文件上传失败"]);
                } 
            }
        }
 
        // //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        foreach ($diffData as $key => $value) {
            if(is_array($value)){
                $diffData[$key] = implode(',', $value);
            }
        }
        try {
            $project = new Project();
 
            // $project->UserID = 1;
            $project->UserID = $this->auth->user()->toArray()['userid'];
            $project->TypeID = $payload['TypeID'];
            $project->ProArea = $payload['ProArea'];
            $project->WordDes = $payload['WordDes'];
            $project->VoiceDes = isset($payload['VoiceDes']) ? $payload['VoiceDes'] : '';
            $project->PictureDes1 = isset($payload['PictureDes1'])?$payload['PictureDes1']:'';
            $project->PictureDes2 = isset($payload['PictureDes2'])?$payload['PictureDes2']:'';
            $project->PictureDes3 = isset($payload['PictureDes3'])?$payload['PictureDes3']:'';
            $project->PictureDes4 = isset($payload['PictureDes4'])?$payload['PictureDes4']:'';
            $project->PictureDes5 = isset($payload['PictureDes5'])?$payload['PictureDes5']:'';
            $project->ProLabel = $ProLabel;
            $project->Promise = $Promise;
            $project->ConnectPerson = $payload['ConnectPerson'];
            $project->ConnectPhone = $payload['ConnectPhone'];
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
            return $this->response->array(['status_code'=>'200','success' => 'Create Pro Success']);
        } else {
            return $this->response->array(['status_code'=>'499','error' => 'Create Pro Error']);
        }    
    }

    public function upload(){
        $image_path=dirname(base_path()).'/ziyaupload/images/user/';
        if(!is_dir($image_path)){  
            mkdir($image_path,0777,true);  
        } 
        $baseName=basename($_FILES['UserPicture']['name'] );
        $extension=strrchr($baseName, ".");
        $newName=time() . mt_rand(1000, 9999).$extension;
        $target_path = $image_path . $newName;  
        $filePath="/user/".$newName;
        if(move_uploaded_file($_FILES['UserPicture']['tmp_name'], $target_path)){
            $user=new User();
            $userId=$this->auth->user()->toArray()["userid"];
            $dbs= DB::table("users")->where("userid",$userId)->update([
                        "UserPicture"=>$filePath,
                ]);
            if($dbs){
                return $this->response->array(['status_code'=>'200','success' => 'update User Success']);
            }else{
                return $this->response->array(['status_code'=>'409','success' => 'update User Error']);
            }

        }
    }

    /**
    *
    *大杂烩
    */
    public function mass($startpage,$pagecount,$skipnum){
        $NEWS = new \App\Http\Controllers\Api\V1\NewsController();
        $Type = [1,6,12,16,17,18,19,20,21,22];
        $sql ="
        (select `ProjectID`,`created_at`,'' AS `NewsID` from T_P_PROJECTINFO where CertifyState=1 and PublishState!=1 and TypeID in (1,6,12,16,17,18,19,20,21,22) )union all 
        (select '' AS `ProjectID`,`created_at`,`NewsID` from T_N_NEWSINFO where Flag=1 and NewsLabel='czgg') order by created_at desc limit $skipnum,$pagecount";
        $mass = DB::select($sql);
        $data = [];
        foreach ($mass as $v) {
            if($v->ProjectID != ''){
                $item = $this->getInfo($v->ProjectID);
                $item['ListType'] = 1;
                $item = $this->_makeArr($item);
                $data[] = $item;
            } else {
                $item = $NEWS->getInfo($v->NewsID);
                $item['ListType'] = 2;
                $item['TypeID'] = 99;
                $item = $this->_makeArr($item);
                $data[] = $item;
            }
        }
        return $data;
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
        $TypeID = (isset($payload['TypeID']) && $payload['TypeID'] != 'null' &&  $payload['TypeID'] != '' &&  $payload['TypeID'] != 'undefined') ?  $payload['TypeID'] : null;
        $ProArea = (isset($payload['ProArea']) && $payload['ProArea'] != 'null' && $payload['ProArea'] != '' ) ?  $payload['ProArea'] : null;
        $Vip = (isset($payload['Vip']) && $payload['Vip'] != 'null' && $payload['Vip'] != '' ) ?  $payload['Vip'] : 'default';
        $Type = [1,6,12,16,17,18,19,20,21,22];
        //如果没有选择信息类型、级别、地区
        if(!$TypeID && !$ProArea && $Vip == 'default'){
            $data = $this->mass($startpage,$pagecount,$skipnum);
            $counts = Project::where('CertifyState',1)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->where('PublishState','<>',1)->count();
            $counts += News::where('NewsLabel','ccgg')->where('Flag',1)->count();
            $pages = ceil($counts/$pagecount);
            return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
        }

        if($Vip == 'default' || $Vip == 'null' || $Vip == '') {
            $Vip = [0,1,2];
        } else {
            $Vip = array($Vip);
        }


        //如果typeid为18，19 企业商帐个人债权
        if($TypeID == '18' || $TypeID == '19'){
            if(isset($payload['Law']) && $payload['Law'] == '1'){
                $projects = Project:: where('PublishState','<>',1)->whereIn('Member',$Vip)->where('T_P_PROJECTINFO.TypeID', $TypeID)->where('CertifyState',1)->lists('ProjectID');
                if($ProArea){
                    $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->where('CertifyState',1)->lists('ProjectID');
                }
                $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where('Law','<>','')->lists('ProjectID');
                if(isset($payload['Rate']) && $payload['Rate'] != ''){
                    $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where('Law',$payload['Rate'])->lists('ProjectID');
                }
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
                foreach ($projects as $id) {
                    $item = $this->getInfo($id);
                    $endTime = time();
                    $time = strtotime($item['PublishTime']) + 24*60*60;
                        $item['NewFlag'] = 0;
                    if($time > $endTime){
                        $item['NewFlag'] = 1;
                    }
                    $item['PublishTime'] = substr($item['PublishTime'], 0,10);
                    $data[] = $item;
                }
                return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
            } elseif(isset($payload['UnLaw']) && $payload['UnLaw'] == '1'){
                $projects = Project:: where('PublishState','<>',1)->whereIn('Member',$Vip)->where('T_P_PROJECTINFO.TypeID', $TypeID)->where('CertifyState',1)->lists('ProjectID');
                if($ProArea){
                    $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->where('CertifyState',1)->lists('ProjectID');
                }
                $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$TypeID)->pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where('UnLaw','<>','')->lists('ProjectID');
                if(isset($payload['Rate']) && $payload['Rate'] != ''){
                    $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where('UnLaw',$payload['Rate'])->lists('ProjectID');
                }
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
                foreach ($projects as $id) {
                    $item = $this->getInfo($id);
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
            }
        }

        if($TypeID == "rzxx"){
            $Type = [6,17];
        } elseif($TypeID == "gdzc"){
            $Type = [16,12];
        } elseif($TypeID == "fpzc"){
            $Type = [20,21,22];
        } elseif($TypeID == "czgg"){
            $NEWS = new \App\Http\Controllers\Api\V1\NewsController();
            $news = News::where(['Flag'=>1,'NewsLabel'=>'czgg'])->lists('NewsID');
            $counts = count($news);
            $pages = ceil($counts/$pagecount);
            $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>'czgg'])->orderBy('created_at','desc')->lists('NewsID');
            $data = [];
            foreach ($news as $id) {
                $item = $NEWS->getInfo($id);
                $item = $this->_makeArr($item);
                $item['TypeID'] = 99;
                $data[] = $item;
            }
            return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
        } elseif($TypeID){
            $Type = array($TypeID);
        }

        $where = app('request')->except('startpage','pagecount','access_token','TypeID', 'ProArea', '_', 'Vip','token');
        if(!$ProArea){
            if(count($where) == 0){
                $projects = Project:: where('CertifyState',1)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
                foreach ($projects as $id) {
                    $item = $this->getInfo($id);
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
            } else {
                $projects = Project:: where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->where('CertifyState',1)->lists('ProjectID');
                $diffTableName = DB::table('T_P_PROJECTTYPE')-> pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
                foreach ($projects as $id) {
                    $item = $this->getInfo($id);
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
            }
        } else {
            if(count($where) == 0){
                $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('CertifyState',1)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::where('ProArea','like','%'.$ProArea.'%')->whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
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
            } else {
                $projects = Project::where('ProArea','like','%'.$ProArea.'%')->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->where('CertifyState',1)->lists('ProjectID');
                $diffTableName = DB::table('T_P_PROJECTTYPE')-> pluck('TableName');
                $projects = DB::table("$diffTableName")->whereIn('ProjectID',$projects)->where($where)->lists('ProjectID');
                $counts = count($projects);
                $projects = Project::where('ProArea','like','%'.$ProArea.'%')->whereIn('ProjectID',$projects)->where('PublishState','<>',1)->whereIn('Member',$Vip)->whereIn('T_P_PROJECTINFO.TypeID', $Type)->skip($skipnum)->take($pagecount)->join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->orderBy('T_P_PROJECTINFO.created_at','desc')->lists('ProjectID');
                $pages = ceil($counts/$pagecount);
                $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
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
            }
        }

        $data = [];
        foreach ($projects as $id) {
            $item = $this->getInfo($id);
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
        $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')
        ->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")
        ->join("users", 'T_P_PROJECTINFO.UserID', '=', 'users.userid')
        ->where($where)->get()->toArray();
               
        $project = $project[0];
        //抢单人数统计
        $RushCount = DB::table('T_P_RUSHPROJECT')->where('ProjectID', $id)->where('CooperateFlag','<>',3)->count();
        //收藏人数统计
        $CollectCount = DB::table('T_P_COLLECTION')->where(['Type'=>1, 'ItemID'=>$id])->count();
        // $project = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->join("$diffTableName", 'T_P_PROJECTINFO.ProjectID', '=', "$diffTableName.ProjectID")->select('ProjectID','ProArea')->where($where)->get();
        $project['RushCount'] = $RushCount;
        $project['CollectCount'] = $CollectCount;

        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $project['CollectFlag'] = 0;
        $project['RushFlag'] = 0;
        $project['PayFlag'] = 0;
        $project['Account'] = 0;
        if ($UserID) {
            $this->_upMember($UserID);
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

            $userinfo = $this->auth->user()->toArray();
            $userinfo['showright'] = json_decode($userinfo['showright'],true);
            if(is_array($userinfo['showright'])){
                $arr = array_keys($userinfo['showright']);
                foreach ($arr as $v) {
                    $rong_cloud = new \App\RCServer(env('RC_APPKEY'),env('RC_APPSECRET'));
                    $userinfo['showrightios'][] = \App\Tool::getPY($v);
                }
            }

            $project['right'] = $userinfo['right'];
            $project['rightarr'] = explode(',', $userinfo['right']);
            $project['showright'] = $userinfo['showright'];
            $userinfo['showrightios'] = isset($userinfo['showrightios'])?$userinfo['showrightios']:'';
            $project['showrightios'] = $userinfo['showrightios'];

            $project['Account'] = User::where('userid', $UserID)->pluck('Account');
        }
        if($project['ConnectPhone'] == ''){
            $project['ConnectPhone'] = $project['phonenumber'];
        }
        $project = $this->_makeArr($project);        

        $del = ['TableName','SerName','Spec01ID','Spec06ID','Spec12ID','Spec16ID','Spec17ID','Spec18ID','Spec19ID','Spec20ID','Spec21ID','Spec22ID','password','logintoken','email','idcard','Remark','rctoken','RealMoney','CertifyTime','DoneState',];
        $project = $this->_delatom($project,$del);
        return $project;
    }

    //删除数组元素
    public function _delatom($obj,$arr){
        foreach ($arr as $v) {
            if(isset($obj[$v])){
                unset($obj[$v]);
            }
        }
        return $obj;
    }

    //构造达实体
    public function _makeArr($project){
        $project['ProjectID']      = isset($project['ProjectID'])      ? $project['ProjectID']      : '';
        $project['Identity']       = isset($project['Identity'])       ? $project['Identity']       : '';
        $project['ProArea']        = isset($project['ProArea'])        ? $project['ProArea']        : '';
        $project['AssetType']      = isset($project['AssetType'])      ? $project['AssetType']      : '';
        $project['FromWhere']      = isset($project['FromWhere'])      ? $project['FromWhere']      : '';
        $project['TotalMoney']     = isset($project['TotalMoney'])     ? $project['TotalMoney']     : '';
        $project['TransferMoney']  = isset($project['TransferMoney'])  ? $project['TransferMoney']  : '';
        $project['Money']          = isset($project['Money'])          ? $project['Money']          : '';
        $project['Rate']           = isset($project['Rate'])           ? $project['Rate']           : '';
        $project['Counts']         = isset($project['Counts'])         ? $project['Counts']         : '';
        $project['Report']         = isset($project['Report'])         ? $project['Report']         : '';
        $project['Time']           = isset($project['Time'])           ? $project['Time']           : '';
        $project['Pawn']           = isset($project['Pawn'])           ? $project['Pawn']           : '';
        $project['AssetList']      = isset($project['AssetList'])      ? $project['AssetList']      : '';
        $project['Status']         = isset($project['Status'])         ? $project['Status']         : '';
        $project['Belong']         = isset($project['Belong'])         ? $project['Belong']         : '';
        $project['Usefor']         = isset($project['Usefor'])         ? $project['Usefor']         : '';
        $project['Type']           = isset($project['Type'])           ? $project['Type']           : '';
        $project['Area']           = isset($project['Area'])           ? $project['Area']           : '';
        $project['Year']           = isset($project['Year'])           ? $project['Year']           : '';
        $project['TransferType']   = isset($project['TransferType'])   ? $project['TransferType']   : '';
        $project['MarketPrice']    = isset($project['MarketPrice'])    ? $project['MarketPrice']    : '';
        $project['Credentials']    = isset($project['Credentials'])    ? $project['Credentials']    : '';
        $project['Dispute']        = isset($project['Dispute'])        ? $project['Dispute']        : '';
        $project['Debt']           = isset($project['Debt'])           ? $project['Debt']           : '';
        $project['Guaranty']       = isset($project['Guaranty'])       ? $project['Guaranty']       : '';
        $project['Month']          = isset($project['Month'])          ? $project['Month']          : '';
        $project['Nature']         = isset($project['Nature'])         ? $project['Nature']         : '';
        $project['State']          = isset($project['State'])          ? $project['State']          : '';
        $project['Industry']       = isset($project['Industry'])       ? $project['Industry']       : '';
        $project['DebteeLocation'] = isset($project['DebteeLocation']) ? $project['DebteeLocation'] : '';
        $project['Connect']        = isset($project['Connect'])        ? $project['Connect']        : '';
        $project['Pay']            = isset($project['Pay'])            ? $project['Pay']            : '';
        $project['Law']            = isset($project['Law'])            ? $project['Law']            : '';
        $project['UnLaw']          = isset($project['UnLaw'])          ? $project['UnLaw']          : '';
        $project['Court']          = isset($project['Court'])          ? $project['Court']          : '';
        $project['Brand']          = isset($project['Brand'])          ? $project['Brand']          : '';
        $project['ProLabel']       = isset($project['ProLabel'])       ? rtrim($project['ProLabel'],',')       : '';
        $project['WordDes']        = isset($project['WordDes'])        ? $project['WordDes']        : '';
        $project['VoiceDes']       = isset($project['VoiceDes'])       ? $project['VoiceDes']       : '';
        $project['PictureDes1']    = isset($project['PictureDes1'])    ? $project['PictureDes1']    : '';
        $project['PictureDes2']    = isset($project['PictureDes2'])    ? $project['PictureDes2']    : '';
        $project['PictureDes3']    = isset($project['PictureDes3'])    ? $project['PictureDes3']    : '';
        $project['PictureDes4']    = isset($project['PictureDes4'])    ? $project['PictureDes4']    : '';
        $project['PictureDes5']    = isset($project['PictureDes5'])    ? $project['PictureDes5']    : '';
        $project['ConnectPerson']  = isset($project['ConnectPerson'])  ? $project['ConnectPerson']  : '';
        $project['ConnectPhone']   = isset($project['ConnectPhone'])   ? $project['ConnectPhone']   : '';
        $project['Title']          = isset($project['Title'])          ? $project['Title']          : '';
        $project['CompanyDes']     = isset($project['CompanyDes'] )    ? $project['CompanyDes']     : '';
        $project['ProjectNumber']  = 'FB' . sprintf("%05d", $project['ProjectID']);
        $project['ProLabelArr']    = explode(',', $project['ProLabel']);
        $project['CollectFlag']    = isset($project['CollectFlag'])    ? $project['CollectFlag']    : 0;
        $project['RushFlag']       = isset($project['RushFlag']   )    ? $project['RushFlag']       : 0;
        $project['PayFlag']        = isset($project['PayFlag']    )    ? $project['PayFlag']        : 0;
        $project['Account']        = isset($project['Account']    )    ? $project['Account']        : 0;
        $project['CompanyDesPC']   = $project['CompanyDes'];
        $project['CompanyDes']     = str_replace('</p>', '', $project['CompanyDes']);
        $project['CompanyDes']     = str_replace('<p>', '', $project['CompanyDes']);
        $project['CompanyDes']     = str_replace('<br />', '', $project['CompanyDes']);
        $project['CompanyDes']     = str_replace('&nbsp;', ' ', $project['CompanyDes']);

        $project['NewsID']           = isset($project['NewsID']         ) ? $project['NewsID']          : '';    
        $project['NewsTitle']        = isset($project['NewsTitle']      ) ? $project['NewsTitle']       : '';       
        $project['NewsContent']      = isset($project['NewsContent']    ) ? $project['NewsContent']     : '';         
        $project['NewsLogo']         = isset($project['NewsLogo']       ) ? $project['NewsLogo']        : '';       
        $project['NewsThumb']        = isset($project['NewsThumb']      ) ? $project['NewsThumb']       : '';       
        $project['NewsLabel']        = isset($project['NewsLabel']      ) ? $project['NewsLabel']       : '';        
        $project['PublishTime']      = isset($project['PublishTime']    ) ? $project['PublishTime']     : '';        
        $project['NewsAuthor']       = isset($project['NewsAuthor']     ) ? $project['NewsAuthor']      : '';       
        $project['ViewCount']        = isset($project['ViewCount']      ) ? $project['ViewCount']       : '';       
        $project['CollectionCount']  = isset($project['CollectionCount']) ? $project['CollectionCount'] : '';            
        $project['Brief']            = isset($project['Brief']          ) ? $project['Brief']           : '';   
        $project['ListType']         = isset($project['ListType']       ) ? $project['ListType']        : '';   

        $project['right']         = isset($project['right']       ) ? $project['right']        : '';     
        $project['rightarr']         = isset($project['rightarr']       ) ? $project['rightarr']        : '';     
        $project['showright']         = isset($project['showright']       ) ? $project['showright']        : '';     
        $project['showrightios']         = isset($project['showrightios']       ) ? $project['showrightios']        : '';     
        
//给ios加hide字段
        $project['Hide'] = isset($project['Hide'] ) ? $project['Hide'] : 0;   //0是显示，1是隐藏  
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
        Project::where('ProjectID',$id)->increment('ViewCount');
        
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $data['CollectFlag'] = 0;
        $data['RushFlag'] = 0;
        if ($UserID) {
            $this->_upMember($UserID);
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

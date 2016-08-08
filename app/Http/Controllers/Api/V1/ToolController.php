<?php

/**
 * 通用控制器
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
use App\News;
use App\Video;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;

class ToolController extends BaseController
{

     /**
     * 获取系统消息列表
     *
     * @return mixed
     */
    public function getMessage()
    {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $UserID = $this->auth->user()->toArray()['userid'];
        $counts = DB::table('T_M_MESSAGE')->where('RecID', $UserID)->where('Status','<>', 2)->count();
        if($counts == 0){
            return $this->response->array(['status_code'=>'200','counts'=>'0']);
        }

        $readcounts = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID,'Status'=>0])->count();
        $messages = DB::table('T_M_MESSAGE')->where('RecID', $UserID)->where('Status','<>', 2)->skip($skipnum)->take($pagecount)->lists('TextID');
        // dd($messages);
        $data = [];
        foreach ($messages as $id) {
            $status = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID, 'TextID'=>$id])->pluck('Status');
            $message = $this->getMessageInfo($id);
            $message->Status = $status;
            $data[] = $message;
        }

        return $this->response->array(['status_code'=>'200','counts'=>$counts,'data'=>$data, 'readcounts'=>$readcounts, 'currentpage'=>$startpage]);

    }

     /**
     * 获取系统消息内容
     *
     * @return mixed
     */
    public function getMessageInfo($id)
    {
        $message = DB::table('T_M_MESSAGETEXT')->where('TextID', $id)->select('TextID','Title','Text','Time')->first();

        return $message;
    }

     /**
     * 更改消息状态为已读
     *
     * @return mixed
     */
    public function readMessage()
    {
        $TextID = app('request')->get('TextID');
        $UserID = $this->auth->user()->toArray()['userid'];
        $TextID = is_array($TextID)? $TextID : array($TextID);
        DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID])->whereIn('TextID',$TextID)->update(['Status'=>1]);
        $readcounts = DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID,'Status'=>0])->count();
        return $this->response->array(['status_code'=>'200','msg'=>'信息已读', 'readcounts'=>$readcounts,]);
    }

    /**
     * 更改消息状态为删除
     *
     * @return mixed
     */
    public function delMessage()
    {
        $TextID = app('request')->get('TextID');
        $UserID = $this->auth->user()->toArray()['userid'];
        $TextID = is_array($TextID)? $TextID : array($TextID);
        DB::table('T_M_MESSAGE')->where(['RecID'=>$UserID])->whereIn('TextID',$TextID)->update(['Status'=>2]);
        return $this->response->array(['status_code'=>'200','msg'=>'信息已删除']);
    }


    /**
     * 收藏
     *
     * @return mixed
     */
    public function collect()
    {   
        $payload = app('request')->all();
        $type = $payload['type'];
        $itemID = $payload['itemID'];
        $UserID = $this->auth->user()->toArray()['userid'];
        $tmp = DB::table('T_P_COLLECTION')->where(['Type' => $type, 'ItemID' => $itemID, 'UserID' => $UserID])->first();
        if($tmp){
            DB::table('T_P_COLLECTION')->where(['Type' => $type, 'ItemID' => $itemID, 'UserID' => $UserID])->delete();

            switch ($type) {
                //默认1：项目，2：视频，3：新闻资讯，4：服务方
                case '1':
                    $table = 'T_P_PROJECTINFO';
                    $pk = 'ProjectID';
                    break;
                
                case '2':
                    $table = 'T_V_VIDEOINFO';
                    $pk = 'VideoID';
                    break;

                case '3':
                    $table = 'T_N_NEWSINFO';
                    $pk = 'NewsID';
                    break;

                case '4':
                    $table = 'T_U_SERVICEINFO';
                    $pk = 'ServiceID';
                    break;
            }

            $item = DB::table("$table")->where($pk,$itemID)->decrement('CollectionCount');
            
            return $this->response->array(['status_code' => 200, 'msg' => '取消收藏成功！']);
        }
        $res = DB::table('T_P_COLLECTION')->insert([
                'Type'=>$type,
                'CollectTime' => date('Y-m-d H:i:s',time()),
                'ItemID' => $itemID,
                'UserID' => $UserID
            ]);

        if($res) {
            switch ($type) {
                //默认1：项目，2：视频，3：新闻资讯，4：服务方
                case '1':
                    $table = 'T_P_PROJECTINFO';
                    $pk = 'ProjectID';
                    break;
                
                case '2':
                    $table = 'T_V_VIDEOINFO';
                    $pk = 'VideoID';
                    break;

                case '3':
                    $table = 'T_N_NEWSINFO';
                    $pk = 'NewsID';
                    break;

                case '4':
                    $table = 'T_U_SERVICEINFO';
                    $pk = 'ServiceID';
                    break;
            }

            $item = DB::table("$table")->where($pk,$itemID)->increment('CollectionCount');

            return $this->response->array(['status_code' => 200, 'msg' => '收藏成功！']);
        } else {
            return $this->response->array(['status_code' => 200, 'msg' => '取消收藏成功！']);
        }
    }

    /**
     * 收藏列表
     *
     * @return mixed
     */
    public function collectList()
    {   

        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $UserID = $this->auth->user()->toArray()['userid'];
        // $collections = DB::table('T_P_COLLECTION')->where('UserID',$UserID)->get();
        $collections = DB::table('T_P_COLLECTION')->select('ItemID','Type')->where('UserID',$UserID)->get();
        $counts = count($collections);
        $pages = ceil($counts/$pagecount);
        $collections = DB::table('T_P_COLLECTION')->select('ItemID','Type','CollectTime')->where('UserID',$UserID)->orderBy('CollectTime','desc')->skip($skipnum)->take($pagecount)->get();

        $data = [];
        foreach ($collections as $items) {
            $id = $items->ItemID;
            $item = [];
            switch ($items->Type) {
                case '1':
                    $item = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->select('TypeName', 'ProjectID','ProArea','WordDes','PictureDes1')->where('ProjectID', $id)->first()->toArray();
                    $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
                    $item['TypeID'] = 1;
                    break;
                case '2':
                    $item = Video::select('VideoTitle','ViewCount','VideoDes','VideoID','VideoLogo')->where('VideoID', $id)->first()->toArray();
                    $item['TypeID'] = 2;
                    break;
                case '3':
                    $item = News::select('NewsTitle','Brief','NewsID','NewsLogo')->where('NewsID', $id)->first()->toArray();
                    $item['TypeID'] = 3;
                    break;
                case '4':
                    $item = Service::select('ServiceName','ServiceID','ServiceType','ServiceArea')->where('ServiceID', $id)->first()->toArray();
                    $type = explode(',', $item['ServiceType']);
                    $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
                    $picture = User::where('UserID',$id)->pluck('UserPicture');
                    $item['ServiceType'] = implode('、', $type);
                    $item['UserPicture'] = $picture;
                    $item['TypeID'] = 4;
                    break;
            }
            $item['ItemID'] = $id;
            $item['CollectTime'] = substr($items->CollectTime, 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }


    /**
     * 收藏列表 移动端专用
     *
     * @return mixed
     */
    public function appcollectList()
    {   

        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;

        $UserID = $this->auth->user()->toArray()['userid'];
        // $collections = DB::table('T_P_COLLECTION')->where('UserID',$UserID)->get();
        $collections = DB::table('T_P_COLLECTION')->select('ItemID','Type')->where('UserID',$UserID)->where('Type','<>',3)->get();
        $counts = count($collections);
        $pages = ceil($counts/$pagecount);
        $collections = DB::table('T_P_COLLECTION')->select('ItemID','Type','CollectTime')->where('UserID',$UserID)->where('Type','<>',3)->orderBy('CollectTime','desc')->skip($skipnum)->take($pagecount)->get();

        $data = [];
        foreach ($collections as $items) {
            $id = $items->ItemID;
            $item = [];
            switch ($items->Type) {
                case '1':
                    $item = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->select('TypeName', 'ProjectID','ProArea','WordDes','PictureDes1')->where('ProjectID', $id)->first()->toArray();
                    $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
                    $item['TypeID'] = 1;
                    break;
                case '2':
                    $item = Video::select('VideoTitle','ViewCount','VideoDes','VideoID','VideoLogo')->where('VideoID', $id)->first()->toArray();
                    $item['TypeID'] = 2;
                    break;
                case '3':
                    $item = News::select('NewsTitle','Brief','NewsID','NewsLogo')->where('NewsID', $id)->first()->toArray();
                    $item['TypeID'] = 3;
                    break;
                case '4':
                    $item = Service::select('ServiceName','ServiceID','ServiceType','ServiceArea')->where('ServiceID', $id)->first()->toArray();
                    $type = explode(',', $item['ServiceType']);
                    $type = DB::table('T_P_PROJECTTYPE')->whereIn('TypeID',$type)->lists('SerName');
                    $picture = User::where('UserID',$id)->pluck('UserPicture');
                    $item['ServiceType'] = implode('、', $type);
                    $item['UserPicture'] = $picture;
                    $item['TypeID'] = 4;
                    break;
            }
            $item['ItemID'] = $id;
            $item['CollectTime'] = substr($items->CollectTime, 0,10);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }




    /**
    *全站搜索
    *
    *@return mixed
    */
    public function search()
    {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 10;

        $type = $payload['type'];
        $content = $payload['content'];
        switch ($type) {
            
            case '1'://
                $data = $this->searchPro($content, $startpage, $pagecount);
                break;

            case '2'://
                $data = $this->searchVid($content, $startpage, $pagecount);
                break;
            
            case '4'://
                $data = $this->searchSer($content, $startpage, $pagecount);
                break;
        }

        return $data;
    }

    public function searchPro($content, $startpage, $pagecount)
    {
        $Project = new ProjectController();
        
        $projects = Project::where('WordDes','like','%'.$content.'%')->lists('ProjectID');
        $counts = count($projects);
        $pages  = ceil($counts/$pagecount);
        $skipnum = ($startpage-1)*$pagecount;
        $projects = Project::where('WordDes','like','%'.$content.'%')->orderBy('created_at', 'desc')->skip($skipnum)->take($pagecount)->lists('ProjectID');

        $data = [];
        foreach ($projects as $id) {
            $item = $Project->getInfo($id);
            $item['ProArea'] = isset($item['ProArea']) ? $item['ProArea'] : '';
            $item['FromWhere'] = isset($item['FromWhere']) ? $item['FromWhere'] : '';
            $item['AssetType'] = isset($item['AssetType']) ? $item['AssetType'] : '';
            $item['TotalMoney'] = isset($item['TotalMoney']) ? $item['TotalMoney'] : '';
            $item['TransferMoney'] = isset($item['TransferMoney']) ? $item['TransferMoney'] : '';
            $item['Status'] = isset($item['Status']) ? $item['Status'] : '';
            $item['Rate'] = isset($item['Rate']) ? $item['Rate'] : '';
            $item['Requirement'] = isset($item['Requirement']) ? $item['Requirement'] : '';
            $item['BuyerNature'] = isset($item['BuyerNature']) ? $item['BuyerNature'] : '';
            $item['Infomant'] = isset($item['Infomant']) ? $item['Infomant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
         // dd($data);
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data]);
    }

    public function searchSer($content, $startpage, $pagecount)
    {
        $Service = new UserController();

        $services = Service::where('ServiceIntroduction','like','%'.$content.'%')->orwhereRaw("CONCAT(`ServiceName`, `ServiceLocation`, 'ConnectPerson', 'ServiceArea') LIKE '%$content%'")->lists('ServiceID');
        $counts = count($services);
        $pages  = ceil($counts/$pagecount);
        $skipnum = ($startpage-1)*$pagecount;
        $services = Service::where('ServiceIntroduction','like','%'.$content.'%')->orwhereRaw("CONCAT(`ServiceName`, `ServiceLocation`, 'ConnectPerson', 'ServiceArea') LIKE '%$content%'")->orderBy('created_at', 'desc')->skip($skipnum)->take($pagecount)->lists('ServiceID');
// dd($services);
        $data = [];
        foreach ($services as $id) {
            $item = $Service->getInfo($id);
            if($item != 0) {
                $item['ServiceNumber'] = 'FW' . sprintf("%05d", $item['ServiceID']);
            }
            $data[] = $item;
        }
        return ['counts'=>$counts, 'pages'=>$pages, 'data'=>$data];
    }

    public function searchVid($content, $startpage, $pagecount)
    {
        $Video = new VideoController();

        $videos = Video::orwhereRaw("CONCAT(`VideoTitle`, `VideoDes`, 'VideoLabel') LIKE '%$content%'")->lists('VideoID');
        $counts = count($videos);
        $pages  = ceil($counts/$pagecount);
        $skipnum = ($startpage-1)*$pagecount;
        $videos = Video::orwhereRaw("CONCAT(`VideoTitle`, `VideoDes`, 'VideoLabel') LIKE '%$content%'")->orderBy('created_at', 'desc')->skip($skipnum)->take($pagecount)->lists('VideoID');
        $data = [];
        foreach ($videos as $id) {
            $item = $Video->getInfo($id);
            $data[] = $item;
        }
        return ['counts'=>$counts, 'pages'=>$pages, 'data'=>$data];
    }


    public function uploadFile(){
            //获取提交的数据
             $payload = app('request')->all();
          // $payload = app('request')->except('a');
            $proType = $payload['TypeID'];
            $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$proType)->pluck('TableName');
            $diffData = app('request')->except('ProArea','WordDes','VoiceDes','PictureDes1','PictureDes2','PictureDes3','token','access_token','UserID');
             // dd($diffData);
            //整理上传的图片，音频
             //$base_path = "./ziyaupload/users/"; //存放目录  
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
          
        // /*$validator = app('validator')->make($payload, $rules);
 
        // // 验证格式
        // if ($validator->fails()) {
        //     return $this->response->array(['error' => $validator->errors()]);
        // }
 
        // //事务处理,往项目信息表projectinfo和项目属性表spec01表插入数据
        DB::beginTransaction();
        try {
            $project = new Project();
 
            // $project->UserID = 1;
            $project->UserID = $this->auth->user()->toArray()['userid'];
            $project->TypeID = $payload['TypeID'];
            $project->ProArea = $payload['ProArea'];
            $project->WordDes = $payload['WordDes'];
            $project->VoiceDes = isset($payload['VoiceDes']) ? $payload['VoiceDes'] : '';
            $project->PictureDes1 = isset($payload['PictureDes1']) ? $payload['PictureDes1'] : '';
            $project->PictureDes2 = isset($payload['PictureDes2']) ?  $payload['PictureDes2'] : '';
            $project->PictureDes3 = isset($payload['PictureDes3']) ?  $payload['PictureDes3']  : '';
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
            return $this->response->array(['status_code'=>'200','success' => 'Create Pro Success']);
        } else {
            return $this->response->array(['status_code'=>'499','error' => 'Create Pro Error']);
        }    
    }

}

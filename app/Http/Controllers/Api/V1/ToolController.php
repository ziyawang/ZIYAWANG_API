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
        $collections = DB::table('T_P_COLLECTION')->select('ItemID','Type','CollectTime')->where('UserID',$UserID)->skip($skipnum)->take($pagecount)->get();

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
        $projects = Project::where('WordDes','like','%'.$content.'%')->skip($skipnum)->take($pagecount)->lists('ProjectID');

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
        $services = Service::where('ServiceIntroduction','like','%'.$content.'%')->orwhereRaw("CONCAT(`ServiceName`, `ServiceLocation`, 'ConnectPerson', 'ServiceArea') LIKE '%$content%'")->skip($skipnum)->take($pagecount)->lists('ServiceID');
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
}

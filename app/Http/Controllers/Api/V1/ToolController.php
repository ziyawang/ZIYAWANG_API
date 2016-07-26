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
            
            return $this->response->array(['status_code' => 200, 'msg' => 'cancel collect success']);
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

            return $this->response->array(['status_code' => 200, 'msg' => 'collect success']);
        } else {
            return $this->response->array(['status_code' => 200, 'msg' => 'collect error']);
        }
    }

    /**
     * 收藏列表
     *
     * @return mixed
     */
    public function collectList()
    {   
        $UserID = $this->auth->user()->UserID;
        $collections = DB::table('T_P_COLLECTION')->where('UserID',$UserID)->get();
    }

    /**
    *全站搜索
    *
    *@return mixed
    */
    public function search()
    {
        $payload = app('request')->all();
        $type = $payload['type'];
        $content = $payload['content'];
        switch ($type) {
            
            case '1'://
                $data = $this->searchPro($content);
                break;
            
            case '4'://
                $data = $this->searchSer($content);
                break;
        }

        return $this->response->array($data);
    }

    public function searchPro($content)
    {
        
    }

    public function searchSer($content)
    {
        $services = Service::where('ServiceIntroduction','like','%'.$content.'%')->orwhereRaw("CONCAT(`ServiceName`, `ServiceLocation`, 'ConnectPerson', 'ServiceArea') LIKE '%$content%'")->lists('ServiceID');
        dd($services);
    }
}

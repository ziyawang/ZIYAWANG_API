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

            return $this->response->array('collect success');
        } else {
            return $this->response->array('collect error');
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
}

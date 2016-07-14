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

class ToolController extends Controller
{
    /**
     * 收藏
     *
     * @return mixed
     */
    public function collect($type, $itemID)
    {   
        $UserID = $this->auth->user()->UserID;
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
                    break;
                
                case '2':
                    $table = 'T_V_VIDEOINFO';
                    break;

                case '3':
                    $table = 'T_N_NEWSINFO';
                    break;

                case '4':
                    $table = 'T_U_SERVICEINFO';
                    break;
            }

            $item = DB::table("$table")->find("$itemID");
            $item->CollectCount += 1;
            $item->save();

            $this->response->array('collect success');
        } else {
            $this->response->array('collect error');
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

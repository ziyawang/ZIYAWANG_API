<?php

/**
 * 视频发布、展示、详情控制器
 */
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Tymon\users\Exceptions\JWTException;
use JWTAuth;
use Cache;
use DB;
use App\User;
use App\Video;

class VideoController extends BaseController
{
    /**
     * 视频发布
     *
     * @param Request $request
     */
    public function create()
    {
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

        $project = new Video();
        $res = $project->create($payload);


        // 发布视频成功
        if ($res) {
            return $this->response->array(['success' => 'Create Video Success']);
        } else {
            return $this->response->array(['error' => 'Create Video Error']);
        }
    }

    /**
     * 视频列表
     *
     * @param Request $request
     */
    public function videoList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $VideoLabel = isset($payload['VideoLabel']) ?  $payload['VideoLabel'] : null;
        $order = isset($payload['order']) ?  $payload['order'] : null;
        $weight = isset($payload['weight']) ?  $payload['weight'] : null;

        if($weight){
            if($VideoLabel){
                $videos = Video::where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->orderBy('Order','desc')->lists('VideoID');
            } else {
                $videos = Video::where('Flag', 1)->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where('Flag', 1)->orderBy('Order','desc')->lists('VideoID');
            }
        } else {
            if(!$VideoLabel && !$order){
                $videos = Video::where('Flag', 1)->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where('Flag', 1)->orderBy('created_at','desc')->lists('VideoID');
            } elseif($VideoLabel && !$order) {
                $videos = Video::where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->orderBy('created_at','desc')->lists('VideoID');
            } elseif(!$VideoLabel && $order) {
                $videos = Video::where('Flag', 1)->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where('Flag', 1)->orderBy('ViewCount','desc')->lists('VideoID');
            } elseif($VideoLabel && $order) {
                $videos = Video::where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->lists('VideoID');
                $counts = count($videos);
                $pages = ceil($counts/$pagecount);
                $videos = Video::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'VideoLabel'=>$VideoLabel])->orderBy('ViewCount','desc')->lists('VideoID');
            }          
        }
        
        $data = [];
        foreach ($videos as $id) {
            $item = $this->getInfo($id);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    /**
     * 获取视频详情
     *
     * @param Request $request
     */
    public function getInfo($id) {
        $data = Video::select('VideoID','VideoTitle','VideoDes','VideoLogo','VideoThumb','VideoLabel','VideoAuthor','PublishTime','ViewCount','CollectionCount','VideoLink','VideoLink2')->where('VideoID',$id)->first()->toArray();
        return $data;
    }

    /**
     * 视频详情
     *
     * @param Request $request
     */
    public function videoInfo($id) {
        
        $data = $this->getInfo($id);
        Video::where('VideoID',$id)->increment('ViewCount');
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $data['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 2, 'ItemID' => $data['VideoID'], 'UserID' => $UserID])->get();
             if ($tmp) {
                $data['CollectFlag'] = 1;
             } else {
                $data['CollectFlag'] = 0;
             }
        }
        return $data;
    }
}

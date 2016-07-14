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
        $videotype = isset($payload['videotype']) ?  $payload['videotype'] : null;

        $pdata = Video::skip($skipnum)->take($pagecount)->get()->toArray();
        dd($data);
    }

    /**
     * 视频详情
     *
     * @param Request $request
     */
    public function videoInfo($id) {
        $data = Video::find($id);
        dd($data);
    }
}

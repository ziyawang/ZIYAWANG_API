<?php

/**
 * 资讯发布、展示、详情控制器
 */
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use DB;
use App\User;
use App\News;
use Cache;
use Tymon\users\Exceptions\JWTException;

class NewsController extends BaseController
{
     /**
     * 新闻资讯发布
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

        $project = new News();
        $res = $project->create($payload);


        // 发布资讯成功
        if ($res) {
            return $this->response->array(['success' => 'Create News Success']);
        } else {
            return $this->response->array(['error' => 'Create News Error']);
        }
    }

    /**
     * 视频列表
     *
     * @param Request $request
     */
    public function newsList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $videotype = isset($payload['videotype']) ?  $payload['videotype'] : null;

        $data = Video::skip($skipnum)->take($pagecount)->get()->toArray();
        dd($data);
    }

    /**
     * 新闻资讯详情
     *
     * @param Request $request
     */
    public function newsInfo($id) {
        $data = News::find($id);
        dd($data);
    }
}

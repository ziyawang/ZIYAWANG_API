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
     * 新闻列表
     *
     * @param Request $request
     */
    public function newsList() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $NewsLabel = isset($payload['NewsLabel']) ?  $payload['NewsLabel'] : null;
        $weight = isset($payload['weight']) ?  $payload['weight'] : null;

        if($weight){
            if($NewsLabel){
                $news = News::where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->orderBy('created_at','desc')->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->orderBy('created_at','desc')->lists('NewsID');
                }
            } else {
                $news = News::where(['Flag'=>1])->where('NewsLabel','<>','czgg')->where('NewsLabel','<>','zyjt')->where('NewsLabel','<>','hyyj')->lists('NewsID');
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->whereIn('NewsID',$news)->orderBy('Order','desc')->lists('NewsID');
            }
        } else {
            if(!$NewsLabel){
                $news = News::where('Flag', 1)->where('NewsLabel','<>','czgg')->where('NewsLabel','<>','zyjt')->where('NewsLabel','<>','hyyj')->lists('NewsID');
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->whereIn('NewsID',$news)->orderBy('created_at','desc')->lists('NewsID');
            } else {
                $news = News::where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->orderBy('created_at','desc')->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->orderBy('created_at','desc')->lists('NewsID');
                }
            }
        }

        $data = [];
        foreach ($news as $id) {
            $item = $this->getInfo($id);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    /**
     * 新闻列表PC
     *
     * @param Request $request
     */
    public function newsListPC() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $NewsLabel = isset($payload['NewsLabel']) ?  $payload['NewsLabel'] : null;
        $weight = isset($payload['weight']) ?  $payload['weight'] : null;

        if($weight){
            if($NewsLabel){
                $news = News::where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->orderBy('created_at','desc')->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
            } else {
                $news = News::where(['Flag'=>1])->where('NewsLabel','<>','czgg')->where('NewsLabel','<>','zyjt')->where('NewsLabel','<>','hyyj')->lists('NewsID');
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1])->orderBy('Order','desc')->lists('NewsID');
            }
        } else {
            if(!$NewsLabel){
                $news = News::where('Flag', 1)->where('NewsLabel','<>','czgg')->where('NewsLabel','<>','zyjt')->where('NewsLabel','<>','hyyj')->lists('NewsID');
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where('Flag', 1)->orderBy('created_at','desc')->lists('NewsID');
            } else {
                $news = News::where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
                $counts = count($news);
                $pages = ceil($counts/$pagecount);
                $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>$NewsLabel])->orderBy('created_at','desc')->lists('NewsID');
                if($NewsLabel == "hyzx"){
                    $news = News::skip($skipnum)->take($pagecount)->where(['Flag'=>1,'NewsLabel'=>'cjzx'])->orWhere(['Flag'=>1,'NewsLabel'=>'hydt'])->lists('NewsID');
                }
            }
        }

        $data = [];
        foreach ($news as $id) {
            $item = $this->getInfo($id);
            $data[] = $item;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    /**
     * 获取新闻详情
     *
     * @param Request $request
     */
    public function getInfo($id) {
        $data = News::select('NewsID','NewsTitle','NewsContent','NewsLogo','NewsThumb','NewsLabel','PublishTime','NewsAuthor','ViewCount','CollectionCount','Brief')->where('NewsID',$id)->first()->toArray();

        return $data;
    }

    /**
     * 新闻资讯详情
     *
     * @param Request $request
     */
    public function newsInfo($id) {
        
        $data = $this->getInfo($id);
        News::where('NewsID',$id)->increment('ViewCount');

        $pre  = News::select('NewsID','NewsTitle')->where('NewsID','<',$id)->where('Flag', 1)->orderBy('NewsID','desc')->first();
        $next = News::select('NewsID','NewsTitle')->where('NewsID','>',$id)->where('Flag', 1)->orderBy('NewsID','asc')->first();

        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        $data['CollectFlag'] = 0;
        if ($UserID) {
             $tmp = DB::table('T_P_COLLECTION')->where(['Type' => 3, 'ItemID' => $data['NewsID'], 'UserID' => $UserID])->get();
             if ($tmp) {
                $data['CollectFlag'] = 1;
             } else {
                $data['CollectFlag'] = 0;
             }


            //写查看新闻log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'check.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = $UserID;
            $log['type'] = 3;
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
            $log['type'] = 3;
            $log['itemid'] = $id;
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        }
        return $this->response->array(['data'=>$data,'pre'=>$pre, 'next'=>$next]);
    }
}

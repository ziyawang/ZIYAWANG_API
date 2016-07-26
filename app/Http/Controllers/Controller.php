<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Mail;

abstract class Controller extends BaseController
{

    use DispatchesJobs, ValidatesRequests;

    /**
     * 格式化返回接口数据
     *
     * @param $data
     * @param int $status_code
     * @return array
     */
    public function formatJson($data, $status_code = 200)
    {
        return ['data' => $data, 'status_code' => $status_code];
    }

    // 发送短信
    protected function _sendSms($mobile, $message, $action)
    {
        require(base_path().'/vendor/alidayu/TopSdk.php');
        date_default_timezone_set('Asia/Shanghai');

        $c = new \TopClient;
        $c->appkey = '23401348';//需要加引号
        $c->secretKey = 'b192055dbd09af7e7e98539698f67716';
        $c->format = 'xml';
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("");//暂时不填
        $req->setSmsType("normal");//默认可用
        $req->setSmsFreeSignName("资芽网");//设置短信免费符号名(需在阿里认证中有记录的)
        $req->setSmsParam("{\"code\":\"{$message}\"}");//设置短信参数
        $req->setRecNum($mobile);//设置接受手机号
        if($action == 'register'){
            $req->setSmsTemplateCode("SMS_12660435");//设置模板
        } elseif($action == 'login') {
            $req->setSmsTemplateCode("SMS_12670230");//设置模板
        }
        $resp = $c->execute($req);//执行

        if($resp->result->success)
        {
            return true;
        } 
        else
        {
            return false;
        }
    }

    //发送邮件
    protected function _sendMail($email, $title, $msg)
    {
        // $data = ['email'=>$email, 'name'=>1, 'uid'=>1, 'activationcode'=>1];
        $data = ['email'=>$email, 'title'=>$title, 'msg'=>$msg];
        Mail::send('activemail', $data, function($message) use($data)
        {
            $message->to($data['email'])->subject($data['title']);
        });
    }

    //增加浏览次数
    protected function _addView($type, $itemID)
    {
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
        $item->ViewCount += 1;
        $item->save();
    }
}

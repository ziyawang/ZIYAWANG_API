<?php

/**
 * 临时控制器
 */
namespace App\Http\Controllers\Api\V2;

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
use PDO;
use DB;

class MemberController extends BaseController
{
    public function memberList() {
        $data = DB::table('T_CONFIG_MEMBER')->get();
        return $this->response->array($data);
    }

    public function starList() {
        $data = DB::table('T_CONFIG_STAR')->get();
        return $this->response->array($data);
    }

    function payMoney() {

        // api_key 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Secret Key
        $api_key = 'sk_live_HW18yLG0ir9S1u5qP4KGa9uH';
        // $api_key = 'sk_test_0ePWjTrXLSu9frH8GK5ajnPG';//测试
        // app_id 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击你创建的应用->应用首页->应用 ID(App ID)
        $app_id = 'app_uz1SuHfHqDuT5u9u';

        // 此处为 Content-Type 是 application/json 时获取 POST 参数的示例
        $user = $this->auth->user();
        $payload = app('request')->all();
        if (empty($payload['channel'])) {
            echo 'channel is empty';
            exit();
        }

        $channel = strtolower($payload['channel']);
        if(isset($payload['paytype'])){
            if($payload['paytype'] == 'member'){
                $member = DB::table('T_CONFIG_MEMBER')->where('MemberID',$payload['payid'])->first();
                if($member->MemberName != $payload['payname']){
                    return $this->response->array(['status_code'=>'456','msg'=>'参数有误！']);
                }
                $amount = $member->Price;
                $orderNo = 'KT' . substr(time(),4) . mt_rand(1000,9999);
                $subject = isset($payload['subject']) ? $payload['subject']:'开通会员，类型：'.$member->MemberName;
                $body = '开通会员';
                $url = "http://ziyawang.com/ucenter/member";
                $metadata = ['paytype'=>'member','month'=>$member->Month,'payid'=>$payload['payid'],'userid'=>$user->userid];
            }
            if($payload['paytype'] == 'star'){
                $star = DB::table('T_CONFIG_STAR')->where('StarID',$payload['payid'])->first();
                if($star->StarName != $payload['payname']){
                    return $this->response->array(['status_code'=>'456','msg'=>'参数有误！']);
                }
                //先查是否已经有缴费成功的记录
                $tmp = DB::table('T_U_STAR')->where(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>1])->orWhere(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>2])->count();
                if($tmp > 0){
                    return $this->response->array(['status_code'=>'577', 'msg'=>'您已经支付过了！']);
                }
                //如果是重新上传
                $tmp = DB::table('T_U_STAR')->where(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>3])->count();
                if($tmp > 0){
                    DB::table('T_U_STAR')->where(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>3])->update(['State'=>1,'created_at'=>date('Y-m-d H:i:s',time())]);
                    return $this->response->array(['status_code'=>'200', 'msg'=>'上传成功！']);
                }

                $amount = $star->Price;
                $orderNo = 'RZ' . substr(time(),4) . mt_rand(1000,9999);
                $subject = isset($payload['subject']) ? $payload['subject']:'认证星级，类型：'.$star->StarName;
                $body = '服务方星级认证';
                $url = "http://ziyawang.com/ucenter/star";
                $metadata = ['paytype'=>'star','payid'=>$payload['payid'],'userid'=>$user->userid];
//如果payid=4或者5的时候
                if($payload['payid'] == 4 || $payload['payid'] == 5){
                    //整理插入数据
                    $freearr['StarID'] = $payload['payid'];
                    $freearr['PayName'] = $payload['payname'];
                    $freearr['PayMoney'] = 0;
                    $freearr['UserID'] = $user->userid;
                    $freearr['ServiceID'] = Service::where('UserID',$user->userid)->pluck('ServiceID');
                    $freearr['IP'] = $_SERVER['REMOTE_ADDR'];
                    $freearr['created_at'] = date('Y-m-d H:i:s',time());
                    $freearr['OrderNumber'] = $orderNo;
                    $freearr['State'] = 1;
                    $freearr['Resource'] = trim($payload['Resource'],',');
                    DB::table("T_U_STAR")->insert($freearr);
                    return $this->response->array(['status_code'=>'200', 'msg'=>'上传成功！']);
                }
            }
        } else {
            $amount = $payload['amount'];
            $ProjectID = isset($payload['ProjectID'])?$payload['ProjectID']: null;
            $orderNo = 'CZ' . substr(time(),4) . mt_rand(1000,9999);
            $subject = isset($payload['subject']) ? $payload['subject']:'充值金额';
            $body = '芽币充值';
            if($ProjectID){
                $url = "http://ziyawang.com/project/".$ProjectID;
            } else {
                $url = "http://ziyawang.com/ucenter/money/success";
            }    
            $metadata = ['paytype'=>'yabi','userid'=>$user->userid];
        }

        /**
         * 设置请求签名密钥，密钥对需要你自己用 openssl 工具生成，如何生成可以参考帮助中心：https://help.pingxx.com/article/123161；
         * 生成密钥后，需要在代码中设置请求签名的私钥(rsa_private_key.pem)；
         * 然后登录 [Dashboard](https://dashboard.pingxx.com)->点击右上角公司名称->开发信息->商户公钥（用于商户身份验证）
         * 将你的公钥复制粘贴进去并且保存->先启用 Test 模式进行测试->测试通过后启用 Live 模式
         */
        // 设置私钥内容方式1
        \Pingpp\Pingpp::setPrivateKeyPath(base_path() . '/public/your_rsa_private_key.pem');

        // 设置私钥内容方式2
        // \Pingpp\Pingpp::setPrivateKey(file_get_contents(__DIR__ . '/your_rsa_private_key.pem'));

        /**
         * $extra 在使用某些渠道的时候，需要填入相应的参数，其它渠道则是 array()。
         * 以下 channel 仅为部分示例，未列出的 channel 请查看文档 https://pingxx.com/document/api#api-c-new；
         * 或直接查看开发者中心：https://www.pingxx.com/docs/server/charge；包含了所有渠道的 extra 参数的示例；
         */
        $extra = array();
        switch ($channel) {
            case 'alipay_wap':
                $extra = array(
                    // success_url 和 cancel_url 在本地测试不要写 localhost ，请写 127.0.0.1。URL 后面不要加自定义参数
                    'success_url' => $url,
                    'cancel_url' => 'http://example.com/cancel'
                );
                break;
            case 'alipay_pc_direct':
                $extra = array(
                    // success_url 和 cancel_url 在本地测试不要写 localhost ，请写 127.0.0.1。URL 后面不要加自定义参数
                    'success_url' => $url,
                );
                break;      
            case 'bfb_wap':
                $extra = array(
                    'result_url' => 'http://example.com/result',// 百度钱包同步回调地址
                    'bfb_login' => true// 是否需要登录百度钱包来进行支付
                );
                break;
            case 'upacp_wap':
                $extra = array(
                    'result_url' => $url// 银联同步回调地址
                );
                break;
            case 'upacp_pc':
                $extra = array(
                    'result_url' => $url// 银联同步回调地址
                );
                break;
            case 'wx_pub':
                $extra = array(
                    'open_id' => 'openidxxxxxxxxxxxx'// 用户在商户微信公众号下的唯一标识，获取方式可参考 pingpp-php/lib/WxpubOAuth.php
                );
                break;
            case 'wx_pub_qr':
                $extra = array(
                    'product_id' => 'yabi'// 为二维码中包含的商品 ID，1-32 位字符串，商户可自定义
                );
                break;
        }


        \Pingpp\Pingpp::setApiKey($api_key);// 设置 API Key
        try {
            $ch = \Pingpp\Charge::create(
                array(
                    //请求参数字段规则，请参考 API 文档：https://www.pingxx.com/api#api-c-new
                    'subject'   => $subject,
                    'body'      => $body,
                    'amount'    => $amount,//订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
                    'order_no'  => $orderNo,// 推荐使用 8-20 位，要求数字或字母，不允许其他字符
                    'currency'  => 'cny',
                    'extra'     => $extra,
                    'channel'   => $channel,// 支付使用的第三方支付渠道取值，请参考：https://www.pingxx.com/api#api-c-new
                    'client_ip' => $_SERVER['REMOTE_ADDR'],// 发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
                    'app'       => array('id' => $app_id),
                    'metadata'  => $metadata
                )
            );

            //整理插入数据
            if(isset($payload['paytype'])){
                if($payload['paytype'] == 'member'){
                    $member = DB::table('T_CONFIG_MEMBER')->where('MemberID',$payload['payid'])->first();
                    $data = array();
                    // 会员开通记录
                    $data['UserID'] = $user->userid;
                    $data['Month'] = $member->Month;
                    $data['MemberID'] = $payload['payid'];
                    $data['OrderNumber'] = $orderNo;
                    $data['PayMoney'] = $member->Price;
                    $data['created_at'] = date('Y-m-d H:i:s',time());
                    $data['IP'] = $_SERVER['REMOTE_ADDR'];
                    $data['PayFlag'] = 0;
                    $data['PayName'] = $payload['payname'];
                    DB::table("T_U_MEMBER")->insert($data);
                }
                if($payload['paytype'] == 'star'){
                    $star = DB::table('T_CONFIG_STAR')->where('StarID',$payload['payid'])->first();
                    $data = array();
                    // 星级开通
                    $data['StarID'] = $payload['payid'];
                    $data['PayName'] = $payload['payname'];
                    $data['PayMoney'] = $star->Price;
                    $data['UserID'] = $user->userid;
                    $data['ServiceID'] = Service::where('UserID',$user->userid)->pluck('ServiceID');
                    $data['OrderNumber'] = $orderNo;
                    $data['created_at'] = date('Y-m-d H:i:s',time());
                    $data['IP'] = $_SERVER['REMOTE_ADDR'];
                    $data['State'] = 0;
                    DB::table("T_U_STAR")->insert($data);
                }
            } else {
                $add = DB::table('T_CONFIG_RATE')->where('RealMoney',$amount)->pluck('add');
                $data = array();
                $data['UserID'] = $user->userid;
                $data['Type'] = 1;
                $data['Money'] = intval($amount)/10 + $add;
                $data['OrderNumber'] = $orderNo;
                // $data['Account'] = $user->Account + floatval($amount);
                $data['created_at'] = date('Y-m-d H:i:s',time());
                $data['timestamp'] = time();
                $data['IP'] = $_SERVER['REMOTE_ADDR'];
                $data['RealMoney'] = $amount;
                $data['Flag'] = 0;

                DB::table("T_U_MONEY")->insert($data);
            }

            echo $ch;// 输出 Ping++ 返回的支付凭据 Charge
        } catch (\Pingpp\Error\Base $e) {
            // 捕获报错信息
            if ($e->getHttpStatus() != NULL) {
                header('Status: ' . $e->getHttpStatus());
                echo $e->getHttpBody();
            } else {
                echo $e->getMessage();
            }
        }
    }

    public function applePay() {
        $user = $this->auth->user();
        $payload = app('request')->all();

        if(isset($payload['paytype'])){
            if($payload['paytype'] == 'member'){
                $member = DB::table('T_CONFIG_MEMBER')->where('MemberID',$payload['payid'])->first();
                $month = $member->Month;
                if($member->MemberName != $payload['payname']){
                    return $this->response->array(['status_code'=>'456','msg'=>'参数有误！']);
                }
                $arr['OrderNumber'] = 'KT' . substr(time(),4) . mt_rand(1000,9999);
                $arr['MemberID'] = $payload['payid'];
                $arr['PayMoney'] = $member->Price;
                $arr['Month'] = $member->Month;
                $arr['UserID'] = $user->userid;
                $arr['IP'] = $_SERVER['REMOTE_ADDR'];
                $arr['BackNumber'] = $payload['backnumber'];
                $arr['Channel'] = 'iap';
                $arr['PayFlag'] = 1;
                $arr['PayName'] = $payload['payname'];

                // 先查有没有同类型会员
                $tmp = DB::table('T_U_MEMBER')->where(['PayName'=>$payload['payname'],'UserID'=>$user->userid])->where('Over','<>',1)->orderBy('EndTime','desc')->first();
                if($tmp){
                    //判断,到期就不管，如果没到期
                    if(strtotime($tmp->EndTime) > time()){
                        $arr['StartTime'] = $tmp->EndTime;
                    } else {
                        $arr['StartTime'] = date('Y-m-d H:i:s',time());
                    }
                } else {
                    $arr['StartTime'] = date('Y-m-d H:i:s',time());
                }
                $arr['EndTime'] = date('Y-m-d H:i:s',strtotime("+$month months", strtotime($arr['StartTime'])));

                if($member->YB > 0){
                    //赠送芽币
                    //整理插入数据
                    $data = array();
                    $data['UserID'] = $user->userid;
                    $data['Type'] = 1;
                    $data['Money'] = $member->YB;
                    $data['OrderNumber'] = 'ZS' . substr(time(),4) . mt_rand(1000,9999);
                    $data['Account'] = $user->Account + $member->YB;
                    $data['Flag'] = 1;
                    $data['created_at'] = date('Y-m-d H:i:s',time());
                    $data['IP'] = $_SERVER['REMOTE_ADDR'];
                    $data['timestamp'] = time();
                    $data['Operates'] = "开通" . $member->Content . "，赠送" . $member->YB . "个芽币";

                    DB::beginTransaction();
                    try {
                        DB::table("T_U_MONEY")->insert($data);
                        $user->Account = $user->Account + $member->YB;
                        $user->save();
                        DB::table('T_U_MEMBER')->insert($arr);

                        DB::commit();
                    } catch (Exception $e){
                        DB::rollback();
                        throw $e;
                    }
                    if(!isset($e)){
                        return 'ok';
                    }
                }

                DB::table('T_U_MEMBER')->insert($arr);
                return "ok";
            }
            if($payload['paytype'] == 'star'){
                //先查是否已经有缴费成功的记录
                $tmp = DB::table('T_U_STAR')->where(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>1])->orWhere(['UserID'=>$user->userid,'StarID'=>$payload['payid'],'State'=>2])->count();
                if($tmp > 0){
                    return $this->response->array(['status_code'=>'577', 'msg'=>'您已经支付过了！']);
                }
                $star = DB::table('T_CONFIG_STAR')->where('StarID',$payload['payid'])->first();
                $arr = array();
                // 星级开通
                $arr['StarID'] = $payload['payid'];
                $arr['PayName'] = $payload['payname'];
                $arr['PayMoney'] = $star->Price;
                $arr['UserID'] = $user->userid;
                $arr['ServiceID'] = Service::where('UserID',$user->userid)->pluck('ServiceID');
                $arr['OrderNumber'] = 'RZ' . substr(time(),4) . mt_rand(1000,9999);
                $arr['created_at'] = date('Y-m-d H:i:s',time());
                $arr['IP'] = $_SERVER['REMOTE_ADDR'];
                $arr['BackNumber'] = $payload['backnumber'];
                $arr['Channel'] = 'iap';
                $arr['State'] = 1;
                DB::table("T_U_STAR")->insert($arr);
                return 'ok';
            }
        } else {
            $amount = $payload['amount'];
            $BackNumber = $payload['backnumber'];
            $orderNo = 'CZ' . substr(time(),4) . mt_rand(1000,9999);
            $user = $this->auth->user();

            //整理插入数据
            $data = array();
            $data['UserID'] = $user->userid;
            $data['Type'] = 1;
            $data['OrderNumber'] = $orderNo;
            // $data['Account'] = $user->Account + floatval($amount);
            $data['created_at'] = date('Y-m-d H:i:s',time());
            $data['timestamp'] = time();
            $data['IP'] = $_SERVER['REMOTE_ADDR'];
            $data['RealMoney'] = $amount;
            //赠送的钱
            $add = DB::table('T_CONFIG_RATE')->where('RealMoney',$amount)->pluck('add');
            $data['Money'] = intval($amount)/10 + $add;
            $data['Flag'] = 1;
            $data['Channel'] = 'iap';
            $data['Operates'] = '充值' . $data['Money'] . '芽币';
            if($add > 0 ){
                $data['Operates'] = '充值' . ($data['Money']-$add) . '芽币，赠送了'.$add.'个芽币';
            }
            $data['Account'] = $user->Account + $amount/10 + $add;
            $data['BackNumber'] = $BackNumber;

            $user->Account = $data['Account'];

            DB::beginTransaction();
            try {
                DB::table("T_U_MONEY")->insert($data);
                $user->save();

                DB::commit();
            } catch (Exception $e){
                DB::rollback();
                throw $e;
            }

            if(isset($e)){
                return $this->response->array(['status_code'=>'421', 'msg'=>'插入记录失败，请重试！']);
            }
            return $this->response->array(['status_code'=>'200', 'msg'=>'插入记录成功！']);
        }
    }

    public function mybill() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        
        $UserID = $this->auth->user()->toArray()['userid'];
        $counts = DB::table('T_U_MEMBER')->where('UserID', $UserID)->where('PayFlag', 1)->orderBy('MemPayID','desc')->count();
        $pages = ceil($counts/$pagecount);
        $data = DB::table('T_U_MEMBER')->join('T_CONFIG_MEMBER','T_U_MEMBER.MemberID','=','T_CONFIG_MEMBER.MemberID')->where('UserID', $UserID)->where('PayFlag', 1)->select('StartTime','EndTime','OrderNumber','MemberName','PayMoney','Content')->orderBy('MemPayID','desc')->skip($skipnum)->take($pagecount)->get();
        foreach ($data as $v) {
            $v->Operates = "开通".$v->Content;
        }
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    //更新会员状态
    protected function _upMember($userid){
        DB::table('T_U_MEMBER')->where('EndTime','<',date('Y-m-d H:i:s',time()))->update(['Over'=>1]);
        DB::setFetchMode(PDO::FETCH_ASSOC);
        $arr =  DB::table('T_U_MEMBER')->join('T_CONFIG_MEMBER','T_U_MEMBER.MEMBERID','=','T_CONFIG_MEMBER.MEMBERID')->where(['UserID'=>$userid,'Over'=>0,'PayFlag'=>1])->select('EndTime','TypeID','MemberName')->orderBy('MemPayID','desc')->get();
        DB::setFetchMode(PDO::FETCH_CLASS);
        $rightarr = [];
        $showrightarr = [];
        foreach ($arr as $a) {
            $tmp = explode(',', $a['TypeID']);
            $rightarr = array_merge($rightarr, $tmp);
            if(!isset($showrightarr[$a['MemberName']])){
                $showrightarr[$a['MemberName']] = $a['EndTime'];                 
            }
        }
        $right = implode(',', array_unique($rightarr));
        $showright = json_encode($showrightarr);
        DB::table('users')->where('userid',$userid)->update(['right'=>$right,'showright'=>$showright]);
    }
}

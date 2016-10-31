<?php

/**
 * 亮亮 8.23新增功能控制器
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

class ZLLController extends BaseController
{
    public function matchPro() {
        $payload = app('request')->all();
        $opro = Project::where('ProjectID', $payload['ProjectID'])->first();
        $diffTableName = DB::table('T_P_PROJECTTYPE')->where('TypeID',$opro->TypeID)->pluck('TableName');
        $matchpros = DB::table("$diffTableName")->where('ProjectID', '<>', $payload['ProjectID'])->lists('ProjectID');
        $matchpros = DB::table('T_P_PROJECTINFO')->where('CertifyState',1)->where('PublishState','<>','2')->whereIn('ProjectID',$matchpros)->lists('ProjectID');

        $Project = new ProjectController();
        shuffle($matchpros);
        $matchpros = array_slice($matchpros, 0, 3);
        $data = [];
        foreach ($matchpros as $id) {
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
            $item['Informant'] = isset($item['Informant']) ? $item['Informant'] : '';
            $item['Buyer'] = isset($item['Buyer']) ? $item['Buyer'] : '';
            $item['ProjectNumber'] = 'FB' . sprintf("%05d", $item['ProjectID']);
            $item['PublishTime'] = substr($item['PublishTime'], 0,10);
            $data[] = $item;
        }
         // dd($data);
        return $this->response->array(['data'=>$data]);
    }

    public function matchSer() {
        $payload = app('request')->all();
        $oser = Service::where('ServiceID', $payload['ServiceID'])->first();
        $ServiceType = explode(',', $oser->ServiceType);
        $matchsers = [];
        foreach ($ServiceType as $type) {
            $services = DB::table('T_U_SERVICEINFO')->where('ServiceType','like','%'.$type.'%')->lists('ServiceID');
            $services = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->lists('ServiceID');
            // dd($services);
            $matchsers = array_merge($services, $matchsers);
        }
        $matchsers = array_values(array_unique($matchsers));
        $Service = new UserController();
        shuffle($matchsers);
        $matchsers = array_slice($matchsers, 0, 3);
        $data = [];
        foreach ($matchsers as $id) {
            $item = $Service->getInfo($id);
            if($item != 0) {
                $item['ServiceNumber'] = 'FW' . sprintf("%05d", $item['ServiceID']);
            }
            $data[] = $item;
        }
        return $this->response->array(['data'=>$data]);
    }


    public function matchProSer() {
        $payload = app('request')->all();
        $opro = Project::where('ProjectID', $payload['ProjectID'])->first();
        $type = $opro->TypeID;
        $type = sprintf("%02d", $type);
        if($type == '09'){
            $type = '02';
        } elseif($type == '13'){
            $type = '12';
        }
        $services = DB::table('T_U_SERVICEINFO')->where('ServiceType','like','%'.$type.'%')->lists('ServiceID');
        $services = DB::table('T_P_SERVICECERTIFY')->where('T_P_SERVICECERTIFY.State','1')->whereIn('ServiceID',$services)->lists('ServiceID');
        // dd($services);
        $services = array_values(array_unique($services));
        $Service = new UserController();
        shuffle($services);
        $services = array_slice($services, 0, 3);
        $data = [];
        foreach ($services as $id) {
            $item = $Service->getInfo($id);
            if($item != 0) {
                $item['ServiceNumber'] = 'FW' . sprintf("%05d", $item['ServiceID']);
            }
            $data[] = $item;
        }
        return $this->response->array(['data'=>$data]);
    }


    function sendmail() {
        //给客服发送邮件
        $phonenumber = app('request')->get('phonenumber');
        $email = '3153679024@qq.com';    
        // $email = 'zll@ziyawang.com';    
        $title = '新会员:' . $phonenumber . '注册成功！';
        $message = '手机号为' . $phonenumber . '的用户注册成为资芽网会员';

        $this->_sendMail($email, $title, $message);
    } 

    function test() {
        // $phonenumber = '111';

        // $fp = fsockopen("api.ziyawang.com", 80, $errno, $errstr, 30); 
        // if ($fp) {
        //     $header  = "GET /v1/sendmail?access_token=token&phonenumber=$phonenumber HTTP/1.1\r\n";
        //     $header .= "Host: api.ziyawang.com\r\n";
        //     $header .= "Connection: Close\r\n\r\n";//长连接关闭

        //     fwrite($fp, $header); 
        //     fclose($fp); 
        // }

        return 1;
    }

    function payMoney() {

        // api_key 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Secret Key
        $api_key = 'sk_live_HW18yLG0ir9S1u5qP4KGa9uH';
        // app_id 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击你创建的应用->应用首页->应用 ID(App ID)
        $app_id = 'app_uz1SuHfHqDuT5u9u';

        // 此处为 Content-Type 是 application/json 时获取 POST 参数的示例
        $payload = app('request')->all();
        if (empty($payload['channel']) || empty($payload['amount'])) {
            echo 'channel or amount is empty';
            exit();
        }
        $channel = strtolower($payload['channel']);
        $amount = $payload['amount'];
        $ProjectID = isset($payload['ProjectID'])?$payload['ProjectID']: null;
        $orderNo = 'CZ' . substr(time(),4) . mt_rand(1000,9999);
        $subject = isset($payload['subject']) ? $payload['subject']:'充值金额';
        $user = $this->auth->user();
        if($ProjectID){
            $url = "http://ziyawang.com/project/".$ProjectID;
        } else {
            $url = "http://ziyawang.com/ucenter/money/success";
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
            case 'yeepay_wap':
                $extra = array(
                    'product_category' => '1',// 商品类别码参考链接 ：https://www.pingxx.com/api#api-appendix-2
                    'identity_id'=> 'your identity_id',// 商户生成的用户账号唯一标识，最长 50 位字符串
                    'identity_type' => 1,// 用户标识类型参考链接：https://www.pingxx.com/api#yeepay_identity_type
                    'terminal_type' => 1,// 终端类型，对应取值 0:IMEI, 1:MAC, 2:UUID, 3:other
                    'terminal_id'=>'your terminal_id',// 终端 ID
                    'user_ua'=>'your user_ua',// 用户使用的移动终端的 UserAgent 信息
                    'result_url'=>'http://example.com/result'// 前台通知地址
                );
                break;
            case 'jdpay_wap':
                $extra = array(
                    'success_url' => 'http://example.com/success',// 支付成功页面跳转路径
                    'fail_url'=> 'http://example.com/fail',// 支付失败页面跳转路径
                    /**
                    *token 为用户交易令牌，用于识别用户信息，支付成功后会调用 success_url 返回给商户。
                    *商户可以记录这个 token 值，当用户再次支付的时候传入该 token，用户无需再次输入银行卡信息
                    */
                    'token' => 'dsafadsfasdfadsjuyhfnhujkijunhaf' // 选填
                );
                break;
        }


        \Pingpp\Pingpp::setApiKey($api_key);// 设置 API Key
        try {
            $ch = \Pingpp\Charge::create(
                array(
                    //请求参数字段规则，请参考 API 文档：https://www.pingxx.com/api#api-c-new
                    'subject'   => $subject,
                    'body'      => '芽币充值',
                    'amount'    => $amount,//订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
                    'order_no'  => $orderNo,// 推荐使用 8-20 位，要求数字或字母，不允许其他字符
                    'currency'  => 'cny',
                    'extra'     => $extra,
                    'channel'   => $channel,// 支付使用的第三方支付渠道取值，请参考：https://www.pingxx.com/api#api-c-new
                    'client_ip' => $_SERVER['REMOTE_ADDR'],// 发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
                    'app'       => array('id' => $app_id)
                )
            );

            //整理插入数据
            $data = array();
            $data['UserID'] = $user->userid;
            $data['Type'] = 1;
            $data['Money'] = intval($amount)/10;
            $data['OrderNumber'] = $orderNo;
            // $data['Account'] = $user->Account + floatval($amount);
            $data['created_at'] = date('Y-m-d H:i:s',time());
            $data['timestamp'] = time();
            $data['IP'] = $_SERVER['REMOTE_ADDR'];
            $data['RealMoney'] = $amount;
            $data['Flag'] = 0;

            DB::table("T_U_MONEY")->insert($data);

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


    function payResult() {
        $payload = app('request')->all();
        $str = serialize($payload);
        file_put_contents('./test.txt', $str);
    }


    function consume() {
        $payload = app('request')->all();

        //数据库查询信息单价
        $ProjectID = $payload['ProjectID'];
        $Project = Project::where('ProjectID', $ProjectID)->first();
        $user = $this->auth->user();
        //判断是否是收费信息
        if($Project->Member != 2){
            return $this->response->array(['status_code' => '416', 'msg' => '非收费信息']);
        }
        $phonenumber = User::where('userid', $Project->UserID)->pluck('phonenumber');
        $price = $Project->Price;
        //判断是否已经付费
        $tmp = DB::table('T_U_MONEY')->where('UserID', $user->userid)->where('ProjectID', $Project->ProjectID)->first();
        if($tmp){
            return $this->response->array(['status_code' => '417', 'msg' => '已支付', 'phonenumber' => $phonenumber]);
        }
        //用户余额小于价格返回余额不足
        if($user->Account < $price){
            return $this->response->array(['status_code' => '418', 'msg' => '余额不足']);
        }

        //整理插入数据
        $data = array();
        $data['UserID'] = $user->userid;
        $data['Type'] = 2;
        $data['Money'] = $price;
        $data['OrderNumber'] = 'XF' . substr(time(),4) . mt_rand(1000,9999);
        $data['Account'] = $user->Account - $price;
        $data['ProjectID'] = $ProjectID;
        $data['Flag'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $data['IP'] = $_SERVER['REMOTE_ADDR'];
        $data['timestamp'] = time();
        $data['Operates'] = "查看信息编号为:FB" . sprintf("%05d", $ProjectID) . "的信息，消费" . $price . "芽币";


        DB::beginTransaction();
        try {
            DB::table("T_U_MONEY")->insert($data);
            $user->Account = $user->Account - $price;
            $user->save();

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        if(!isset($e)){
            return $this->response->array(['status_code' => '200', 'phonenumber' => $phonenumber, 'Account' => $data['Account']]);
        }
    }


    function appConsume() {
        $payload = app('request')->all();

        //数据库查询信息单价
        $ProjectID = $payload['ProjectID'];
        $Project = Project::where('ProjectID', $ProjectID)->first();
        $user = $this->auth->user();
        $UserID = $user->userid;
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
        $RushTime = date("Y-m-d H:i:s",strtotime('now'));
        //判断是否是收费信息
        if($Project->Member != 2){
            $tmp = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->count();
            if($tmp != 0){
                return $this->response->array(['status_code'=>'200','msg'=>'您已约谈，请不要重复约谈！']);
            }
            DB::table('T_P_RUSHPROJECT')->insert(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID, 'RushTime'=>$RushTime]);
            return $this->response->array(['status_code' => '200', 'msg' => '非收费信息', 'Account' => $user->Account]);
        }
        $phonenumber = User::where('userid', $Project->UserID)->pluck('phonenumber');
        $price = $Project->Price;
        //判断是否已经付费
        $tmp = DB::table('T_U_MONEY')->where('UserID', $UserID)->where('ProjectID', $Project->ProjectID)->first();
        if($tmp){
            return $this->response->array(['status_code' => '417', 'msg' => '已支付', 'phonenumber' => $phonenumber]);
        }
        //用户余额小于价格返回余额不足
        if($user->Account < $price){
            return $this->response->array(['status_code' => '418', 'msg' => '余额不足']);
        }

        $puber = Project::where("ProjectID", $ProjectID)->pluck('UserID');

        if($puber == $UserID){
            return $this->response->array(['status_code'=>'200','msg'=>'您不能和自己约谈！']);
        }
        $isSer = Service::join('T_P_SERVICECERTIFY', 'T_P_SERVICECERTIFY.ServiceID', '=', 'T_U_SERVICEINFO.ServiceID')->where(['T_U_SERVICEINFO.UserID'=>$UserID, 'T_P_SERVICECERTIFY.State'=>1])->count();
        if($isSer == 0){
            return $this->response->array(['status_code'=>'200','msg'=>'您还没有认证成为服务方，还不能约谈，快去认证吧！']);
        }


        $tmp = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->count();
        if($tmp != 0){
            $status = DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->pluck('CooperateFlag');
            if($status == 3){
                DB::table('T_P_RUSHPROJECT')->where(['ProjectID'=>$ProjectID,'ServiceID'=>$ServiceID])->update(['CooperateFlag'=>0]);
            }
            return $this->response->array(['status_code'=>'200','msg'=>'您已约谈，请不要重复约谈！']);
        }
        DB::table('T_P_RUSHPROJECT')->insert(['ProjectID'=>$ProjectID, 'ServiceID'=>$ServiceID, 'RushTime'=>$RushTime]);

        //整理插入数据
        $data = array();
        $data['UserID'] = $user->userid;
        $data['Type'] = 2;
        $data['Money'] = $price;
        $data['OrderNumber'] = 'XF' . substr(time(),4) . mt_rand(1000,9999);
        $data['Account'] = $user->Account - $price;
        $data['ProjectID'] = $ProjectID;
        $data['Flag'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $data['IP'] = $_SERVER['REMOTE_ADDR'];
        $data['timestamp'] = time();
        $TypeName = Project::join('T_P_PROJECTTYPE', 'T_P_PROJECTINFO.TypeID', '=', 'T_P_PROJECTTYPE.TypeID')->where('ProjectID',$ProjectID)->pluck('TypeName');
        $data['Operates'] = "查看信息编号为:FB" . sprintf("%05d", $ProjectID) . "的" . $TypeName . "，消费" . $price . "芽币";


        DB::beginTransaction();
        try {
            DB::table("T_U_MONEY")->insert($data);
            $user->Account = $user->Account - $price;
            $user->save();

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }

        if(!isset($e)){
            return $this->response->array(['status_code' => '200', 'phonenumber' => $phonenumber, 'Account' => $data['Account']]);
        }
    }

    public function mybill() {
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $Type = (isset($payload['Type']) && $payload['Type'] != 'null' && $payload['Type'] != '' ) ?  $payload['Type'] : 'default';
        if($Type == 'default' || $Type == 'null' || $Type == '') {
            $Type = [1,2];
        } else {
            $Type = array($Type);
        }
        $starttime = (isset($payload['starttime']) && $payload['starttime'] != '') ? $payload['starttime'] : date('Y-m-d H:i:s',0);
        $endtime = (isset($payload['endtime']) && $payload['endtime'] != '') ? $payload['endtime'] : date('Y-m-d H:i:s',time());

        $UserID = $this->auth->user()->toArray()['userid'];
        $counts = DB::table('T_U_MONEY')->where('UserID', $UserID)->where('Flag', 1)->whereIn('Type', $Type)->whereBetween('created_at',[$starttime, $endtime])->count();
        $pages = ceil($counts/$pagecount);
        $data = DB::table('T_U_MONEY')->where('UserID', $UserID)->where('Flag', 1)->whereIn('Type', $Type)->whereBetween('created_at',[$starttime, $endtime])->orderBy('created_at','desc')->skip($skipnum)->take($pagecount)->get();
        return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage]);
    }

    public function webhooks() {
        $payload = app('request')->all()['data']['object'];
        $OrderNumber = $payload['order_no'];
        $Channel = $payload['channel'];
        $BackNumber = $payload['transaction_no'];
        $money = DB::table('T_U_MONEY')->where('OrderNumber', $OrderNumber)->first();
        if($money){
            $user = User::where('userid', $money->UserID)->first();
            $Account = $user->Account + $money->Money;
            $Operates = '充值' . $money->Money . '芽币';
            $user->Account = $Account;
            DB::beginTransaction();
            try {
                DB::table('T_U_MONEY')->where('OrderNumber', $OrderNumber)->update(['Flag'=>1,'Account'=>$Account, 'BackNumber'=>$BackNumber, 'Channel'=>$Channel, 'Operates'=>$Operates]);
                $user->save();

                DB::commit();
            } catch (Exception $e){
                DB::rollback();
                throw $e;
            }
        }
        // $str = serialize($payload);
        // file_put_contents('./test.txt', $str);
        if(!isset($e)){
            return 'ok';
        }
    }

    public function payList() {
        $data = DB::table('T_CONFIG_RATE')->get();
        return $this->response->array($data);
    }


    public function changeUserName() {
        $payload = app('request')->all();
        $UserID = $this->auth->user()->toArray()['userid'];
        $username = $payload['username'];
        $tmp = User::where('username', $username)->count();
        if($tmp != 0) {
            return $this->response->array(['status_code'=>'419', 'msg'=>'昵称已存在！']);
        }
        $res = User::where('userid', $UserID)->update(['username'=>$username]);
        if($res){
            return $this->response->array(['status_code'=>'200', 'msg'=>'昵称修改成功！']);
        } else {
            return $this->response->array(['status_code'=>'409', 'msg'=>'昵称修改失败！']);
        }
    }

    public function applePay() {
        $payload = app('request')->all();
        $amount = $payload['amount'];
        $BackNumber = $payload['backnumber'];
        $orderNo = 'CZ' . substr(time(),4) . mt_rand(1000,9999);
        $user = $this->auth->user();

        //整理插入数据
        $data = array();
        $data['UserID'] = $user->userid;
        $data['Type'] = 1;
        $data['Money'] = intval($amount)/10;
        $data['OrderNumber'] = $orderNo;
        // $data['Account'] = $user->Account + floatval($amount);
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $data['timestamp'] = time();
        $data['IP'] = $_SERVER['REMOTE_ADDR'];
        $data['RealMoney'] = $amount;
        $data['Flag'] = 1;
        $data['Channel'] = 'iap';
        $data['Operates'] = '充值' . $data['Money'] . '芽币';
        $data['Account'] = $user->Account + $amount/10;
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

    public function enroll(){
        $payload = app('request')->all();
        $str = "姓名：" . $payload['name'] . "\n";
        $str = $str . "公司名称：" . $payload['company'] . "\n";
        $str = $str . "电话：" . $payload['phonenumber'] . "\n";
        $str = $str . "邮箱：" . (isset($payload['mail'])?$payload['mail']:'') . "\n";
        $str = $str . "职务：" . (isset($payload['job'])?$payload['job']:'') . "\n";
        $str = $str . "报名时间：" . date('Y-m-d H:i:s', time()) . "\n";
        $str = $str . "用户IP：" . $_SERVER["REMOTE_ADDR"] . "\n\n\n";

        file_put_contents('./lists.txt', $str, FILE_APPEND);
        return $this->response->array(['status_code'=>'200', 'msg'=>'恭喜您报名成功！工作人员近期会联系您，确认报名信息，请保持电话畅通！']);
    }

    public function getPayFlag(){
        $payload = app('request')->all();
        $ProjectID = $payload['ProjectID'];
        $UserID = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : null;
        if(!$UserID){
            return $this->response->array(['status_code' => '400', 'msg' => 'token错误！']);
        }
        $ServiceID = Service::where('UserID',$UserID)->pluck('ServiceID');
        //支付标记
        $tmp = DB::table('T_U_MONEY')->where('UserID', $UserID)->where('ProjectID', $ProjectID)->get();
        $tmpp = DB::table('T_P_RUSHPROJECT')->where(['ServiceID'=>$ServiceID, 'ProjectID'=>$ProjectID])->get();
        if ($tmp || $tmpp) {
            $PayFlag = 1;
        } else {
            $PayFlag = 0;
        }
        return $this->response->array(['status_code' => '200', 'PayFlag' => $PayFlag]);        
    }

    public function report(){
        $payload = app('request')->all();
        $data = [];
        $data['UserID'] = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : 0;
        // if(!$data['UserID']){
        //     return $this->response->array(['status_code' => '400', 'msg' => 'token错误！']);
        // }
        $data['Type'] = (int)$payload['Type'];
        $data['ItemID'] = $payload['ItemID'];
        $data['Channel'] = $payload['Channel'];
        $data['ReasonID'] = trim($payload['ReasonID'], ',');
        $reasons = explode(',', $data['ReasonID']);
        $data['Content'] = "";
        foreach ($reasons as $reason) {
            $reason = (int)$reason;    
            switch ($reason) {
                case 1:
                    if($data['Type'] == 1){
                        $data['Content'] .= "已合作或已处置；";
                    } elseif($data['Type'] == 2){
                        $data['Content'] .= "服务方描述与实事不符；";
                    }
                    break;

                case 2:
                    $data['Content'] .= "虚假信息；";
                    break;
                
                case 3:
                    $data['Content'] .= "泄露隐私；";
                    break;

                case 4:
                    $data['Content'] .= "垃圾广告；";
                    break;

                case 5:
                    $data['Content'] .= "人身攻击；";
                    break;

                case 6:
                    $data['Content'] .= "无法联系；";
                    break;
            }
        }
        $data['Content'] = rtrim($data['Content'], '；');
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $res = DB::table("T_U_REPORT")->insert($data);
        if($res){
            return $this->response->array(['status_code' => '200', 'msg' => '举报成功，请等待客服审核查实！']);
        } else {
            return $this->response->array(['status_code' => '200', 'msg' => '未知错误，请稍后重试！']);
        }
    }

    public function checkService(){
        $payload = app('request')->all();
        $data = [];
        $data['UserID'] = $this->auth->user() ? $this->auth->user()->toArray()['userid'] : 0;
        $data['ServiceID'] = $payload['ServiceID'];
        $data['Channel'] = $payload['Channel'];
        $data['IP'] = $_SERVER["REMOTE_ADDR"];
        $data['created_at'] = date('Y-m-d H:i:s', time());
        DB::beginTransaction();
        try {
            DB::table("T_C_SERVICE")->insert($data);
            SERVICE::where('ServiceID', $payload['ServiceID'])->increment('CheckCount');

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    public function appStart(){
        $payload = app('request')->all();

        $User = $this->auth->user() ? $this->auth->user()->toArray(): null;

        if($User){
            //写登录log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'login.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = $User['userid'];
            $log['phonenumber'] = $User['phonenumber'];
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $log['channel'] = $payload['Channel'];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        } else {
            //写登录log
            $log_path = base_path().'/storage/logs/data/';
            $log_file_name = 'login.log';
            // $log_file_name = date('Ymd', time()) . '.log';
            $Logs = new \App\Logs($log_path,$log_file_name);
            $log = array();
            $log['userid'] = 0;
            $log['phonenumber'] = $User['phonenumber'];
            $log['time'] = time();
            $log['ip'] = $_SERVER["REMOTE_ADDR"];
            $log['channel'] = $payload['Channel'];
            $logstr = serialize($log);
            $res = $Logs->setLog($logstr); 
        }
    }

}

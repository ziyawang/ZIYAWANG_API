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

}

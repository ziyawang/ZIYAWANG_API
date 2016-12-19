<?php   
/*
*App版本2方法
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
use App\Message;
use App\Send;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;
class LdsController extends  BaseController{
	//app第二版本中的轮播图接口
	public function  banner(){
		$datas=DB::table("T_P_BANNER")->orderBy("created_at","desc")->take(4)->get();
	       	return $datas;
	}

	//短信通知下载app，从融云获取聊天记录
	public function  sendMessage(){
		$datas=Message::select()->get()->toArray();
		//dd($datas);
		foreach($datas as $data){
			$fromUserId=$data['fromUserId'];
			$targetId=$data['targetId'];
			if($fromUserId==0 || $targetId==0){
				continue;
			}
			$results=Message::select()->where(['fromUserId'=>$targetId,'targetId'=>$fromUserId])->get()->toArray();
			if(!$results){
				$phoneNumbers=DB::table("users")->select("phonenumber")->where("userid",$targetId)->get();
				
				foreach($phoneNumbers as $value){
					$phoneNumber=$value->phonenumber;
				}
				$records=Send::select()->where("receiveId",$targetId)->get()->toArray();
				foreach($records as $record){
					$send_time=$record['updated_at'];
				}
				
				if(!$records){
					$sendResult=$this->_sendMes($phoneNumber);
					if($sendResult){
						Send::insert([
							"receiveId"=>$targetId,
							"receiveNum"=>$phoneNumber,
							 'created_at' =>date("Y-m-d H:i:s", time()),
							 'updated_at'=>date("Y-m-d H:i:s", time())
							]);
					}
				} else {
					$created_at=time()-60*60*24;
					$sendTime=strtotime($send_time);
					$nowCount=Message::where(['fromUserId'=>$fromUserId,'targetId'=>$targetId])->count();
					$lastConut=Message::where(['fromUserId'=>$fromUserId,'targetId'=>$targetId])->where("dateTime","<",$send_time)->count();
					if($created_at>$sendTime && $nowCount!=$lastConut){
						$sendResult=$this->_sendMes($phoneNumber);
						if($sendResult){
							Send::where("receiveNum",$phoneNumber)->update([
								// 'created_at' =>date("Y-m-d H:i:s", time()),
								  'updated_at'=>date("Y-m-d H:i:s", time())
								]);
						}
					}
				}

			}
			
		}
	}


//获取相关视频的信息

	public function  reVideo($id){
		$payload = app('request')->all();
	           $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
	           $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
	           $skipnum = ($startpage-1)*$pagecount;
	           $videoId=$id;
	           $datas=DB::table("T_V_VIDEOINFO")->select("VideoLabel")->where("VideoID",$videoId)->get();
	           foreach($datas as $data){
	           	$types=$data->VideoLabel;
	           }
	           $type=explode(",",$types);
	           $matchers=array();
	           foreach($type as $value){
	           	if($value=="tj"){
	           		continue;
	           	}
	           	 $results=DB::table("T_V_VIDEOINFO")->skip($skipnum)->take($pagecount)->where("VideoLabel","like","%".$value."%")->where("VideoID","<>",$id)->where("Flag",1)->orderBy("VideoID","desc")->get();
	           	 $matchers=array_merge($matchers,$results);
	           }
	           $counts=count($matchers);
	           $pages=ceil($counts/$pagecount);
	          
	          return $this->response->array(['counts'=>$counts, 'pages'=>$pages, 'data'=>$matchers, 'currentpage'=>$startpage]);
	}

	//
	 public function enroll(){
	        $payload = app('request')->all();
		 if(!empty($payload['name']) && $payload['phonenumber'] && $payload['wechat'] && $payload['email'] && $payload['work']){
			 $str = "姓名：" . $payload['name'] . "\n";
			 $str = $str . "性别：" .(isset($payload['sex'])?$payload['sex']:'男') . "\n";
			 if(isset($payload['year'])){
				 $str = $str . "生日：" . $payload['year']."-".$payload['month']."-".$payload['day']. "\n";
			 }
			 $str = $str . "手机：" . $payload['phonenumber'] . "\n";
			 $str = $str . "微信：" . $payload['wechat'] . "\n";
			 $str = $str . "邮箱：" . (isset($payload['email'])?$payload['email']:'') . "\n";
			 $str = $str . "居住地：" . (isset($payload['live'])?$payload['live']:'') . "\n";
			 $str = $str . "工作单位：" . $payload['work'] . "\n";
			 $str = $str . "培训目标：" . (isset($payload['goal'])?$payload['goal']:'') . "\n";
			 $str = $str . "工作经历：" . (isset($payload['task'])?$payload['task']:'') . "\n";
			 $str = $str . "报名时间：" . date('Y-m-d H:i:s', time()) . "\n";
			 $str = $str . "用户IP：" . $_SERVER["REMOTE_ADDR"] . "\n\n\n";

			 file_put_contents('./lists.txt', $str, FILE_APPEND);
			 return $this->response->array(['status_code'=>'200', 'msg'=>'恭喜您报名成功！工作人员近期会联系您，确认报名信息，请保持电话畅通！']);
		 }else{
			 return $this->response->array(['status_code'=>'604', 'msg'=>"请您将信息填写完整"]);
		 }

    }

}
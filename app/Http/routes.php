<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
Route::get('/', function () {
    // return view('welcome');
    echo 'Hi, Welcome to Api Server . ';
});



//支付成功回调地址
Route::any('pay/result','Api\V1\ZLLController@payResult');
//webhooks回调地址
Route::any('pay/webhooks','Api\V1\ZLLController@webhooks');

// API 路由接管
$api = app('api.router');
// V2 版本，公有接口，不需要登录
$api->version('v1', ['middleware'=>['access','cross']], function ($api) {

$api->get('/v2/shouye', 'App\Http\Controllers\Api\V1\ZLLController@indexProject');//获取验证码


//weile ie
$api->get('/v2/ie/auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');//获取验证码
$api->get('/v2/ie/auth/resetpwd', 'App\Http\Controllers\Api\V1\AuthenticateController@resetPassword');//找回密码
$api->get('/v2/ie/auth/register', 'App\Http\Controllers\Api\V1\AuthenticateController@register');//注册
$api->get('/v2/ie/auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@authenticate');//登录

    //【用户】
    // 用户注册
    $api->post('/v2/auth/register', 'App\Http\Controllers\Api\V1\AuthenticateController@register');
    // 用户登录验证并返回 Token
    $api->post('/v2/auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@authenticate');
    // 忘记密码，使用手机验证码登录，并返回 Token
    $api->post('/v2/auth/smslogin', 'App\Http\Controllers\Api\V1\AuthenticateController@smslogin');
    // 找回密码
    $api->post('/v2/auth/resetpwd', 'App\Http\Controllers\Api\V1\AuthenticateController@resetPassword');
    // 获取用户手机验证码
    $api->post('/v2/auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');
    // 生成图形验证码
    
    // 服务方列表
    $api->get('/v2/service/list', 'App\Http\Controllers\Api\V1\UserController@serList');
    // 服务方详情
    $api->get('/v2/service/list/{id}', 'App\Http\Controllers\Api\V1\UserController@serInfo');

    //【项目】
    // 项目列表
    $api->get('/v2/project/list', 'App\Http\Controllers\Api\V2\ProjectController@proList');
    // 项目详情
    $api->get('/v2/project/list/{id}', 'App\Http\Controllers\Api\V2\ProjectController@proInfo');

    //【视频】
    // 视频列表
    $api->get('/v2/video/list', 'App\Http\Controllers\Api\V1\VideoController@videoList');
    // 视频详情
    $api->get('/v2/video/list/{id}', 'App\Http\Controllers\Api\V1\VideoController@videoInfo');


    //【新闻资讯】
    // 新闻资讯列表
    $api->get('/v2/news/list', 'App\Http\Controllers\Api\V1\NewsController@newsList');
    // 新闻资讯详情
    $api->get('/v2/news/list/{id}', 'App\Http\Controllers\Api\V1\NewsController@newsInfo');

    
    // 搜索
    $api->post('/v2/search', 'App\Http\Controllers\Api\V1\ToolController@search');
    // $api->post('login','App\Http\Controllers\Auth\AuthController@postLogin');

    //安卓更新
    $api->get('/v2/app/update', 'App\Http\Controllers\Api\V1\ToolController@update');
    //ios更新
    $api->get('/v2/app/iosupdate', 'App\Http\Controllers\Api\V1\ToolController@iosupdate');
    //appBanner
    $api->get('/v2/app/banner', 'App\Http\Controllers\Api\V1\ToolController@banner');
    //app 版本1.0.1中轮播图接口
     $api->get('/v2/app/twobanner', 'App\Http\Controllers\Api\V1\LdsController@banner');

    //视频评论
    $api->post('/v2/video/comment/create', 'App\Http\Controllers\Api\V1\ToolController@commentCreate');
    //视频评论列表
    $api->get('/v2/video/comment/list', 'App\Http\Controllers\Api\V1\ToolController@commentList');
    //视频评论删除
    $api->get('/v2/video/comment/delete', 'App\Http\Controllers\Api\V1\ToolController@commnetDelete');
    //视频点赞
    $api->post('/v2/video/zan', 'App\Http\Controllers\Api\V1\ToolController@zan');

    //app获取用户信息
    $api->post('/v2/app/uinfo', 'App\Http\Controllers\Api\V1\UserController@userInfo');

    //获取融云历史消息
    $api->get('/v2/history', 'App\Http\Controllers\Api\V1\IMController@messageHistory');



    //亮亮8.23
    //智能匹配相关信息相关服务方
    $api->get('/v2/match/project','App\Http\Controllers\Api\V1\ZLLController@matchpro');//信息
    $api->get('/v2/match/service','App\Http\Controllers\Api\V1\ZLLController@matchser');//服务方
    $api->get('/v2/match/proser','App\Http\Controllers\Api\V1\ZLLController@matchProSer');//发布信息匹配服务方
    $api->get('/v2/sendmail','App\Http\Controllers\Api\V1\ZLLController@sendmail');//注册用户发送邮件
    $api->get('/v2/test','App\Http\Controllers\Api\V1\ZLLController@test');//测试异步发送邮件
    $api->get('/v2/sendmessage','App\Http\Controllers\Api\V1\LdsController@sendMessage');//发送信息，提醒用户下载app
    $api->get('/v2/match/video/{id}', 'App\Http\Controllers\Api\V1\LdsController@reVideo');

    
    //亮亮9.24
    //充值列表
    $api->get('/v2/pay/list','App\Http\Controllers\Api\V1\ZLLController@payList');
    //区分app不同步
    // $api->get('/v2/project/lists', 'App\Http\Controllers\Api\V1\ProjectController@proList2');
    $api->get('/v2/project/lists', 'App\Http\Controllers\Api\V2\ProjectController@proList');

    //亮亮10.14
    //报名接口
    $api->post('/v2/enroll', 'App\Http\Controllers\Api\V1\ZLLController@enroll');
    //亮亮10.18
    //报名接口
    $api->post('/v2/report', 'App\Http\Controllers\Api\V1\ZLLController@report');
    //亮亮10.20
    //可以搜出收费信息（旧版本不能搜出）
    $api->post('/v2/searchs', 'App\Http\Controllers\Api\V1\ToolController@searchs');
    //查看服务方次数
    $api->post('/v2/count/service', 'App\Http\Controllers\Api\V1\ZLLController@checkService');
    //亮亮10.25
    //app启动接口    
    $api->post('/v2/app/start', 'App\Http\Controllers\Api\V1\ZLLController@appStart');

    //亮亮10.31
    //新闻评论
    $api->post('/v2/news/comment/create', 'App\Http\Controllers\Api\V1\ZLLController@newsCommentCreate');
    //新闻评论列表
    $api->get('/v2/news/comment/list', 'App\Http\Controllers\Api\V1\ZLLController@newsCommentList');
    //新闻评论删除
    $api->get('/v2/news/comment/delete', 'App\Http\Controllers\Api\V1\ZLLController@newsCommnetDelete');
    //亮亮11.3
    //获取问卷
    $api->get('/v2/test/paper','App\Http\Controllers\Api\V1\ZLLController@getPaper');
    //获取结果
    $api->post('/v2/test/result','App\Http\Controllers\Api\V1\ZLLController@getResult');

    //亮亮11.23
    //测试新发布接口
    $api->post('/v2/project/create','App\Http\Controllers\Api\V2\ProjectController@create');

$api->post('temp/addquestion','App\Http\Controllers\Api\V2\TempController@addQuestion');


//12.12 临时群发短信
    $api->get('/v2/temp/sendsms', 'App\Http\Controllers\Api\V1\ZLLController@tempSms');

    //12.13会员开通列表，服务方认证列表
    $api->get('/v2/member/list','App\Http\Controllers\Api\V2\MemberController@memberList');
    $api->get('/v2/star/list','App\Http\Controllers\Api\V2\MemberController@starList');

});

// 私有接口，需要登录
$api->version('v1', ['protected' => true, 'middleware'=>['access','cross']], function ($api) {

    // 更新用户 token
    $api->get('/v2/upToken', 'App\Http\Controllers\Api\V1\AuthenticateController@upToken');

    //【用户】
    // 修改用户头像
    $api->post('/v2/auth/chpicture', 'App\Http\Controllers\Api\V1\UserController@chpicture');
    // 获取当前用户信息
    $api->post('/v2/auth/me', 'App\Http\Controllers\Api\V2\UserController@me');
    // 重置用户密码
    $api->post('/v2/auth/chpwd', 'App\Http\Controllers\Api\V1\UserController@changePassword');
    // 服务方信息完善
    $api->post('/v2/service/confirm', 'App\Http\Controllers\Api\V1\UserController@confirm');
    // 服务方信息重新完善
    $api->post('/v2/service/reconfirm', 'App\Http\Controllers\Api\V1\UserController@reconfirm');
     // app服务方信息完善
    $api->post('/v2/app/service/confirm', 'App\Http\Controllers\Api\V1\UserController@appConfirm');
    // app服务方信息重新完善
    $api->post('/v2/app/service/reconfirm', 'App\Http\Controllers\Api\V1\UserController@appReconfirm');


    //【项目】
    // 当前用户发布信息列表
    $api->get('/v2/project/mypro', 'App\Http\Controllers\Api\V1\UserController@myPro');
    // 创建项目
    $api->post('/v2/project/create', 'App\Http\Controllers\Api\V1\ProjectController@create');


     //app上传文件 山
    $api->post('/v2/uploadfile', 'App\Http\Controllers\Api\V2\ProjectController@uploadFile');

    // 项目抢单
    $api->post('/v2/project/rush', 'App\Http\Controllers\Api\V1\ProjectController@proRush');

    // 取消抢单
    $api->post('/v2/project/rushcancel', 'App\Http\Controllers\Api\V1\ProjectController@proRushCancel');

    // 项目抢单列表
    $api->get('/v2/project/rushlist/{id}', 'App\Http\Controllers\Api\V1\UserController@proRushList');
    // 项目合作
    $api->post('/v2/project/cooperate', 'App\Http\Controllers\Api\V1\UserController@proCooperate');
    // 取消合作
    $api->post('/v2/project/cancel', 'App\Http\Controllers\Api\V1\UserController@proCancel');
    // 合作列表
    $api->get('/v2/project/coolist', 'App\Http\Controllers\Api\V1\UserController@cooList');   
    // 我的抢单列表（服务方才有）
    $api->get('/v2/project/myrush', 'App\Http\Controllers\Api\V1\UserController@rushList');

    //【视频】
    $api->post('/v2/video/create', 'App\Http\Controllers\Api\V1\VideoController@create');

    //【资讯】
    $api->post('/v2/news/create', 'App\Http\Controllers\Api\V1\NewsController@create');

    //【通用】
    // 收藏
    $api->post('/v2/collect', 'App\Http\Controllers\Api\V1\ToolController@collect');
    // 收藏列表
    $api->get('/v2/collect/list', 'App\Http\Controllers\Api\V1\ToolController@collectList');
    // 收藏列表APP专用
    $api->get('/v2/app/collect/list', 'App\Http\Controllers\Api\V1\ToolController@appcollectList');
    // 上传
    $api->post('/v2/upload', 'App\Http\Controllers\Api\V1\ToolController@upload');
    // 获取消息列表
    $api->post('/v2/getmessage', 'App\Http\Controllers\Api\V1\ToolController@getMessage');
    // 已读消息
    $api->post('/v2/readmessage', 'App\Http\Controllers\Api\V1\ToolController@readMessage');
    // 删除消息
    $api->post('/v2/delmessage', 'App\Http\Controllers\Api\V1\ToolController@delMessage');
    // 意见反馈
    $api->post('/v2/app/advice', 'App\Http\Controllers\Api\V1\UserController@advice');



    //获取融云token
    $api->get('/v2/rctoken', 'App\Http\Controllers\Api\V1\IMController@get_token');


    //亮亮9.21
    //查看联系方式消费接口
    $api->post('/v2/consume','App\Http\Controllers\Api\V1\ZLLController@consume');
    //app查看联系方式消费接口
    $api->post('/v2/app/consume','App\Http\Controllers\Api\V1\ZLLController@appConsume');
    //账单
    $api->post('/v2/mybill','App\Http\Controllers\Api\V1\ZLLController@mybill');
    //亮亮9.20
    //ping++支付接口
    $api->post('/v2/pay','App\Http\Controllers\Api\V1\ZLLController@payMoney');
    //修改用户名
    $api->post('/v2/auth/chusername','App\Http\Controllers\Api\V1\ZLLController@changeUserName');
    //亮亮9.27
    //苹果支付成功回调接口
    $api->post('/v2/apple/pay','App\Http\Controllers\Api\V1\ZLLController@applePay');
    //亮亮10.18
    //获取信息支付标记
    $api->post('/v2/ispay','App\Http\Controllers\Api\V1\ZLLController@getPayFlag');

    //亮亮11.28
    //委托发布接口
    $api->post('/v2/entrust','App\Http\Controllers\Api\V1\ZLLController@entrust');

    //亮亮12.13
    //会员付费，服务方认证付费接口
    $api->post('/v2/pay','App\Http\Controllers\Api\V2\MemberController@payMoney');
    //会员开通记录
    $api->post('/v2/pay/member/list','App\Http\Controllers\Api\V2\MemberController@mybill');
    //苹果支付成功回调接口
    $api->post('/v2/apple/pay','App\Http\Controllers\Api\V2\MemberController@applePay');

});

// END V2 版本

//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================
//==========================================================================================================================================

// V1 版本，公有接口，不需要登录
$api->version('v1', ['middleware'=>['access','cross']], function ($api) {


//weile ie
$api->get('ie/auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');//获取验证码
$api->get('ie/auth/resetpwd', 'App\Http\Controllers\Api\V1\AuthenticateController@resetPassword');//找回密码
$api->get('ie/auth/register', 'App\Http\Controllers\Api\V1\AuthenticateController@register');//注册
$api->get('ie/auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@authenticate');//登录

    //【用户】
    // 用户注册
    $api->post('auth/register', 'App\Http\Controllers\Api\V1\AuthenticateController@register');
    // 用户登录验证并返回 Token
    $api->post('auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@authenticate');
    // 忘记密码，使用手机验证码登录，并返回 Token
    $api->post('auth/smslogin', 'App\Http\Controllers\Api\V1\AuthenticateController@smslogin');
    // 找回密码
    $api->post('auth/resetpwd', 'App\Http\Controllers\Api\V1\AuthenticateController@resetPassword');
    // 获取用户手机验证码
    $api->post('auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');
    // 生成图形验证码
    
    // 服务方列表
    $api->get('service/list', 'App\Http\Controllers\Api\V1\UserController@serList');
    // 服务方详情
    $api->get('service/list/{id}', 'App\Http\Controllers\Api\V1\UserController@serInfo');

    //【项目】
    // 项目列表
    $api->get('project/list', 'App\Http\Controllers\Api\V1\ProjectController@proList');
    // 项目详情
    $api->get('project/list/{id}', 'App\Http\Controllers\Api\V1\ProjectController@proInfo');

    //【视频】
    // 视频列表
    $api->get('video/list', 'App\Http\Controllers\Api\V1\VideoController@videoList');
    // 视频详情
    $api->get('video/list/{id}', 'App\Http\Controllers\Api\V1\VideoController@videoInfo');


    //【新闻资讯】
    // 新闻资讯列表
    $api->get('news/list', 'App\Http\Controllers\Api\V1\NewsController@newsList');
    // 新闻资讯详情
    $api->get('news/list/{id}', 'App\Http\Controllers\Api\V1\NewsController@newsInfo');

    
    // 搜索
    $api->post('search', 'App\Http\Controllers\Api\V1\ToolController@search');
    // $api->post('login','App\Http\Controllers\Auth\AuthController@postLogin');

    //安卓更新
    $api->get('app/update', 'App\Http\Controllers\Api\V1\ToolController@update');
    //ios更新
    $api->get('app/iosupdate', 'App\Http\Controllers\Api\V1\ToolController@iosupdate');
    //appBanner
    $api->get('app/banner', 'App\Http\Controllers\Api\V1\ToolController@banner');
    //app 版本1.0.1中轮播图接口
     $api->get('app/twobanner', 'App\Http\Controllers\Api\V1\LdsController@banner');

    //视频评论
    $api->post('video/comment/create', 'App\Http\Controllers\Api\V1\ToolController@commentCreate');
    //视频评论列表
    $api->get('video/comment/list', 'App\Http\Controllers\Api\V1\ToolController@commentList');
    //视频评论删除
    $api->get('video/comment/delete', 'App\Http\Controllers\Api\V1\ToolController@commnetDelete');
    //视频点赞
    $api->post('video/zan', 'App\Http\Controllers\Api\V1\ToolController@zan');

    //app获取用户信息
    $api->post('app/uinfo', 'App\Http\Controllers\Api\V1\UserController@userInfo');

    //获取融云历史消息
    $api->get('history', 'App\Http\Controllers\Api\V1\IMController@messageHistory');



    //亮亮8.23
    //智能匹配相关信息相关服务方
    $api->get('match/project','App\Http\Controllers\Api\V1\ZLLController@matchpro');//信息
    $api->get('match/service','App\Http\Controllers\Api\V1\ZLLController@matchser');//服务方
    $api->get('match/proser','App\Http\Controllers\Api\V1\ZLLController@matchProSer');//发布信息匹配服务方
    $api->get('sendmail','App\Http\Controllers\Api\V1\ZLLController@sendmail');//注册用户发送邮件
    $api->get('test','App\Http\Controllers\Api\V1\ZLLController@test');//测试异步发送邮件
    $api->get('sendmessage','App\Http\Controllers\Api\V1\LdsController@sendMessage');//发送信息，提醒用户下载app
    $api->get('match/video/{id}', 'App\Http\Controllers\Api\V1\LdsController@reVideo');

    
    //亮亮9.24
    //充值列表
    $api->get('pay/list','App\Http\Controllers\Api\V1\ZLLController@payList');
    //区分app不同步
    // $api->get('project/lists', 'App\Http\Controllers\Api\V1\ProjectController@proList2');
    $api->get('project/lists', 'App\Http\Controllers\Api\V1\ProjectController@proList');

    //亮亮10.14
    //报名接口
    $api->post('enroll', 'App\Http\Controllers\Api\V1\LdsController@enroll');
    //亮亮10.18
    //报名接口
    $api->post('report', 'App\Http\Controllers\Api\V1\ZLLController@report');
    //亮亮10.20
    //可以搜出收费信息（旧版本不能搜出）
    $api->post('searchs', 'App\Http\Controllers\Api\V1\ToolController@searchs');
    //查看服务方次数
    $api->post('count/service', 'App\Http\Controllers\Api\V1\ZLLController@checkService');
    //亮亮10.25
    //app启动接口    
    $api->post('app/start', 'App\Http\Controllers\Api\V1\ZLLController@appStart');

    //亮亮10.31
    //新闻评论
    $api->post('news/comment/create', 'App\Http\Controllers\Api\V1\ZLLController@newsCommentCreate');
    //新闻评论列表
    $api->get('news/comment/list', 'App\Http\Controllers\Api\V1\ZLLController@newsCommentList');
    //新闻评论删除
    $api->get('news/comment/delete', 'App\Http\Controllers\Api\V1\ZLLController@newsCommnetDelete');
    //亮亮11.3
    //获取问卷
    $api->get('test/paper','App\Http\Controllers\Api\V1\ZLLController@getPaper');
    //获取结果
    $api->post('test/result','App\Http\Controllers\Api\V1\ZLLController@getResult');

    //亮亮11.23
    //测试新发布接口
    $api->post('test/project/create','App\Http\Controllers\Api\V2\ProjectController@create');

$api->post('temp/addquestion','App\Http\Controllers\Api\V2\TempController@addQuestion');
});

// 私有接口，需要登录
$api->version('v1', ['protected' => true, 'middleware'=>['access','cross']], function ($api) {

    // 更新用户 token
    $api->get('upToken', 'App\Http\Controllers\Api\V1\AuthenticateController@upToken');

    //【用户】
    // 修改用户头像
    $api->post('auth/chpicture', 'App\Http\Controllers\Api\V1\UserController@chpicture');
    // 获取当前用户信息
    $api->post('auth/me', 'App\Http\Controllers\Api\V1\UserController@me');
    // 重置用户密码
    $api->post('auth/chpwd', 'App\Http\Controllers\Api\V1\UserController@changePassword');
    // 服务方信息完善
    $api->post('service/confirm', 'App\Http\Controllers\Api\V1\UserController@confirm');
    // 服务方信息重新完善
    $api->post('service/reconfirm', 'App\Http\Controllers\Api\V1\UserController@reconfirm');
     // app服务方信息完善
    $api->post('app/service/confirm', 'App\Http\Controllers\Api\V1\UserController@appConfirm');
    // app服务方信息重新完善
    $api->post('app/service/reconfirm', 'App\Http\Controllers\Api\V1\UserController@appReconfirm');


    //【项目】
    // 当前用户发布信息列表
    $api->get('project/mypro', 'App\Http\Controllers\Api\V1\UserController@myPro');
    // 创建项目
    $api->post('project/create', 'App\Http\Controllers\Api\V1\ProjectController@create');


     //app上传文件 山
    $api->post('uploadfile', 'App\Http\Controllers\Api\V1\ProjectController@uploadFile');

    // 项目抢单
    $api->post('project/rush', 'App\Http\Controllers\Api\V1\ProjectController@proRush');

    // 取消抢单
    $api->post('project/rushcancel', 'App\Http\Controllers\Api\V1\ProjectController@proRushCancel');

    // 项目抢单列表
    $api->get('project/rushlist/{id}', 'App\Http\Controllers\Api\V1\UserController@proRushList');
    // 项目合作
    $api->post('project/cooperate', 'App\Http\Controllers\Api\V1\UserController@proCooperate');
    // 取消合作
    $api->post('project/cancel', 'App\Http\Controllers\Api\V1\UserController@proCancel');
    // 合作列表
    $api->get('project/coolist', 'App\Http\Controllers\Api\V1\UserController@cooList');   
    // 我的抢单列表（服务方才有）
    $api->get('project/myrush', 'App\Http\Controllers\Api\V1\UserController@rushList');

    //【视频】
    $api->post('video/create', 'App\Http\Controllers\Api\V1\VideoController@create');

    //【资讯】
    $api->post('news/create', 'App\Http\Controllers\Api\V1\NewsController@create');

    //【通用】
    // 收藏
    $api->post('collect', 'App\Http\Controllers\Api\V1\ToolController@collect');
    // 收藏列表
    $api->get('collect/list', 'App\Http\Controllers\Api\V1\ToolController@collectList');
    // 收藏列表APP专用
    $api->get('app/collect/list', 'App\Http\Controllers\Api\V1\ToolController@appcollectList');
    // 上传
    $api->post('upload', 'App\Http\Controllers\Api\V1\ToolController@upload');
    // 获取消息列表
    $api->post('getmessage', 'App\Http\Controllers\Api\V1\ToolController@getMessage');
    // 已读消息
    $api->post('readmessage', 'App\Http\Controllers\Api\V1\ToolController@readMessage');
    // 删除消息
    $api->post('delmessage', 'App\Http\Controllers\Api\V1\ToolController@delMessage');
    // 意见反馈
    $api->post('app/advice', 'App\Http\Controllers\Api\V1\UserController@advice');



    //获取融云token
    $api->get('rctoken', 'App\Http\Controllers\Api\V1\IMController@get_token');


    //亮亮9.21
    //查看联系方式消费接口
    $api->post('consume','App\Http\Controllers\Api\V1\ZLLController@consume');
    //app查看联系方式消费接口
    $api->post('app/consume','App\Http\Controllers\Api\V1\ZLLController@appConsume');
    //账单
    $api->post('/mybill','App\Http\Controllers\Api\V1\ZLLController@mybill');
    //亮亮9.20
    //ping++支付接口
    $api->post('pay','App\Http\Controllers\Api\V1\ZLLController@payMoney');
    //修改用户名
    $api->post('auth/chusername','App\Http\Controllers\Api\V1\ZLLController@changeUserName');
    //亮亮9.27
    //苹果支付成功回调接口
    $api->post('apple/pay','App\Http\Controllers\Api\V1\ZLLController@applePay');
    //亮亮10.18
    //获取信息支付标记
    $api->post('ispay','App\Http\Controllers\Api\V1\ZLLController@getPayFlag');

});

// END V1 版本

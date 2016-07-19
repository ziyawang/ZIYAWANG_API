<?php

/**
 * vergil-lai/uc-client包配置文件
 * @author Vergil <vergil@vip.163.com>
 */
return [

    /*
     * Ucenter的地址
     */
    'api' => env('UC_API'),

    /*
     * 通信密钥，必须药与Ucenter保持一致，否则该应用无法与Ucenter正常通信
     */
    'key' => env('UC_KEY'),

    /*
     * 应用ID，必须与Ucenter设置的一致
     */
    'appid' => env('UC_APPID'),

    /*
     * 处理Ucenter发出的通知的类名，需要实现接口：\VergilLai\UcClient\Contracts\UcenterNoteApi
     */
    'note_handler' => \VergilLai\UcClient\Note::class,

    /*
     * 应用接口文件名称，需要与Ucenter设置的一致，默认为uc.php
     */
    'apifilename' => env('UC_API_FILENAME', 'uc.php'),

    /*
     * 用户删除 API 接口开关
     */
    'api_deleteuser' => env('UC_API_DELETEUSER', 1),

    /*
     * 用户改名 API 接口开关
     */
    'api_renameuser' => env('UC_API_RENAMEUSER', 1),

    /*
     * 获取标签 API 接口开关
     */
    'api_gettag' => env('UC_API_GETTAG', 1),

    /*
     * 同步登录 API 接口开关
     */
    'api_synlogin' => env('UC_API_SYNLOGIN', 1),

    /*
     * 同步登出 API 接口开关
     */
    'api_synlogout' => env('UC_API_SYNLOGOUT', 1),

    /*
     * 更改用户密码 开关
     */
    'api_updatepw' => env('UC_API_UPDATEPW', 1),

    /*
     * 更新关键字列表 开关
     */
    'api_updatebadword' => env('UC_API_UPDATEBADWORDS', 1),

    /*
     * 更新域名解析缓存 开关
     */
    'api_updatehosts' => env('UC_API_UPDATEHOSTS', 1),

    /*
     * 更新应用列表 开关
     */
    'api_updateapps' => env('UC_API_UPDATEAPPS', 1),

    /*
     * 更新客户端缓存 开关
     */
    'api_updateclient' => env('UC_API_UPDATECLIENT', 1),

    /*
     * 更新用户积分 开关
     */
    'api_updatecredit' => env('UC_API_UPDATECREDIT', 1),

    /*
     * 向 UCenter 提供积分设置 开关
     */
    'api_getcreditsettings' => env('UC_API_GETCREDITSETTINGS', 1),

    /*
     * 获取用户的某项积分 开关
     */
    'api_getcredit' => env('UC_API_GETCREDIT', 1),

    /*
     * 更新应用积分设置 开关
     */
    'api_updatecreditsettings' => env('UC_API_UPDATECREDITSETTINGS', 1),

    /*
     * cookie domain
     */
    'cookie_domain' => env('UC_COOKIE_DOMAIN', ''),

    /*
     * cookie path
     */
    'cookie_path' => env('UC_COOKIE_PATH', ''),


];
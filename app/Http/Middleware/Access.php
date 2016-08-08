<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Input;

class Access
{
    /**
     * Handle an incoming request.
     * 设置接口访问权限
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        $access = $this->getAccess();
        $access_token = $request->access_token? $request->access_token:null;
        if($access != $access_token){
            $data = [
                'errcode'=>4050,
                'errmsg'=>'Access Denied!'
            ];

            return json_encode($data);
        }
        return $next($request);
    }

    public function getAccess(){
        return "token";
    }
}

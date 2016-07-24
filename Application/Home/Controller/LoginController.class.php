<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller{
    
    function log(){
        
        $cellphone = I('post.cellphone');
        $password = I('post.password');
        $password = md5(md5($password));
        $user = D('User');
        $res = $user->where(array('cellphone'=>$cellphone,'password_hash'=>$password))->find();
        unset($res['password_hash']);
        if(!$res){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '用户名或密码不正确'
            );
            exit(json_encode($str));
        }else{
            $str = array(
                'data'  =>  $res,
                'msg'   =>  '登录成功',
                'code'  =>  '200'
            );
            exit(json_encode($str));
        }
        
    }
}
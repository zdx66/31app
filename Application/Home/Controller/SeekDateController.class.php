<?php
namespace Home\Controller;
use Think\Controller;
class SeekDateController extends Controller{
    
    //觅约主页信息
    function user_info_lst(){
        
        //当前用户ID
        $user_id     =  isset($_GET['user_id'])?$_GET['user_id']:'';
        $page        =  isset($_GET['page'])?$_GET['page']:1;
        $sex         =  isset($_GET['sex'])?$_GET['sex']:1;
        $mode        =  isset($_GET['mode'])?$_GET['mode']:0;
        $coin        =  isset($_GET['coin'])?$_GET['coin']:70;
        $userinfomodel   =   D('user');
        if($sex == 1){
            $sex = "female";
        }else{
            $sex = 'male';
        }
        $list = $userinfomodel
                ->table("__USER__ as user")
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->join('left join __USER_PROFILE__ as pro on (user.id = pro.user_id)')
                ->field("user.id,user.cid,user.sex,user.avatar,data.jiecao_coin,pro.mark_friend,pro.mark")
                ->where(array('mode'=>0,'sex' => $sex))
                ->order('user.id desc')
                ->page($page,15)
                ->select();
        //等待觅约--冻结节操币
        if($mode == 9){
            $datamodel = M("UserData");
            $info = $datamodel->field('jiecao_coin,frozen_jiecao_coin')->where(array('user_id'=>$user_id))->find();
            if($info['jiecao_coin']<$coin){
                $warning = "节操币余额不足，请先充值";  
            }elseif ($info['jiecao_coin']<100) {
                $warning = '节操币余额少于100，可以去充值了';
            }else{
                $data['jiecao_coin'] = $info['jiecao_coin']-$coin;
                $data['frozen_jiecao_coin'] = $info['frozen_jiecao_coin']+$coin;
                $res = $datamodel->field('')->where(array('user_id'=>$user_id))->setfield($data);
                $warning = '节操币数量安全';
            }
            
        }
        
        //返回客户端结果
        if($list){
            $str = array(
            "code"  =>  '200',
            'data'  =>  $list,
            'warning'   =>  $warning
                    
            );
        }else{
            $str = array(
            'code'  =>  '201',
            'msg'   =>  "查询出错"
            );  
        }
        exit(json_encode($str));

    }
    
    //用户主页
    function self_info()
    {
        $user_id = isset($_GET['user_id'])?$_GET['user_id']:'';
        //$username = isset($_GET['username'])?$_GET['username']:'';
        $model = D("user");
        $info = $model
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->field('id,sex,rank,status,avatar,hx_pwd,mode,jiecao_coin,appion_time,following_count,follower_count,viscosity,levels,sex_skill,lan_skill,appearance')
                ->where(array("user_id"=>$user_id))
                ->find();
        echo $model->getLastSql();
        if(!$info){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '信息查询失败'
            );
            exit(json_encode($str));
        }
        $info['charm'] = $info['viscosity']+$info['levels']+$info['sex_skill']+$info['lan_skill']+$info['appearance'];
        if($info['charm']<600)
        {
            $info['charm'] = 600;
        }
        $str = array(
            'data'  =>  $info,
            'code'  =>  200,
            'msg'   =>  '成功'
        );    
        exit(json_encode($str));

    }
    
    
    //他人主页
    function some_info()
    {
        $user_id = isset($_GET['user_id'])?$_GET['user_id']:'';
        //$username = isset($_GET['username'])?$_GET['username']:'';
        $model = D("user");
        $info = $model
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->join('left join __USER_PROFILE__ as pro on (user.id = pro.user_id')
                ->field('id,sex,rank,status,avatar,mode,,following_count,file_1,file_2,file_3,file_4,file_5,birthdate,signature,address,address_1,address_2,address_3,self_mark,mark,height,weight')
                ->where(array("user_id"=>$user_id))
                ->find();
        echo $model->getLastSql();
        if(!$info){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '信息查询失败'
            );
            exit(json_encode($str));
        }else{
            $str = array(
                'data'  =>  $info,
                'code'  =>  200,
                'msg'   =>  '成功'
            );    
            exit(json_encode($str));
        }
    }

}
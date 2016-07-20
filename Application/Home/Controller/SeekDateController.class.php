<?php
namespace Home\Controller;
use Think\Controller;
class SeekDateController extends Controller{
    
    //发现页面
    function user_info_lst(){
        
        //当前用户ID
        $user_id     =  isset($_GET['user_id'])?$_GET['user_id']:'';
        $page        =  isset($_GET['page'])?$_GET['page']:1;
        $sex         =  isset($_GET['sex'])?$_GET['sex']:1;
        $mode        =  isset($_GET['mode'])?$_GET['mode']:0;
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
                ->field("user.id,user.cid,user.sex,user.avatar,data.need_coin,pro.mark_friend,pro.self_mark,pro.hobby")
                ->where(array('mode'=>0,'sex' => $sex))
                ->order('user.id desc')
                ->page($page,15)
                ->select();
        //男的：等待觅约--冻结节操币
        if($sex == 'female'){
            if($mode == 9 ){
                $datamodel = M("UserData");
                $info = $datamodel->field('jiecao_coin,frozen_jiecao_coin')->where(array('user_id'=>$user_id))->find();
                if($info['jiecao_coin']<$list['need_coin']){
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
    
    //觅约详情页
    function appion_detail(){
        
        //用户id
        $id = I('get.id');
        //觅约对象id
        $user_id = I('get.user_id');
        //点赞
        $is_good = I('get.is_good');
        $user = D('user');
        if($is_good){
            $userdata = D('UserData');
            //给觅约对象加人气
            $res1 = $userdata->where(array('user_id'=>65))->setInc("following_count");
            
            //用户给觅约对象加关注
            $res2 = $userdata->where(array('user_id'=>$id))->setInc("follower_count"); 
            //echo $userdata->getLastSql();
        }
        if(!$user_id){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  'user_id不能为空'
            );
            exit($str);
        }
        
        $info = $user
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->join('left join __USER_PROFILE__ as pro on ( user.id = pro.user_id)')
                ->field("user.id,nickname,mode,sex,need_coin,file_1,file_2,file_3,birthdate,signature,address,self_mark,mark,hobby,height,weight,constellation")
                ->where(array('user.id'=>$user_id))
                ->find();
        if(!$info){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '用户信息查询失败'
            );
            exit($str);
        }
        $str = array(
            'code'  =>  '200',
            'data'  =>  $info,
            'id'    =>  $id,
            'msg'   =>  '成功'
        );
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
                ->field('id,sex,rank,seekid,status,avatar,hx_pwd,mode,jiecao_coin,appion_time,following_count,follower_count,viscosity,levels,sex_skill,lan_skill,appearance')
                ->where(array("user_id"=>$user_id))
                ->find();
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
        //用户id
        $id = isset($_GET['id'])?$_GET['id']:'';
        //他人id
        $user_id = isset($_GET['user_id'])?$_GET['user_id']:'';
        $model = D("user");
        $info1 = $model
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->field('user.id,sex,seekid,rank,status,avatar,mode,following_count')
                ->where('user.id = '.$user_id)
                ->find();
        $model2 = D("UserProfile");
        $info2 = $model2
                ->field("file_1,file_2,file_3,file_4,file_5,birthdate,signature,address,address_1,address_2,address_3,self_mark,mark,height,weight,constellation")
                ->where(array("user_id"=>$user_id))
                ->find();

        if(!$info1 && !$info2){
            $str = array(
                'id'    =>  $id,
                'code'  =>  '201',
                'msg'   =>  '信息查询失败'
            );
            exit(json_encode($str));
        }else{
            $str = array(
                'data1'  =>  $info1,
                'data2'  =>  $info2,
                'code'   =>  200,
                'id'     =>  $id,
                'msg'    =>  '成功'
            );    
            exit(json_encode($str));
        }
    }
    
    //翻牌
    function turn_over_cards()
    {
        $user_id = isset($_GET['user_id'])?$_GET['user_id']:'';
        $user_adress = isset($_GET['address'])?$_GET['address']:'';
        //不喜欢的用户id 觅约状态-》0
        $dislike = isset($_GET['dislike'])?$_GET['dislike']:'';
        //等待回复觅约的用户id 觅约状态-》9
        $like = isset($_GET['like'])?$_GET['like']:'';
        //超级喜欢的用户，发资料给对方 觅约状态-》10
        $love = isset($_GET['love'])?$_GET['love']:'';
        if(!$user_id){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '没有传入用户编号'
            );
            exit(json_encode($str));
        }
        $model = D('user');
        $info = $model->field('sex')->where(array('id'=>$user_id))->find();
        if($info['sex'] == 'male'){
            $sex = 'female';
        }else{
            $sex = 'male';
        }
        $user_model = D('user');
        $info = $user_model
                ->alias('user')
                ->join('left join __USER_PROFILE__ as pro on (user.id = pro.user_id)')
                ->field("user.id,nickname,address,file_1,birthdate,hobby,mark,height,weight,constellation")
                ->where(array('sex'=>$sex))
                ->order('user.id desc ')
                ->find();
        echo $user_model->getLastSql();
        if($dislike){
            $disable_id =$info['id'];
        }
        
        
        dump($info);
        $id = $info['id']-1;
        
    }
    
    //发动态
    function say_something(){
        
    }
    
    //标签
    function mylabel(){
        
    }

}
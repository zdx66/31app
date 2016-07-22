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
        if(!$user_id){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  'user_id不能为空'
            );
            exit(json_encode($str));
        }
        
        //点赞
        $is_good = I('get.is_good');
        $user = D('user');
        if($is_good){
            $pro = D('UserProfile');
            $res = $pro->where(array('user_id'=>$user_id))->setInc("number");
            if($res){
                $was_dianzan = "已给被查看用户点赞";
            }else{
                $was_dianzan = "没有给查看的用户点赞";
            }
        }
        
        $info = $user
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->join('left join __USER_PROFILE__ as pro on ( user.id = pro.user_id)')
                ->field("user.id,avatar,nickname,mode,sex,need_coin,file_1,file_2,file_3,birthdate,signature,address,self_mark,mark_friend,mark,hobby,height,weight,constellation")
                ->where(array('user.id'=>$user_id))
                ->find();
        if(!$info){
            $str = array(
                'code'  =>  '201',
                'msg'   =>  '用户信息查询失败'
            );
            exit(json_encode($str));
        }
        if($info['need_coin']<70)
        {
            $info['need_coin']=70;
        }
        $str = array(
            'code'  =>  '200',
            'data'  =>  $info,
            'id'    =>  $id,
            'dianzan'   =>  $was_dianzan,
            'msg'   =>  '成功'
        );
        exit(json_encode($str));
    }
    
    //用户主页
    function self_info()
    {
        $usermodel = D("user");
        $data = I('post.');
        $user_id = $data['user_id'];
        $data['avatar'] = $data['avatar'];
        if($data['avatar']){
            //查看用户是否已经有头像，有的话先删除服务器原来的图片
            $data['avatar']  =   substr($data['avatar'], strpos($data['avatar'], ",")+1);
            if(!is_base64_encoded($data['avatar'])){
                $str = array(
                    'code'  =>  '201',
                    'msg'   =>  '传过来的头像不是约定的编码'
                );
                exit(json_encode($str));
            }  
            $data['avatar'] = base64_decode(str_replace(" ","+",$data['avatar']));
            $oldimg = $usermodel->field("avatar")->where(array("id" => $user_id))->find();
            if($data['avatar'] && $oldimg['avatar']){
               //数据库中存在头像且传入了新图片，先删除服务器上的图片
                if($oldimg['avatar'] !== $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/avatar/img.jpg'){ 
                    @unlink($oldimg['avatar']);
                }
                //把图片放在服务器上
                $time = date("Y-m", time());
                $imgpath = $_SERVER['DOCUMENT_ROOT'].'/'."Public/Uploads/".$time.'/';
                if(!is_dir($imgpath)){
                    if(!mkdir($imgpath,0777,true))
                    {         
                        echo "无法创建该路径";
                    }
                }
                //将图片存到服务器上
                $imgname = time();
                file_put_contents ($imgpath.$imgname.".jpg", $data['avatar'], FILE_USE_INCLUDE_PATH);
                //将新的图片路径放到数据库中
                $data['avatar'] = $imgpath.$imgname.".jpg";

            }else if($data['avatar'] && !$oldimg['avatar']){
                //传入新图片，但数据库中还没有图片，把传入的图片解码

                //把图片放在服务器上
                $time = date("Y-m", time());
                $imgpath = $_SERVER['DOCUMENT_ROOT'].'/'."Public/Uploads/".$time.'/';
                if(!is_dir($imgpath)){
                    if(!mkdir($imgpath,0777,true))
                    {         
                        echo "无法创建该路径";
                    }
                }
                //将图片存到服务器上
                $imgname = time();
                file_put_contents ($imgpath.$imgname.".jpg", $data['avatar'], FILE_USE_INCLUDE_PATH);
                //将新的图片路径放到数据库中
                $data['avatar'] = $imgpath.$imgname.".jpg";   

            }else if(!$data['avatar'] && !$oldimg['avatar']){
                //没传图片，数据库也没有图片，给一个默认的图片
                $data['avatar'] = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/avatar/img.jpg';
            }
        }
        
        $info = $usermodel
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->field('user.id,sex,rank,seekid,status,avatar,hx_pwd,mode,jiecao_coin,appion_time,following_count,follower_count,viscosity,levels,sex_skill,lan_skill,appearance')
                ->where(array("user.id"=>$user_id))
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
        echo json_encode($str);
        //将头像入库
        $arr[avatar] = $data['avatar'];
        $res = $usermodel->where(array('id'=>$user_id))->setField($arr);
        if($res){
            $Str = array(
                'code'  =>  '200',
                'msg'   =>  '头像更新成功'
            );
            exit(json_encode($Str));
        }
    }
    
    
    //他人主页
    function some_info()
    {
        //用户id
        $data = I('post.');
        $id = $data['id'];
        //他人id
        $user_id = $data['user_id'];
        $data['avatar'] = $data['avatar'];
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
            echo(json_encode($str));
        }
        //是否更换头像
        if($data['avatar']){
            //查看用户是否已经有头像，有的话先删除服务器原来的图片
            $data['avatar']  =   substr($data['avatar'], strpos($data['avatar'], ",")+1);
            if(!is_base64_encoded($data['avatar'])){
                $str = array(
                    'code'  =>  '201',
                    'msg'   =>  '传过来的头像不是约定的编码'
                );
                exit(json_encode($str));
            }  
            $data['avatar'] = base64_decode(str_replace(" ","+",$data['avatar']));
            $oldimg = $model->field("avatar")->where(array("id" => $user_id))->find();
            if($data['avatar'] && $oldimg['avatar']){
               //数据库中存在头像且传入了新图片，先删除服务器上的图片
                if($oldimg['avatar'] !== $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/avatar/img.jpg'){ 
                    @unlink($oldimg['avatar']);
                }
                //把图片放在服务器上
                $time = date("Y-m", time());
                $imgpath = $_SERVER['DOCUMENT_ROOT'].'/'."Public/Uploads/".$time.'/';
                if(!is_dir($imgpath)){
                    if(!mkdir($imgpath,0777,true))
                    {         
                        echo "无法创建该路径";
                    }
                }
                //将图片存到服务器上
                $imgname = time();
                file_put_contents ($imgpath.$imgname.".jpg", $data['avatar'], FILE_USE_INCLUDE_PATH);
                //将新的图片路径放到数据库中
                $data['avatar'] = $imgpath.$imgname.".jpg";

            }else if($data['avatar'] && !$oldimg['avatar']){
                //传入新图片，但数据库中还没有图片，把传入的图片解码

                //把图片放在服务器上
                $time = date("Y-m", time());
                $imgpath = $_SERVER['DOCUMENT_ROOT'].'/'."Public/Uploads/".$time.'/';
                if(!is_dir($imgpath)){
                    if(!mkdir($imgpath,0777,true))
                    {         
                        echo "无法创建该路径";
                    }
                }
                //将图片存到服务器上
                $imgname = time();
                file_put_contents ($imgpath.$imgname.".jpg", $data['avatar'], FILE_USE_INCLUDE_PATH);
                //将新的图片路径放到数据库中
                $data['avatar'] = $imgpath.$imgname.".jpg";   

            }else if(!$data['avatar'] && !$oldimg['avatar']){
                //没传图片，数据库也没有图片，给一个默认的图片
                $data['avatar'] = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/avatar/img.jpg';
            }
            $arr[avatar] = $data['avatar'];
            $res = $model->where(array('id'=>$user_id))->setField($arr);
            if($res){
                $Str = array(
                    'code'  =>  '200',
                    'msg'   =>  '头像更新成功'
                );
                exit(json_encode($Str));
            }
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
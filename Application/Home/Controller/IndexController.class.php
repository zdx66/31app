<?php
namespace Home\Controller;
use Think\Controller\RestController;
class IndexController extends RestController
 {
    /*
     * 获取APP管理员Token 
     */
    function Index()
    {
        $url = C('URL') . "/token";
        $data = array(
            'grant_type' => 'client_credentials',
            'client_id' => C('CLIENT_ID'),
            'client_secret' => C('CLIENT_SECRET')
        );
        $rs = json_decode($this->curl($url, $data), true);
        $this->token = $rs['access_token'];
        return $this->token;
    }
    /*:
     * 注册IM用户(授权注册)$username, $password, $nickname
     */
    function hx_register()
    {  
        $username = I('post.cellphone');
        $pwd = I('post.password');
        $password = md5(md5($pwd));
        $nickname = I('post.nickname');
        $sex = I('post.sex');
        $birthdate = I('post.birthdate');
        $avatar = I('post.avatar');
        if($username == null || $pwd ==null || $nickname == null || $sex == null || $birthdate == null || $avatar == null){
            $data= array('msg'=>'所填各项都不能为空，请重新输入');
            exit(json_encode($data));
        }
        if($sex == 1){
            $sex = 'male';
        }elseif($sex == 2){
            $sex = 'female';
        }
        //判断手机号是否合法
        if(!is_cellphone($username)){
            $str = array(
                'code'  =>  '201',
                'cellphone' =>  $username,
                'msg'   =>  '该手机号不合法'
            );
            exit(json_encode($str));
        }
        //限制年龄到18岁以上
        $birthdate = time()-strtotime($birthdate);
        $userage =  number_format($birthdate/(3600*24*365),1);
        if($userage<18){
            $str = array(
                'code'  =>  '201',
                'age'   =>  $userage.'岁',
                'msg'   =>  '不好意思，年龄太小了'
            );
            exit(json_encode($str));
        }

        //上传用户头像/档案照
        if($avatar){
            $avatar = base64_decode_img($avatar);
        }
        //组装图片存储路径
        $time = date("Y-m",time());
        $imgpath = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$time.'/';
        if(!is_dir($imgpath)){
            if(!mkdir($imgpath,0777,true))
            {         
                echo "无法创建该路径";
            }
        }  
        $imgname = time();
        file_put_contents ($imgpath.$imgname.".jpg", $avatar, FILE_USE_INCLUDE_PATH);
        //将新的图片路径放到数据库中
        $avatar = $imgpath.$imgname.".jpg";
        $file_1 = $avatar;
        $user = D('User'); 
        $userData = D('UserData');
        $userProfile = D('UserProfile');
        $res = $user->field('id')->where(array('cellphone '=>$username))->find();
        if($res){
            $result = array(   
            'code' => '201',   
            'msg' => '该手机号已注册',   
            'data' =>$username  
                ); 
            exit(json_encode($result));
        }
        $t = time();
        $id = $user->add(array('cellphone'=>$username,'password_hash'=>$password,'nickname'=>$nickname,'sex'=>$sex,'avatar'=>$avatar,'created_at'=>$t,'status'=>10));
        $usr = $userData->add(array('user_id'=>$id));
        $usrp = $userProfile->add(array('user_id'=>$id,'birthdate'=>$birthdate,'file_1'=>$file_1));
        //环信
        $url = C('URL') . "/users";
        $token = $this->Index();
        if($id && $usr && $usrp){ 
            $user->commit();
            $userData->commit();
            $userProfile->commit();
            $data = array(
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
            );
            $code = '200';
            $header = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            );
        }else{
            $user->rollback();
            $userData->rollback();
            $userProfile->rollback();
            $data = array(
                'code'      => '201',
                'msg'       => '注册失败'
            );
            $header = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            );
        }
        $str = array('data' => $data,'header'=>$header,'code'=>$code);
        echo json_encode($str);
        return $this->curl($url, $data, $header, "POST");
    }
    
     /*
     * 修改用户昵称
     * $username, $nickname
     */
    public function hx_user_update_nickname()
    {
        $GetPutData = file_get_contents("php://input");
        $GetPutData = explode("&", $GetPutData);
        //用户名
        $GetPutData[0] = explode("=", $GetPutData[0] );
        $username = $GetPutData[0][1];
        //密码
        $GetPutData[1] = explode("=", $GetPutData[1]);
        $nickname = $GetPutData[1][1];
        if($username == null || $nickname == null)
        {
            exit(json_encode("用户名或密码不能为空"));
        }
        $time = time();
        $data = array('nickname'=>$nickname,'update_at'=>$time);
        $user = D('user');
        //更改数据库昵称
        $result = $user->where(array('cellphone'=>$username))->setField($data);
        $url = C('URL') . "/users/${username}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        if($result)
        {
            $str = array('data'=>$data,'code'=>'200',"header"=>$header);
            echo json_encode($str);
        }else{
            $str = array('code'=>'201','msg'=>"昵称更改失败");
            exit(json_encode($str));
        }
        //更改环信昵称
        return $this->curl($url, $data, $header, "PUT");
    }
    
    /**
     * 修改个人信息接口
     */
    function update_user_info(){
        
        //获取原来的旧数据
        $usermodel = D('user');
        $data = I("post.");
        $user_id = $data['user_id'];
        $data2['work'] = $data['work'];
        $info = $usermodel
                ->alias('user')
                ->join('left join __USER_DATA__ as data on (user.id = data.user_id)')
                ->join('left join __USER_PROFILE__ as pro on (user.id = pro.user_id)')
                ->where(array('user.id'=>$user_id))
                ->find();
        //标签
        $label = D("label");
        $labelinfo = $label->field("id,labelname")->where("")->select();
        //地区
        $area = D('address');
        $areaInfo = $area->field('id,addrname,pid')->where('')->select();
        $username = $data['username'];
        $str = array(
            'userinfo'  =>  $info,
            'label_list'     =>  $labelinfo,
            'area_list'      =>  $labelinfo
        );
        //echo json_encode($str);
        
        //检查用户是否存在
        if(!$usermodel->where(array("id"=>$user_id))->find())
        {
           $str = array(
               'code'   =>  '201',
               'msg'    =>  '该用户不存在'
           );
           exit(json_encode($str));
        }
        
        //验证手机号
        $cellphone = isset($data['cellphone'])?$data['cellphone']:'';
        if($cellphone){
            if(!is_cellphone($cellphone)){
                $str = array(
                    'code'  =>  '201',
                    'msg'   =>  '输入的手机号不合法'
                ); 
                exit(json_encode($str));
            }
        }
        
        //检验年龄
        $birthdate = $data['birthdate'];
        $birthdate = time()-strtotime($birthdate);
        $userage =  number_format($birthdate/(3600*24*365),1);
        if($userage<18){
            $str = array(
                'code'  =>  '201',
                'age'   =>  $userage.'岁',
                'msg'   =>  '不好意思，年龄太小了'
            );
            exit(json_encode($str));
        }
        
        unset($data['user_id']);
        unset($data['nickname']);
        unset($data['sex']);
        unset($data['work']);
        unset($data['update_at']);
        //验证邮箱格式
        $email = isset($data['email'])?$data['email']:'';
        if($email){
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";   
            if(!preg_match($pattern, $email )){
                $data = array(
                    'code'  =>  '201',
                    'msg'   =>  '邮箱格式不合法'
                );
                exit(json_encode($data));
            }
        }
        
        //判断是否更改更改用户头像或更改用户档案照
        $da[0]['file_1'] = isset($data['file_1'])?$data['file_1']:'';
        $da[0]['file_2'] = isset($data['file_2'])?$data['file_2']:'';
        $da[0]['file_3'] = isset($data['file_3'])?$data['file_3']:'';
        $da[0]['file_4'] = isset($data['file_4'])?$data['file_4']:'';
        $da[0]['file_5'] = isset($data['file_5'])?$data['file_5']:'';
        $da[0]['avatar'] = isset($data['avatar'])?$data['avatar']:'';
        foreach ($da[0] as $v=>$k){
            if($data[$v]){
                $data[$v] = base64_decode_img($data[$v]);
                $arr[$v] = $v;
            }           
        }
        //如果传过来了头像
        //删除旧图片
        $user_profile_model = D('UserProfile');
        $oldimg_1 = $user_profile_model
                ->alias("pro")
                ->join('left join __USER__ as user on (user.id=pro.user_id)')
                ->field($arr)
                ->where(array("user_id" => $user_id))
                ->find();
        foreach($oldimg_1 as $v)
        {
            @unlink($v);
        }
        
        //将图片存放到服务器上
        $t = date("Y-m",time());
        $imgpath_1 = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$t.'/';
        if(!is_dir($imgpath_1)){
            if(!mkdir($imgpath_1,0777,true))
            {         
                echo "无法创建该路径";
            }
        }  

        foreach($arr as $v=>$k)
        {
            if($v){
                $allPath = $imgpath_1.$user_id.'_'.rand(0, 20).'_';
                file_put_contents($allPath.$v.'.jpg', $k,FILE_USE_INCLUDE_PATH);
                $data[$v] = $allPath.$v.'.jpg';
            }
        }

        //数据更改的时间
        $time = time();
        $data2['update_at'] = $time;
        $data2['avatar'] = $data['avatar'];
        unset($data['avatar']);
        unset($data['user_id']);
        //更新用户信息
        $result = $user_profile_model
                ->where(array("user_id"=>$user_id))
                ->setfield($data);
        $result2 = $usermodel->where(array("id"=>$user_id))->setField($data2);
        //echo $usermodel->getLastSql();
        if($result&&$result2)
        {
            $data['username'] = $username;
            $str = array(
                'data' =>  $data,
                'data2'=>  $data2,
                "code"  => "200",
                "msg"   =>  "修改成功"
            );    
            exit(json_encode($str));
        }else{
             $str = array(
                'data'  => $data,
                "code"  => "201",
                "msg"   =>  "操作失败"
            );
             exit(json_encode($str));
        }    
    }
    
    /**
     * 更新用户其他信息
     */
    function update_user_other_data()
    {
        $userdata_model = D("UserData");
        $data = I("post.");
        $user_id = $data['user_id'];
        unset($data['user_id']);
        //如果是女性，必须要有need_coin值
        if($data['sex'] == 2){
            $data['need_coin'] = empty($data['need_coin'])?70:$data['need_coin'];
        }
        $res = $userdata_model->where(array("user_id"=>$user_id))->setField($data);
        if(!$res){
            $str = array(
                'data'  =>  $data,
                'code'  =>  '201',
                'msg'   =>  '操作失败'
            );
            exit(json_encode($str));
        }
        $data['user_id']    = $user_id;
        $str = array(
            'data'  =>  $data,
            'code'  =>  '200',
            'msg'   =>  '操作成功'
        );
        exit(json_encode($str));
    }
    
    /**
     * 更新用户扩展信息接口
     
    function user_profile_info()
    {
        $user_profile_model = D("UserProfile");
        $data = I("post.");
        $user_id = $data['user_id'];
        unset($data['user_id']);
        //判断并处理传过来的档案照

        $da[0]['file_1'] = isset($data['file_1'])?$data['file_1']:'';
        $da[0]['file_2'] = isset($data['file_2'])?$data['file_2']:'';
        $da[0]['file_3'] = isset($data['file_3'])?$data['file_3']:'';
        $da[0]['file_4'] = isset($data['file_4'])?$data['file_4']:'';
        $da[0]['file_5'] = isset($data['file_5'])?$data['file_5']:'';
        foreach ($da[0] as $v=>$k){
            if($data[$v]){
                $data[$v] = base64_decode_img($data[$v]);
                $arr[$v] = $v;
            }           
        }
        //删除旧图片
        $oldimg_1 = $user_profile_model->field($arr)->where(array("user_id" => $user_id))->find();
        foreach($oldimg_1 as $v)
        {
            @unlink($v);
        }
        //将图片存放到服务器上
        $time = date("Y-m",time());
        $imgpath_1 = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$time.'/';
        if(!is_dir($imgpath_1)){
            if(!mkdir($imgpath_1,0777,true))
            {         
                echo "无法创建该路径";
            }
        }  
        foreach($data as $v=>$k)
        {
            $allPath = $imgpath_1.$user_id.'_'.rand(0, 20).'_';
            file_put_contents($allPath.$v.'.jpg', $k,FILE_USE_INCLUDE_PATH);
            $data[$v] = $allPath.$v.'.jpg';
        }
        $res = $user_profile_model->where(array("user_id"=>$user_id))->setField($data);
        if(!$res)
        {
            $str = array(
                'data'  =>  $data,
                'code'  =>   '201',
                'msg'   =>   '操作失败'
            );
            exit(json_encode($str));
        }
        
        $data['user_id'] = $user_id;
        $str = array(
            'data'  =>  $data,
            'code'  =>  '200',
            'msg'   =>  '操作成功'
        );
        exit(json_encode($str));
    }*/
      
    /*
     * 给IM用户的添加好友 $owner_username, $friend_username
     */
    public function hx_contacts()
    {
        $owname = json_decode($_POST['owner_username'],true);
        $fname = json_decode($_POST['friend_username'],true);
        $owner_username = $owname['owner_username'];
        $friend_username = $fname['friend_username'];
        $url = C('URL')  . "/users/${owner_username}/contacts/users/${friend_username}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "POST");
    }
    /*
     * 解除IM用户的好友关系$owner_username, $friend_username
     */
    public function hx_contacts_delete()
    {
        $owname = json_decode($_POST['owner_username'],true);
        $fname = json_decode($_POST['friend_username'],true);
        $owner_username = $owname['owner_username'];
        $friend_username = $fname['friend_username'];
        $url = C('URL')  . "/users/${owner_username}/contacts/users/${friend_username}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "DELETE");
    }
    /*
     * 查看好友$owner_username
     */
    public function hx_contacts_user()
    {
        $owname = json_decode($_POST['owner_username'],true);
        $owner_username = $owname['owner_username'];
        $url = C('URL') . "/users/${owner_username}/contacts/users";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "GET");
    }
    
    /* 发送文本消息 */
    public function hx_send($sender, $receiver, $msg)
    {
        $url = C('URL') . "/messages";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        $data = array(
            'target_type' => 'users',
            'target' => array(
                '0' => $receiver
            ),
            'msg' => array(
                'type' => "txt",
                'msg' => $msg
            ),
            'from' => $sender,
            'ext' => array(
                'attr1' => 'v1',
                'attr2' => "v2"
            )
        );
        return $this->curl($url, $data, $header, "POST");
    }
    /* 查询离线消息数 获取一个IM用户的离线消息数 */
    public function hx_msg_count($owner_username)
    {
        $url = C('URL') . "/users/${owner_username}/offline_msg_count";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "GET");
    }
    
    /*
     * 获取IM用户[单个]$username
     */
    public function hx_user_info()
    {
        $username = I("get.username");
        if(!$username)
        {
            $data = array(
                "code"      =>  "201",
                "msg"       =>  "用户名不能为空"
            );
            exit(json_encode($data));
        }
        $user = D("user");
        $result = $user
                ->table("__USER__ AS user ")
                ->join("__USER_DATA__ AS data on user.id = data.user_id")
                ->join("__USER_PROFILE__ AS pro on user.id = pro.user_id")
                ->field("*")
                ->where(array("username" => $username))
                ->find();
        //标签
        $label = D("label");
        $labelinfo = $label->field("id,labelname")->where("")->select();
        //地区
        $area = D('address');
        $areaInfo = $area->field('id,addrname,pid')->where('')->select();
        
        if(!$result){
            $data = array(
                "code"  =>  "201",
                "msg"   =>  "该用户不存在"
            );
            exit(json_encode($data));
        }
        $url = C('URL') . "/users/${username}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        //将用户信息返回给客户端
        $str = array(
            "data"  => $result,
            'labelList' =>  $labelinfo,
            'areaList'  =>  $areaInfo,
            "code"  => "200",
            "header"    => $header
        );
        echo json_encode($str);
        return $this->curl($url, "", $header, "GET");
    }
    /*
     * 获取IM用户[批量]$limit
     */
    public function hx_user_infos()
    {
        $url = C('URL') . "/users?${limit}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "GET");
    }
    /**
     * 修改用户密码
     */
    public function hx_user_alter_password()
    {
        $GetPutData = file_get_contents("php://input");
        $GetPutData = explode("&", $GetPutData);
        //用户名
        $GetPutData[0] = explode("=", $GetPutData[0]);
        $username = $GetPutData[0][1];
        //原密码
        $GetPutData[1] = explode("=", $GetPutData[1]);
        $oldpassword = ($GetPutData[1][1]);
        //新密码
        $GetPutData[2] = explode("=", $GetPutData[2]);
        $newpassword = $GetPutData[2][1];
        if($username == null || $oldpassword == null || $newpassword == null){
            $data = array(
                "code"  => '201',
                'msg'   => '参数不能为空'
            );
            exit(json_encode($data));
        }
        $user = D("user");
        $data = array("username"=>$username,"password_hash"=>md5(md5($oldpassword)));
        $result = $user->where($data)->find();
        if(!$result)
        {
            $data = array(
                "code"  => "201",
                "msg"   => "用户名或密码输入错误"
            );
            exit(json_encode($data));
        }
        $time = time();
        $data = array(
            "password_hash"     =>  md5(md5($newpassword)),
            "update_at"         =>  $time
        );
        $res = $user->where(array("username"=>$username))->setField($data);
        if($res)
        {
            $data = array(
                "username"   => $username,
                "newpassword"=> $data['password_hash']
                );
            $url = C('URL') . "/users/${username}/password";
            $token = $this->Index();
            $header = array(
                'Authorization: Bearer ' . $token
            );
            $str = array("data"=>$data,"header"=>$header,"code"=>"200");
            echo json_encode($str);
            return $this->curl($url, $data, $header, "PUT");
        }else{
            $data = array(
                "code"  =>  "201",
                "msg"   =>  "修改密码操作失败"
            );
            exit(json_encode($data));          
        }              
    }
    
    /*
     * 重置IM用户密码$username, $newpassword
     */
    public function hx_user_update_password()
    {
        $data0 = file_get_contents('php://input');
        $data0 = explode('&', $data0);
        $password = explode("=", $data0[1]);
        //前台获取的新密码
        $password = $password[1];
        $username = explode("=", $data0[0]);
        $username = $username[1];
        $data['password_hash'] = md5(md5($password)); 
        if($data['password_hash']== null ){
            $data = array('msg'=>'密码为空，重置失败');
            exit(json_encode($data));
        }
        $usr = D('user');
        $user = $usr->field('password_hash')->where(array('username' => $username))->find();
        if(!$user){
            $data = array('msg'=>'该用户不存在，重置失败');
            exit(json_encode($data));
        }
        $data['update_at'] = time();
        //重置数据库密码
        $result = $usr->where(array(' username ' => $username,'status'=>10))->setField($data);
        //重置环信密码
        $token = $this->Index();
        if($result){
            $data = array(
                'username'      =>$username,
                'newpassword'  =>$data['password_hash']
            );
            $code = '200';
            $header = array(
            'Authorization: Bearer ' . $token
             ); 
            $str = array('data'=>$data,'code'=>$code,'header'=>$header);
            echo json_encode($str);
            $url = C('URL') . "/users/${username}/password";
            return $this->curl($url, $data, $header, "PUT");   
        }else{   
            $data = array('msg'    => '密码重置失败');
            $code = '201';
            $str = array('data'=>$data,'code'=>$code);
            echo json_encode($str);
        }       
    }
    
    /*
     * 删除IM用户[单个]
     */
    public function hx_user_delete($username)
    {
        $url = C('URL') . "/users/${username}";
        $token = $this->Index();
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->curl($url, "", $header, "DELETE");
    }
   
    /*
     *
     * curl
     */
    private function curl($url, $data, $header = false, $method = "POST")
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $ret = curl_exec($ch);
        return $ret;
    }
 }
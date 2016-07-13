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
        $name = $_POST['username'];
        $pwd = $_POST['password'];
        if($name == null || $pwd ==null){
            $data= array('msg'=>'用户名或密码不为空');
            exit(json_encode($data));
        }
        $username = $name;
        $password = md5(md5($pwd));
        $nickname = $name;
        $user = D('User'); 
        $userData = D('UserData');
        $userProfile = D('UserProfile');
        $res = $user->field('id')->where(array('username '=>$username))->find();
        if($res){
            $result = array(   
            'flag' => 'error',   
            'msg' => '用户名已存在',   
            'data' =>$username  
                ); 
            exit(json_encode($result));
        }
        $time = time();
        $id = $user->add(array('username'=>$username,'password_hash'=>$password,'nickname'=>$nickname,'created_at'=>$time,'status'=>10));
        $usr = $userData->add(array('user_id'=>$id));
        $usrp = $userProfile->add(array('user_id'=>$id));
        $url = C('URL') . "/users";
        $token = $this->Index();
        if($id && $usr && $usrp){ 
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
    /**
     * 修改个人基本信息接口
     */
    function update_user_info(){
        
        $usermodel = D('user');
        $data = I("post.");
        $username = $data['username'];
        //验证手机号
        if(!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/",$data['cellphone'])){
            $data = array(
                'code'  =>  '201',
                'msg'   =>  '手机号码格式不合法'
            );
            exit(json_encode($data));
        }
        //验证邮箱格式
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";   
        if(!preg_match($pattern, $data['email'] )){
            $data = array(
                'code'  =>  '201',
                'msg'   =>  '邮箱格式不合法'
            );
            exit(json_encode($data));
        }
        //数据更改的时间
        $time = time();
        $data['update_at'] = $time;
        unset($data['username']);
        //更新用户信息
        $result = $usermodel
                ->where(array("username"=>$username))
                ->setfield($data);
        if($result)
        {
            $data['username'] = $username;
            $str = array(
                'data' =>  $data,
                "code"  => "200",
                "msg"   =>  "修改成功"
            );
            exit(json_encode($str));
        }else{
             $data = array(

                "code"  => "201",
                "msg"   =>  "该用户不存在，修改失败"
            );
             exit(json_encode($data));
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
        $res = $userdata_model->where(array("user_id"=>$user_id))->setField($data);
        if(!$res){
            $data = array(
                'code'  =>  '201',
                'msg'   =>  '操作失败'
            );
            exit(json_encode($data));
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
     */
    function user_profile_info()
    {
        $user_profile_model = D("UserProfile");
        $data = I("post.");
        $user_id = $data['user_id'];
        unset($data['user_id']);
        $res = $user_profile_model->where(array("user_id"=>$user_id))->setField($data);
        if(!$res)
        {
            $data = array(
                'code' =>   '201',
                'msg'  =>   '用户不存在，更新失败'
            );
            exit(json_encode($data));
        }
        $data['user_id'] = $user_id;
        $str = array(
            'data'  =>  $data,
            'code'  =>  '200',
            'msg'   =>  '操作成功'
        );
        exit(json_encode($str));
    }
    
    /**
     * 更换用户照片
     * 1、头像
     * 2、其他照片
     */
    function update_user_img()
    {
        
    }
    
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
        $result = $user->where(array('username'=>$username))->setField($data);
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
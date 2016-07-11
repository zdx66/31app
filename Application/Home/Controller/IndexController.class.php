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
    }
    /*
     * 注册IM用户(授权注册)$username, $password, $nickname
     */
    function hx_register()
    { 
        $name = $_POST['username'];
        $pwd = I('post.password');
        if($name == null || $pwd == null){
            $result = '用户名或密码为空，不能注册';
           exit(json_encode($result));
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
        
        if($id && $usr && $usrp){ 
            $data = array(
                'username' => $username,
                'password' => $password,
                'nickname' => $nickname,
            );
            $code = '200';
            $header = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token
            );
           
        }else{
                $code ='201';
        }
        $str = array('data' => $data,'header'=>$header,'code'=>$code);
        echo json_encode($str);
        return $this->curl($url, $data, $header, "POST");
    }
    
    //用户登录
    public function hx_login(){
        
    }
    
    //用户退出登录
    public function hx_logout(){
        
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
        $header = array(
            'Authorization: Bearer ' . $this->token
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
        $header = array(
            'Authorization: Bearer ' . $this->token
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
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        return $this->curl($url, "", $header, "GET");
    }
    
    /* 发送文本消息 */
    public function hx_send($sender, $receiver, $msg)
    {
        $url = C('URL') . "/messages";
        $header = array(
            'Authorization: Bearer ' . $this->token
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
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        return $this->curl($url, "", $header, "GET");
    }
    
    /*
     * 获取IM用户[单个]
     */
    public function hx_user_info($username)
    {
        $url = C('URL') . "/users/${username}";
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        return $this->curl($url, "", $header, "GET");
    }
    /*
     * 获取IM用户[批量]
     */
    public function hx_user_infos($limit)
    {
        $url = C('URL') . "/users?${limit}";
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        return $this->curl($url, "", $header, "GET");
    }
    /*
     * 重置IM用户密码$username, $newpassword
     */
    public function hx_user_update_password()
    {
        $username = I('post.username');
        $da['password_hash'] = I('post.newpassword');
        if($da['password_hash']== null ){
            $data1 = array('msg'=>'密码为空，重置失败');
            exit(json_encode($data1));
        }
        $usr = D('user');
        $result = $usr->where(array('username'=>$username))->save($da);    
        if($result){
            $data1 = array(
                'username'  =>$username,
                'newpassword'  =>$da['password_hash']
            );
            $code = '200';
            $header = array(
            'Authorization: Bearer ' . $this->token
             ); 
            
        }else{
            $data1 = array('msg'    => '密码重置失败');
            $code = '201';
        }
        $str = array('data'=>$data1,'code'=>$code,'header'=>$header);
        echo json_encode($str);
        
        $url = C('URL') . "/users/${username}/password";
        $data1['newpassword'] = $da['password_hash'];
        return $this->curl($url, $data1, $header, "PUT");
    }
    
    /*
     * 删除IM用户[单个]
     */
    public function hx_user_delete($username)
    {
        $url = C('URL') . "/users/${username}";
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        return $this->curl($url, "", $header, "DELETE");
    }
    /*
     * 修改用户昵称
     */
    public function hx_user_update_nickname($username, $nickname)
    {
        $url = C('URL') . "/users/${username}";
        $header = array(
            'Authorization: Bearer ' . $this->token
        );
        $data['nickname'] = $nickname;
        return $this->curl($url, $data, $header, "PUT");
    }
    /*
     *
     * curl
     */
    private function curl($data, $header = false, $method = "POST")
    {
        $ch = curl_init(C('URL') );
        curl_setopt($ch, CURLOPT_URL, C('URL') );
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

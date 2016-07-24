<?php
namespace Home\Controller;
use Think\Controller;
class TrendsController extends Controller{
    
    //发表动态
    public function Publish(){
        
        $circle = D('circle');
        $data = I('post.');
        $user_id = $data['user_id']?$data['user_id']:exit('用户编号没传过来');
        $words = $data['words']?$data['words']:exit('用户没有发表任何东西，发表动态失败');
        $arr[0]['img1'] =$data['img1']?$data['img1']:'';
        $arr[0]['img2'] =$data['img2']?$data['img2']:'';
        $arr[0]['img3'] =$data['img3']?$data['img3']:'';
        $arr[0]['img4'] =$data['img4']?$data['img4']:'';
        $arr[0]['img5'] =$data['img5']?$data['img5']:'';
        $arr[0]['img6'] =$data['img6']?$data['img6']:'';
        $arr[0]['img7'] =$data['img7']?$data['img7']:'';
        $arr[0]['img8'] =$data['img8']?$data['img8']:'';
        $arr[0]['img9'] =$data['img9']?$data['img9']:'';
        foreach($arr[0] as $v=>$k){
            if($k){
                $arr[$v]=$k;
                $data2[$v] = base64_decode_img($arr[$v]);
                $data3[$v] = $v;
            }
        }
        //如果图片不空,删除图片
        $oldImg = $circle->field($data3)->where(array('user_id'=>$user_id))->find();
        foreach ($oldImg as $v){;
            //@unlink($v);
        }
        if(!empty($data2)){
            $date = date('Y-m-d',time());
            $imgpath = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$date.'/';
            if(!is_dir($imgpath))
            {
                if(!mkdir($imgpath,0777,true))exit('目录创建失败') ; 
            }
            //将图片存放到服务器上
            foreach ($data2 as $k=>$v){
                $allPath = $imgpath.$user_id.'-'.rand(0, 30);
                file_put_contents($allPath.$k.'.jpg', $v,FILE_USE_INCLUDE_PATH);
                $data[$k] = $allPath.$k.'.jpg';
            }
        }
        //是否定位
        $address = $data['address']?$data['address']:'';
        //发表时间
        $data['add_time'] = time();
        $res = $circle->where(array('user_id'=>$user_id))->add($data);
        if($res){
            $str = array(
                'code'  =>  '200',
                'data'  =>  $data,
                'msg'   =>  '动态发表成功'
            );
            exit(json_encode($str));
        }
        $str = array(
            'code'  =>  '201',
            'msg'   =>  '动态发表失败'
        );
        exit(json_encode($str));
    }
    
    //动态列表
    public function lst(){
        
        //根据权限查看动态(未实现)
        $data = I('post.');
        dump($data);
        $data['user_id'] = $data['user_id']?$data['user_id']:exit('用户id必填');
        //$user = D('user');
        $circle = D('circle');
        $TrendInfo = $circle
                ->alias('cir')
                ->join('left join __USER__ as user on(cir.user_id = user.id)')
                ->join('left join __DISCUSS__ as dis on(cir.user_id = dis.user_id)')
                ->field('cir.id,avatar,nickname,words,img1,img2,img3,img4,img5,img6,img7,img8,img9,zan,othersay,saytime,theme,other_id,reply_num')
                ->where(array())
                ->order('add_time desc ')
                ->select();
        if($TrendInfo){
            $str = array(
                'code'  =>  '200',
                'data'  =>  $TrendInfo,
                'msg'   =>  '操作成功'
            );
            echo json_encode($str);
        }
        //用户被赞
        if($data['zan'] == 1){
            $res1 = $circle->where(array('id'=>$data['id']))->setInc('zan');
            echo $circle->getLastSql();
            if($res1)
            {
                echo '点赞成功';
            }
        }
        
        //动态被评论时，reply_num+1
        if($data['flag'] == 1)
        {
            $res2 = $circle->where(array('id'=>$data['id']))->setInc('reply_num');
            if($res2){
                echo '评论数成功加1';
            }
        }
        
    }
}
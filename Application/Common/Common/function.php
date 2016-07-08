<?php
/*************************** api开发辅助函数 **********************/ 
/**  @param null $msg  返回正确的提示信息 
 *  @param flag success CURD 操作成功 
 *  @param array $data 具体返回信息 
 *  Function descript: 返回带参数，标志信息，提示信息的json 数组 * */
function returnApiSuccess($msg = null,$data = array()){  
    $result = array(   
        'flag' => 'Success',   
        'msg' => $msg,   
        'data' =>$data  
            );  
    print json_encode($result);
    
}  

/** * @param null $msg  返回具体错误的提示信息
 *  * @param flag success CURD 操作失败 
 * * Function descript:返回标志信息 ‘Error'，和提示信息的json 数组 */
function returnApiError($msg = null){ 
    $result = array(   
        'flag' => 'Error',  
        'msg' => $msg, 
        );  
    print json_encode($result);
    
} 

/** * @param null $msg  返回具体错误的提示信息 
 * * @param flag success CURD 操作失败
 *  * Function descript:返回标志信息 ‘Error'，和提示信息，当前系统繁忙，请稍后重试； */
function returnApiErrorExample(){  
    $result = array(   
        'flag' => 'Error',  
        'msg' => '当前系统繁忙，请稍后重试！', 
        ); 
    print json_encode($result);
    
}  

/** * @param null $data 
 * * @return array|mixed|null 
 * * Function descript: 过滤post提交的参数； * */
function checkDataPost($data = null){ 
    if(!empty($data)){   
        $data = explode(',',$data); 
        foreach($data as $k=>$v){  
            if((!isset($_POST[$k]))||(empty($_POST[$k]))){    
                if($_POST[$k]!==0 && $_POST[$k]!=='0'){        
                    returnApiError($k.'值为空！');      
                    }      
                }   
            }   
            unset($data);  
            $data = I('post.'); 
            unset($data['_URL_'],$data['token']);
            return $data; 
            }  
        } 
        
/** * @param null $data
 *  * @return array|mixed|null 
 * * Function descript: 过滤get提交的参数； * */
function checkDataGet($data = null){  
    if(!empty($data)){  
        $data = explode(',',$data); 
        foreach($data as $k=>$v){    
            if((!isset($_GET[$k]))||(empty($_GET[$k]))){     
                if($_GET[$k]!==0 && $_GET[$k]!=='0'){      
                    returnApiError($k.'值为空！');        
                    
                }   
            }   
        }   
        unset($data); 
        $data = I('get.'); 
        unset($data['_URL_'],$data['token']);
        return $data; 
        }
}

/**
 * 获取个人详细信息
 *  */
function getMyReleaseInfo(){
       //检查是否通过post方法得到数据
       checkdataPost('id');
       $where['id'] =  $_POST['id'];
       $field[] = 'id,cid,groupid,nickname,auth_key,status';
       $releaseInfo = $this->release_obj->findRelease($where,$field);
       //$releaseInfo['remark'] =  mb_substr($releaseInfo['remark'],0,49,'utf-8').'...';
       //多张图地址按逗号截取字符串，截取后如果存在空数组则需要过滤掉
       //$releaseInfo['fruit_pic'] =  array_filter(explode(',', $releaseInfo['fruit_pic']));
       //$fruit_pic = $releaseInfo['fruit_pic'];unset($releaseInfo['fruit_pic']);
       //为图片添加存储路径
//       foreach($fruit_pic as $k=>$v ){
//           $releaseInfo['fruit_pic'][] =  'http://'.$_SERVER['HTTP_HOST'].'/Uploads/Release/'.$v;
//       }
       if($releaseInfo){
           returnApiSuccess('',$releaseInfo);
       }else{
           returnApiError( '什么也没查到(+_+)！');
       }
   }
   
   /**
    * 查询一条数据
    *     */
   function findRelease($where,$field){
       if($where['status'] == '' || empty($where['status'])){
           $where['status'] = array('neq','10');
       }
       $result = $this->where($where)->field($field)-find();
       return $result;
   }
   
   //查询用户登录的过期时间
   function getexpir_time($id)
   {
       $result = $this->where('id = '.$id)->field('expire_time')->find();
       return $result;
   }




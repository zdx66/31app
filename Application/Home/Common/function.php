<?php
function is_base64_encoded($data){
    if(preg_match("%^[a-zA-Z0-9/+]*={0,2}$%", $data)){
        return TRUE;
    }
    return FALSE;
}

function is_cellphone($cellphone = '')
{
    if($cellphone){
        if(preg_match("/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/",$cellphone)){
            return TRUE;
        }
    }
    return false;

}

function is_email($email = '')
{
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
    return FALSE;
}

//将图片解码并存到数据库，服务器中
function deal_image($avatar)
{
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
}
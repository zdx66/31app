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

//base64解码
function base64_decode_img($file)
{
    $file  =   substr($file, strpos($file, ",")+1);
    if(!is_base64_encoded($file)){
        $str = "非约定编码格式";
        return $str;
    }
    $file = base64_decode(str_replace(" ","+",$file));
    return $file;
}

function del_old_img($imgpath)
{
    
}
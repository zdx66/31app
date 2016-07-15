<?php
function is_base64_encoded($data){
    if(preg_match("%^[a-zA-Z0-9/+]*={0,2}$%", $data)){
        return TRUE;
    }
    return FALSE;
}

function is_cellphone($cellphone = '')
{
    if(!preg_match("/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/",$cellphone)){
        return FALSE;
    }
    return TRUE;

}
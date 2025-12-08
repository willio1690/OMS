<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 公共共享Lib方法类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.08
 */
class dealer_common
{
    /**
     * 替换特定字符为空
     * 
     * @param string $str
     * @return string
     */

    public function replaceChar($str)
    {
        $str = str_replace(array("\r\n","\r","\n","\t","\\","'",'"',"	", ' ','&nbsp;'), '', $str);
        
        return $str;
    }
    
    /**
     * 检查手机号有效性
     * 
     * @param $mobile
     * @param $error_msg
     * @return boolean
     */
    public function checkMobile($mobile, $error_msg=null)
    {
        $pattern = "/^\d{8,15}$/i";
        
        $mobile = str_replace(" ", "", $mobile);
        if(empty($mobile)) {
            $error_msg = '手机号码必需填写';
            return false;
        }
        
        if (!preg_match($pattern, $mobile)) {
            $error_msg = '请输入正确的手机号码';
            return false;
        }
        
        if ($mobile[0] == '0') {
            $error_msg = '手机号码前请不要加0';
            return false;
        }
        
        return true;
    }
    
}

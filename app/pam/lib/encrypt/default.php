<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pam_encrypt_default{
    /**
     * 加密密码
     * @param string $source_str 原始密码
     * @param int $is_hash256 是否使用SHA256加密（1=是，0=否）
     * @return string 加密后的密码
     */
    public static function get_encrypted($source_str, $is_hash256 = 0){
        $md5_hash = md5($source_str);
        // 如果 is_hash256=1，在MD5基础上再进行SHA256加密
        if ($is_hash256 == 1) {
            return hash('sha256', $md5_hash);
        }
        // 否则只使用MD5加密（兼容旧密码）
        return $md5_hash;
    }
}
<?php
/**
 * Shopex OMS
 * 
 * @copyright Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * @license   Apache-2.0 with additional terms (See LICENSE file)
 */

class entermembercenter_task{
    
    function post_install($options)
    {
        // 生成包含特殊符号、数字、大小写字母的随机密钥
        $specialChars = '!@#$%^&*()_+-='; // 常规特殊符号
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $specialChars;
        $key = '';
        $length = 64; // 密钥长度
        
        // 确保至少包含每种类型的字符
        $key .= 'abcdefghijklmnopqrstuvwxyz'[random_int(0, 25)]; // 小写字母
        $key .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, 25)]; // 大写字母
        $key .= '0123456789'[random_int(0, 9)]; // 数字
        $key .= $specialChars[random_int(0, strlen($specialChars) - 1)]; // 特殊符号
        
        // 填充剩余字符
        $charsetLength = strlen($charset);
        for ($i = strlen($key); $i < $length; $i++) {
            $key .= $charset[random_int(0, $charsetLength - 1)];
        }
        
        // 打乱字符顺序，确保随机性
        $key = str_shuffle($key);
        
        // 存储到配置中
        app::get('entermembercenter')->setConf('auth.key', $key);
        
        // 初始化 net.handshake
        base_kvstore::instance('ecos')->store('net.handshake', md5(microtime()));
    }
}

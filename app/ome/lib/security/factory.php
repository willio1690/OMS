<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 加解密工厂 淘宝安全-数据加密
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */

class ome_security_factory
{
    private $__client;

    function __construct()
    {
    
    }

    /**
     * 加密
     *
     * @param string $val 值
     * @param string $type 字段 
     * @return void
     * @author 
     **/
    public function encrypt($val,$type)
    {
        return $val;
    }

    /**
     * 解密
     *
     * @param string $val 值
     * @param string $type 字段
     * @param string $node_id 节点
     * @return void
     * @author 
     **/
    public function decrypt($val,$type,$node_id)
    {
        return $val;
    }

    /**
     * 判断字段是否加密
     *
     * @param string $val 值
     * @param string $type 字段
     * @return void
     * @author 
     **/
    public function isEncryptData($val,$type)
    {
        try {
            return $this->isLocalEncryptData($val,$type);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 加密单条数据
     *
     * @param string $val 值
     * @param string $type 字段类型（例如：phone、mobile、address、ship_name）
     * @return string
     **/
    public function encryptPublic($val, $type, $isMust=false)
    {
        // 检测数据如果是平台加密数据,则返回原数据
        if(!$isMust && kernel::single('ome_security_hash')->get_code() == substr($val, -5)) {
            return $val;
        }
        
        try {
            return $this->localEncryptPublic($val, $type);
        } catch (Exception $e) {
            return $val;
        }
    }
    
    /**
     * 单条数据解密
     *
     * @param string $val 值
     * @param string $type 字段类型（例如：phone、mobile、address、ship_name）
     * @return string
     **/
    public function decryptPublic($val, $type)
    {
        try {
            if ($this->isLocalEncryptData($val, $type)) {
                return $this->localDecryptPublic($val, $type);
            }
            return $val;
        } catch (Exception $e) {
            return $val;
        }
    }

    /**
     * 查询
     *
     * @return void
     * @author 
     **/
    public function search($val,$type, $node_id=null)
    {
        try {
            return  $this->__client->search($val,$type);
        } catch (Exception $e) {
            return $val;
        }
    }
    
    /**
     * 定义本地hash code
     *
     * @return string
     */
    public function get_local_code()
    {
        return '@local_hash';
    }
    
    /**
     * 返回原始数据
     *
     * @return void
     **/
    public function getLocalOriginText($text)
    {
        if ($this->get_local_code() == substr($text, -11)) {
            $text = substr($text, 0, -11);
        }
        return $text;
    }
    
    /**
     * 本地加密单条数据
     *
     * @param $val 需要被加密的数据
     * @param $type 加密数据的类型（例如：phone、mobile、address、ship_name）
     * @return string
     */
    public function localEncryptPublic($val, $type='')
    {
        // 加密密钥
        //@todo：使用系统config/目录下certi.php证书文件中的：token
        $encryption_key = base_certificate::get('token');
        
        // 初始化向量，必须保存下来以便解密时使用
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // 使用AES-256-CBC加密算法加密手机号
        $encrypted_phone_number = openssl_encrypt($val, 'aes-256-cbc', $encryption_key, 0, $iv);
        
        // 返回IV和加密后的数据，以便后续解密
        return base64_encode($iv . $encrypted_phone_number) . $this->get_local_code();
    }
    
    /**
     * 本地单条数据解密
     *
     * @param $encrypted_data 已经被加密的数据
     * @param $type 加密数据的类型（例如：phone、mobile、address、ship_name）
     * @return string
     */
    public function localDecryptPublic($encrypted_data, $type='')
    {
        // 加密密钥
        //@todo：使用系统config/目录下certi.php证书文件中的：token
        $encryption_key = base_certificate::get('token');
        
        // check
        if(empty($encrypted_data) || !is_string($encrypted_data)){
            return $encrypted_data;
        }
        
        // 去除本地hashcode
        $encrypted_data = $this->getLocalOriginText($encrypted_data);
        
        // 将base64编码的数据解码
        $ciphertext = base64_decode($encrypted_data);
        
        // 获取IV（初始化向量），对于AES-256-CBC来说，IV长度为16字节
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($ciphertext, 0, $iv_length);
        
        // 获取加密信息
        $get_encrypted_data = substr($ciphertext, $iv_length);
        
        // 使用AES-256-CBC解密算法解密
        return openssl_decrypt($get_encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }
    
    /**
     * 判断数据是否被加密
     *
     * @param $data 需要判断的数据
     * @param $type 加密数据的类型（例如：phone、mobile、address、ship_name）
     * @return bool 返回true表示已加密，false表示未加密
     */
    public function isLocalEncryptData($data, $type='')
    {
        if(empty($data) || !is_string($data)){
            return false;
        }
        
        if($this->get_local_code() == substr($data, -11)) {
            return true;
        }
        
        return false;
    }
}
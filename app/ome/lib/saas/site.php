<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_saas_site {
    
    private $manager;
    
    private $key = '';
    private $secret = '';
    private $domain = '';
    private $format = 'xml';
    
    private $shopAccount;
    private $contactName;
    private $contactEmail;
    private $contactMobile;
    private $contactQQ;
    private $contactWangwang;
    
    private $info;
    
    /**
     * __construct
     * @param mixed $manager manager
     * @return mixed 返回值
     */
    public function __construct($manager=null) {
        // SaaS 功能已禁用，密钥已删除
        $this->key = '';
        $this->secret = '';
        $this->format = ome_saas_const::XML;
        
        $this->initDomain();
    }
    
    /**
     * 获取Key
     * @return mixed 返回结果
     */
    public function getKey() {
        return $this->key;
    }
    
    /**
     * 获取Secret
     * @return mixed 返回结果
     */
    public function getSecret() {
        return $this->secret;
    }
    
    /**
     * 获取Domain
     * @return mixed 返回结果
     */
    public function getDomain() {
        return $this->domain;
    }
    
    /**
     * 设置Key
     * @param mixed $key key
     * @return mixed 返回操作结果
     */
    public function setKey($key) {
        $this->key = $key;
    }
    
    /**
     * 设置Secret
     * @param mixed $secret secret
     * @return mixed 返回操作结果
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }
    
    /**
     * 设置Domain
     * @param mixed $domain domain
     * @return mixed 返回操作结果
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        
        if(substr($this->domain, 0, 7) === 'http://') {
            $this->domain = substr($this->domain, 7);
        }
        
        $td = explode('.', $this->domain);
    
        if(isset($td[1]) && $td[1]=='tfh') {
            $td[1] = 'tbfh';
            
            $this->domain = implode('.', $td);
        }
    }
    
    /**
     * 设置ContactName
     * @param mixed $contactName contactName
     * @return mixed 返回操作结果
     */
    public function setContactName($contactName) {
        $this->contactName = $contactName;
    }
    
    /**
     * 获取ContactName
     * @return mixed 返回结果
     */
    public function getContactName() {
        return $this->contactName;
    }
    
    /**
     * 设置ContactEmail
     * @param mixed $contactEmail contactEmail
     * @return mixed 返回操作结果
     */
    public function setContactEmail($contactEmail) {
        $this->contactEmail = $contactEmail;
    }
    
    /**
     * 获取ContactEmail
     * @return mixed 返回结果
     */
    public function getContactEmail() {
        return $this->contactEmail;
    }
    
    /**
     * 设置ContactMobile
     * @param mixed $contactMobile contactMobile
     * @return mixed 返回操作结果
     */
    public function setContactMobile($contactMobile) {
        $this->contactMobile = $contactMobile;
    }
    
    /**
     * 获取ContactMobile
     * @return mixed 返回结果
     */
    public function getContactMobile() {
        return $this->contactMobile;
    }
    
    /**
     * 设置ContactQQ
     * @param mixed $contactQQ contactQQ
     * @return mixed 返回操作结果
     */
    public function setContactQQ($contactQQ) {
        $this->contactQQ = $contactQQ;
    }
    
    /**
     * 获取ContactQQ
     * @return mixed 返回结果
     */
    public function getContactQQ() {
        return $this->contactQQ;
    }
    
    /**
     * 设置ContactWangwang
     * @param mixed $contactWangwang contactWangwang
     * @return mixed 返回操作结果
     */
    public function setContactWangwang($contactWangwang) {
        $this->contactWangwang = $contactWangwang;
    }
    
    /**
     * 获取ContactWangwang
     * @return mixed 返回结果
     */
    public function getContactWangwang() {
        return $this->contactWangwang;
    }
    
    /**
     * 设置ShopAccount
     * @param mixed $shopAccount shopAccount
     * @return mixed 返回操作结果
     */
    public function setShopAccount($shopAccount) {
        $this->shopAccount = $shopAccount;
    }
    
    /**
     * 获取ShopAccount
     * @return mixed 返回结果
     */
    public function getShopAccount() {
        return $this->shopAccount;
    }
    
    /**
     * 设置Manager
     * @param mixed $manager manager
     * @return mixed 返回操作结果
     */
    public function setManager(& $manager) {
        $this->manager = $manager;
        
        return $this->manager;
    }
    
    /**
     * 获取Manager
     * @return mixed 返回结果
     */
    public function getManager() {
        if($this->manager === null) {
            $this->setManager ( new ome_saas_manager ( $this ) );
        }
        
        return $this->manager;
    }
    
    /**
     * 设置Format
     * @param mixed $format format
     * @return mixed 返回操作结果
     */
    public function setFormat($format) {
        $this->format = $format;
    }
    
    /**
     * 获取Format
     * @return mixed 返回结果
     */
    public function getFormat() {
        return $this->format;
    }
    
    /**
     * server info
     */
    public function setInfo($info) {
        $this->info = $info;
        
        $this->info->setSite($this);
    }
    
    public function getInfo() {
        return $this->info;
    }
    
    public function __call($name, $argv) {
        if (method_exists ( $this->info, $name )) {
            return call_user_func ( array (
                $this->info, $name
            ), $argv );
        }
    }
    
    private function initDomain() {
        $this->setDomain($_SERVER['HTTP_HOST']);
    }
    
    public function getHostName() {
        return array_shift(explode('.', $this->domain));
    }
    
    public function getServiceCode() {
        $tmp = explode ( '.', $this->getDomain() );
        
        if (isset ( $tmp ['1'] ) && $tmp ['1'] === 'fh') {
            return ome_saas_const::SERVICE_FCFH_CODE;
        } else {
            return ome_saas_const::SERVICE_TAOBAO_CODE;
        }
    }
}
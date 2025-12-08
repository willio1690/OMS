<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


/*
 * @package base
 * @copyright Copyright (c) 2010, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 */
class taskmgr_cache_memcached extends taskmgr_cache_abstract implements taskmgr_cache_interface
{
    static private $_cacheObj = null;

    function __construct() 
    {
        $this->connect();
    }//End Function

    /**
     * connect
     * @return mixed 返回值
     */
    public function connect() 
    {
        if(!isset(self::$_cacheObj)){
            if(defined('__MEMCACHE_CONFIG') && constant('__MEMCACHE_CONFIG')){
                self::$_cacheObj = new Memcached;
                $config = explode(',', __MEMCACHE_CONFIG);
                foreach($config AS $row){
                    $row = trim($row);
                    if(strpos($row, 'unix://') === 0){
                        //self::$_cacheObj->addServer($row, 0);  todo:memcached不支持unix://
                    }else{
                        $tmp = explode(':', $row);
                        self::$_cacheObj->addServer($tmp[0], $tmp[1]);
                    }
                }
            }else{
                trigger_error('can\'t load __MEMCACHE_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }//End Function

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function fetch($key, &$result) 
    {
        $result = self::$_cacheObj->get($this->create_key($key));
        if(self::$_cacheObj->getResultCode() == Memcached::RES_NOTFOUND){
            return false;
        }else{
            return true;
        }
    }//End Function

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl=0) 
    {
        return self::$_cacheObj->set($this->create_key($key), $value, $ttl);
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {
        $status = self::$_cacheObj->getStats();
        foreach($status AS $key=>$value){
            $return['服务器']   = $key;
            $return['缓存获取'] = $value['cmd_get'];
            $return['缓存存储'] = $value['cmd_set'];
            $return['可使用缓存'] = $value['limit_maxbytes'];
        }
        return $return;
    }//End Function

    /**
     * 是否支持同步的自增单号处理
     */
    public function supportUUID() {

        return true;
    }
    
    /**
     * 累加
     */
    public function increment($key, $offset=1)
    {
        $ret = self::$_cacheObj->increment($this->create_key($key), $offset);

        if ($ret === false) {

            if(self::$_cacheObj->getResultCode() == Memcached::RES_NOTFOUND){
                //key 在memcache 中不存在，需创建
                if (self::$_cacheObj->set($key, $offset, 86400*32)) {

                    $ret = $offset;
                } else {

                    $ret = false;
                }
            }
        }
        return $ret;
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        $ret = self::$_cacheObj->decrement($this->create_key($key), $offset);

        if ($ret === false) {
            if(self::$_cacheObj->getResultCode() == Memcached::RES_NOTFOUND){ 
                //key 在memcache 中不存在，需创建
                $value = 0 - $offset;
                if (self::$_cacheObj->set($key, $value, 86400*32)) {

                    $ret = $value;
                } else {

                    $ret = false;
                }
            }
        }

        return $ret;
    }//End Function

    /**
     * 初始化自增ID值
     * 
     * @return void
     * @author 
     */
    public function setcr($key, $value, $ttl=0)
    {
        return self::$_cacheObj->set($this->create_key($key), $value, $ttl);
    }

    public function delete($key) 
    {
        return self::$_cacheObj->delete($this->create_key($key));
    }//End Function

}//End Class

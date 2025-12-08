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
class base_cache_memcache extends base_cache_abstract implements base_interface_cache
{
    static private $_cacheObj = null;

    function __construct() 
    {
        $this->connect();
        $this->check_vary_list();
    }//End Function

    /**
     * connect
     * @return mixed 返回值
     */
    public function connect() 
    {
        if(!isset(self::$_cacheObj)){
            if(defined('CACHE_MEMCACHE_CONFIG') && constant('CACHE_MEMCACHE_CONFIG')){
                self::$_cacheObj = new Memcache;
                $config = explode(',', CACHE_MEMCACHE_CONFIG);
                foreach($config AS $row){
                    $row = trim($row);
                    if(strpos($row, 'unix://') === 0){
                        self::$_cacheObj->addServer($row, 0);
                    }else{
                        $tmp = explode(':', $row);
                        self::$_cacheObj->addServer($tmp[0], $tmp[1]);
                    }
                }
            }else{
                trigger_error('can\'t load CACHE_MEMCACHE_CONFIG, please check it', E_USER_ERROR);
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
        $result = self::$_cacheObj->get($key);
        if($result === false){
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
        return self::$_cacheObj->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {
        $status = self::$_cacheObj->getStats();
        $return['缓存获取'] = $status['cmd_get'];
        $return['缓存存储'] = $status['cmd_set'];
        $return['可使用缓存'] = $status['limit_maxbytes'];
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
        $ret = self::$_cacheObj->increment($key, $offset);
        if ($ret === false) {
            //返回 false 说明 key 在memcache 中不存在，需创建
            if (self::$_cacheObj->set($key, $offset, 0, 86401)) {

                $ret = $offset;
            } else {

                $ret = false;
            }
        }

        return $ret;
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        $ret = self::$_cacheObj->decrement($key, $offset);
        if ($ret === false) {
            //返回 false 说明 key 在memcache 中不存在，需创建
            $value = 0 - $offset;
            if (self::$_cacheObj->set($key, $value, 0, 86401)) {

                $ret = $value;
            } else {

                $ret = false;
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
        return self::$_cacheObj->set($key, $value, 0, $ttl);
    }

}//End Class

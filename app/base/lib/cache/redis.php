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
class base_cache_redis extends base_cache_abstract implements base_interface_cache
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
            if(defined('CACHE_REDIS_CONFIG') && constant('CACHE_REDIS_CONFIG')){
                self::$_cacheObj = new Redis;
                $config = explode(':', CACHE_REDIS_CONFIG);
                self::$_cacheObj->connect($config[0], $config[1]);

                // 密码
                if(defined('CACHE_REDIS_AUTH') && constant('CACHE_REDIS_AUTH')){
                    self::$_cacheObj->auth(CACHE_REDIS_AUTH);
                }

                //Specify a database
                if(isset($config[2]) && $config[2] >= 0){
                    self::$_cacheObj->select($config[2]);
                }


            }else{
                trigger_error('can\'t load CACHE_REDIS_CONFIG, please check it', E_USER_ERROR);
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
	    $result = json_decode($result, true);
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
	$value = json_encode($value);
        return self::$_cacheObj->setex($key, $ttl, $value);
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {
        //$status = self::$_cacheObj->info();
        //$return['缓存获取'] = $status['cmd_get'];
        //$return['缓存存储'] = $status['cmd_set'];
        //$return['可使用缓存'] = $status['limit_maxbytes'];
        $return = array();
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
        return self::$_cacheObj->incr($key, $offset);
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        return self::$_cacheObj->decr($key, $offset);
    }//End Function


    /**
     * 初始化自增ID值
     * 
     * @return void
     * @author 
     */
    public function setcr($key, $value, $ttl=1)
    {
        return self::$_cacheObj->setex($key, $ttl, $value);
    }
}//End Class

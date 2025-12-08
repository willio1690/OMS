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
class base_kvstore_redis extends base_kvstore_abstract implements base_interface_kvstore_base, base_interface_kvstore_extension
{
    static private $_cacheObj;

    function __construct($prefix)
    {
        $this->connect();
        $this->prefix = $prefix;
    }//End Function

    /**
     * connect
     * @return mixed 返回值
     */
    public function connect()
    {
        if(!isset(self::$_cacheObj))
        {
            if(defined('KVSTORE_REDIS_CONFIG') && constant('KVSTORE_REDIS_CONFIG')){
                self::$_cacheObj = new Redis();
                $config = explode(':', KVSTORE_REDIS_CONFIG);
                self::$_cacheObj->connect($config[0], $config[1]);
                // 密码
                if(defined('KVSTORE_REDIS_AUTH') && constant('KVSTORE_REDIS_AUTH')){
                    self::$_cacheObj->auth(KVSTORE_REDIS_AUTH);
                }

                //Specify a database
                if(isset($config[2]) && $config[2] >= 0){
                    self::$_cacheObj->select($config[2]);
                }


            } else {
                trigger_error('Can\'t load KVSTORE_REDIS_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }//End Function

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $timeout_version timeout_version
     * @return mixed 返回值
     */
    public function fetch($key, &$value, $timeout_version=null)
    {
        $store = self::$_cacheObj->get($this->create_key($key));
        $store = json_decode($store,true);
        if($store !== false)
        {
            if($timeout_version < $store['dateline'])
            {
                if($store['ttl'] > 0 && ($store['dateline']+$store['ttl']) < time()){
                    return false;
                }
                $value = $store['value'];
                return true;
            }
        }
        return false;
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
        $store['value'] = $value;
        $store['dateline'] = time();
        $store['ttl'] = $ttl;
        return self::$_cacheObj->set($this->create_key($key), json_encode($store));
    }//End Function

    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key)
    {
        return self::$_cacheObj->delete($this->create_key($key));
    }//End Function

    /**
     * recovery
     * @param mixed $record record
     * @return mixed 返回值
     */
    public function recovery($record)
    {
        $key = $record['key'];
        $store['value'] = $record['value'];
        $store['dateline'] = $record['dateline'];
        $store['ttl'] = $record['ttl'];
        return self::$_cacheObj->set($this->create_key($key), json_encode($store));
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
        return self::$_cacheObj->incr($this->create_key($key), $offset);
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        return self::$_cacheObj->decr($this->create_key($key), $offset);
    }//End Function

    /**
     * 初始化自增ID值
     * 
     * @return void
     * @author 
     */
    public function setcr($key, $value, $ttl=1)
    {
        return self::$_cacheObj->setex($this->create_key($key), $ttl, $value);
    }

}//End Class


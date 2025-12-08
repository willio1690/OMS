<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taskmgr_cache_redis extends taskmgr_cache_abstract implements taskmgr_cache_interface
{
    private static $_cacheObj = null;

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->connect();
    } //End Function

    /**
     * connect
     * @return mixed 返回值
     */
    public function connect()
    {
        if (!isset(self::$_cacheObj)) {
            if (defined('__REDIS_CONFIG') && constant('__REDIS_CONFIG')) {
                self::$_cacheObj = new Redis;
                $config          = explode(':', __REDIS_CONFIG);
                self::$_cacheObj->connect($config[0], $config[1]);

                // 密码
                if (defined('__REDIS_AUTH') && constant('__REDIS_AUTH')) {
                    self::$_cacheObj->auth(__REDIS_AUTH);
                }

                //Specify a database
                if (isset($config[2]) && $config[2] >= 0) {
                    self::$_cacheObj->select($config[2]);
                }

            } else {
                trigger_error('can\'t load __REDIS_CONFIG, please check it', E_USER_ERROR);
            }
        }
    } //End Function

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function fetch($key, &$result)
    {
        $key = $this->create_key($key);

        $result = self::$_cacheObj->get($key);

        if ($result === false) {

            return false;
        } else {

            $result = is_numeric($result) ? $result : base64_decode($result);

            return true;
        }
    } //End Function

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl = 0)
    {
        $key = $this->create_key($key);


        $value = is_numeric($value) ? $value : base64_encode($value);

        return self::$_cacheObj->setex($key, $ttl, $value);
    } //End Function

    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key)
    {
        $key = $this->create_key($key);

        return self::$_cacheObj->delete($key);
    }

    /**
     * 累加
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->create_key($key);

        return self::$_cacheObj->incr($key, $offset);
    } //End Function

} //End Class

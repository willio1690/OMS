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
class base_cache_wincache extends base_cache_abstract implements base_interface_cache 
{

    function __construct() 
    {
        $this->check_vary_list();
    }//End Function

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function fetch($key, &$result) 
    {
        $result = wincache_ucache_get($key, $return);
        return $return;
    }//End Function

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl) 
    {
        return wincache_ucache_set($key, $value, $ttl);
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {
        $status = wincache_ucache_info(true);
        $return['缓存命中'] = $status['total_hit_count'];
        $return['缓存未命中'] = $status['total_miss_count'];
        return $return;
    }//End Function

}//End Class
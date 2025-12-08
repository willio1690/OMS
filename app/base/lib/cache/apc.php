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
class base_cache_apc extends base_cache_abstract implements base_interface_cache 
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
        $result = apc_fetch($key);
        return $result !== false;
    }//End Function

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl = 0) 
    {
        return apc_store($key, $value, $ttl);
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {
        $minfo = apc_sma_info();
        $cinfo = apc_cache_info('user');
        foreach($minfo['block_lists'] as $c){
            $blocks[] = count($c);
        }

        $return['缓存命中'] = $cinfo['num_hits'];
        $return['缓存未命中'] = $cinfo['num_misses'];
        $return['可用内存'] = $minfo['avail_mem'];
        return $return;
    }//End Function

}//End Class
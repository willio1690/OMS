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
class base_cache_xcache extends base_cache_abstract implements base_interface_cache 
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
        if(xcache_isset($key)){
            $result = xcache_get($key);
            return true;
        }else{
            return false;
        }
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
        return xcache_set($key, $value, $ttl);
    }//End Function

}//End Class
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
 * 仅支持0.9.5.3之前的eaccelerator
 */

class base_cache_eaccelerator extends base_cache_abstract implements base_interface_cache 
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
        $result = eaccelerator_get($key);
        return !is_null($result);
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
        return eaccelerator_put($key, $value, $ttl);
    }//End Function

}//End Class
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
class base_cache_nocache extends base_cache_abstract implements base_interface_cache
{
    public $name = '不使用cache';

    function __construct() 
    {

    }//End Function

    /**
     * 获取_modified
     * @param mixed $type type
     * @param mixed $key key
     * @return mixed 返回结果
     */
    public function get_modified($type, $key) 
    {
        return false;
    }//End Function

    /**
     * 设置_modified
     * @param mixed $type type
     * @param mixed $key key
     * @param mixed $time time
     * @return mixed 返回操作结果
     */
    public function set_modified($type, $key, $time=null) 
    {
        return false;
    }//End Function

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function fetch($key, &$result) 
    {
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
        return false;
    }//End Function
    
}//End Class

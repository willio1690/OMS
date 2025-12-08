<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface base_interface_cache{
    
    public function store($key, $value);
    public function fetch($key, &$result);
    public function set_modified($type, $key, $time=null);
    public function get_modified($type, $key);
}

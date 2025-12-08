<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * @package base
 * @copyright Copyright (c) 2011, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 */
class base_kvstore_ecae extends base_kvstore_abstract implements base_interface_kvstore_base 
{
    function __construct($prefix) 
    {
        $this->prefix = $prefix;
    }//End Function

    /**
     * 创建_key
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function create_key($key) 
    {
        if(isset($key[201])){
            return parent::create_key($key);
        }
        return sprintf("%s/%s/%s", base_kvstore::kvprefix(), $this->prefix, $key);
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
        $data['key'] = $key;
        $data['prefix'] = $this->prefix;
        $data['value'] = $value;
        $data['ttl'] = $ttl;
        $data['dateline'] = time();
        return ecae_kvstore_write($this->create_key($key), $data);
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
        if(ecae_kvstore_read($this->create_key($key), $data)){
            if($timeout_version < $data['dateline']){
                if($data['ttl'] > 0 && ($data['dateline'] + $data['ttl']) < time()){
                    return false;
                }
                $value = $data['value'];
                return true;
            }
        }
        return false;
    }//End Function

    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key) 
    {
        return ecae_kvstore_delete($this->create_key($key));
    }//End Function

    /**
     * recovery
     * @param mixed $record record
     * @return mixed 返回值
     */
    public function recovery($record) 
    {
        $key = $record['key'];
        $data['key'] = $key;
        $data['prefix'] = $this->prefix;
        $data['value'] = $record['value'];
        $data['ttl'] = $record['ttl'];
        $data['dateline'] = $record['dateline'];
        return ecae_kvstore_write($this->create_key($key), $data);
    }//End Function

}//End Class
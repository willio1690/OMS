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
class base_kvstore_dba extends base_kvstore_abstract implements base_interface_kvstore_base
{
    private $rs = null;
    private $handle = 'db4';

    function __construct($prefix) 
    {
        if(!is_dir(DATA_DIR.'/kvstore/')){
            utils::mkdir_p(DATA_DIR.'/kvstore/');
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
        $rs = dba_open(DATA_DIR.'/kvstore/dba.db','r-',$this->handle);
        $store = dba_fetch($this->create_key($key),$rs);
        dba_close($rs);
        $store = unserialize($store);
        if($store !== false && $timeout_version < $store['dateline']){
            if($store['ttl'] > 0 && ($store['dateline']+$store['ttl']) < time()){
                return false;
            }
            $value = $store['value'];
            return true;
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
        $rs = dba_open(DATA_DIR.'/kvstore/dba.db','cl',$this->handle);
        $ret = dba_replace($this->create_key($key), serialize($store), $rs);
        dba_close($rs);
        return $ret;
    }//End Function

    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key) 
    {
        $rs = dba_open(DATA_DIR.'/kvstore/dba.db','wl',$this->handle);
        $ret = dba_delete($this->create_key($key),$rs);
        dba_close($rs);
        return $ret;
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
        $rs = dba_open(DATA_DIR.'/kvstore/dba.db','cl',$this->handle);
        $ret = dba_replace($this->create_key($key), serialize($store), $rs);
        dba_close($rs);
        return $ret;
    }//End Function

}//End Class

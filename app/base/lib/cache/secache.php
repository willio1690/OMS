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
class base_cache_secache extends base_cache_secache_model implements base_interface_cache
{

    function __construct() 
    {
        $workat = DATA_DIR . '/cache';
        if(!is_dir($workat))    utils::mkdir_p($workat);        
        $this->workat($workat . '/secache');
        $this->check_vary_list();
    }//End Function

    /**
     * status
     * @param mixed $curBytes curBytes
     * @param mixed $totalBytes totalBytes
     * @return mixed 返回值
     */
    public function status(&$curBytes,&$totalBytes) 
    {
        $data = parent::status($curBytes, $totalBytes);
        foreach($data AS $val){
            $status[$val['name']] = $val['value'];
        }
        //$status[app::get('base')->_('已使用缓存')] = $cur;
        $status['可使用缓存'] = $totalBytes;
        return $status;
    }//End Function
    
}//End Class

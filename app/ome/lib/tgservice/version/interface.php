<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface ome_tgservice_version_interface{
    
    public function install($params = array(),&$sass_params = array(),&$msg);

    public function update($params = array(),&$sass_params = array(),&$msg);
    
    public function main($operation = 'install',$params = array(),&$msg = '',$obj);

    public function callback_tosass($data = array());
}
<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_view_helper{

    function __construct($app){
        $this->app = $app;
    }
    function modifier_barcode($data){
        return kernel::single('eccommon_barcode')->get($data);
    }
}

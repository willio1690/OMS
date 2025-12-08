<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_service_shop_xhs extends inventorydepth_service_shop_common
{
    public $customLimit = 10; 
    
    function __construct(&$app)
    {
        $this->app = $app;
    }

   
   
}

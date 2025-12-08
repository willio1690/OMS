<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_shop_items extends desktop_controller {

    var $name = '店铺商品';
    var $workground = 'tbo2o_center';

    function index()
    {
        $base_filter = array();
        $params = array(
                'title'=>'店铺商品',
                'base_filter' => $base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
        );
        
        $this->finder('tbo2o_mdl_shop_items', $params);
    }
}
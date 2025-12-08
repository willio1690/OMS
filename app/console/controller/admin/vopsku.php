<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT供货价
 * 
 * @access public
 * @author chenping
 * @version 1.0 vopurchase.php 2017-02-23
 */
class console_ctl_admin_vopsku extends desktop_controller{
    
    var $workground = "console_purchasecenter";
    
    
    function index()
    {
        $this->title = '采购单供货价';
        
        $params = array(
            'title'=>$this->title,
            'use_buildin_set_tag'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_recycle'=>false,
        );
        
        $this->finder('purchase_mdl_order_sku_price', $params);
    }
}
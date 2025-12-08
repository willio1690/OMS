<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * sunjing@shopex.cn
 */
class vop_ctl_admin_bill_detail extends desktop_controller {

    public function index() {
        $actions = array();
        $base_filter = array();
        if($_GET['bill_id']){
            $base_filter['bill_id'] = $_GET['bill_id'];
        }
        $params = array(
                'title'=>'JIT费用项',
                'base_filter' => $base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
        );
        
        $this->finder('vop_mdl_source_detail', $params);
    }


   
}
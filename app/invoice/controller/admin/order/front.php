<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @describe: 预发票列表
 * ============================
 */
class invoice_ctl_admin_order_front extends desktop_controller {
    public function index() {
        $actions = array();
        $params = array(
                'title'=>'预发票列表',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'base_filter'=>['disabled'=>'false'],
                'orderBy'=>'id desc',
        );
        
        $this->finder('invoice_mdl_order_front', $params);
    }
}
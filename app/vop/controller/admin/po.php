<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_ctl_admin_po extends desktop_controller {

    
    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $actions = array();
        
        $params = array(
                'title'=>'PO结算单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>true,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy' => 'po_id DESC',
        );
        
        $this->finder('vop_mdl_po', $params);
        
       

    }
}
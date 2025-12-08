<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_ctl_admin_reship extends desktop_controller {

    var $name = "退货单列表";
    var $workground = "console_center";


    /**
     * 
     * 拒收退货单列表
     */
    function index(){

        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>true,
            'use_buildin_filter'=>true,
            'title'=>'退货单',
        );

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $params['base_filter']['org_id'] = $organization_permissions;
        }

        $this->finder ( 'ome_mdl_reship' , $params );
    }

   
}

<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class eccommon_ctl_platform_address extends desktop_controller{

   
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $actions = array();
        
        $params = array(
            'title'                  => '京标地址库管理',
            'actions'                => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
           
        );
        $this->finder('eccommon_mdl_platform_address', $params);
    }

    
   
}

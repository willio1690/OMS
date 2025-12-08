<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_ctl_aftersale extends desktop_controller
{
    
    
    function index() {
        
        $this->title='售后列表';
        $params = array(
            'title' => $this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
                  
        );
        $this->finder('pos_mdl_aftersale', $params);
    }
}
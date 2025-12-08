<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_product_storagelife_history extends desktop_controller{

    var $name = "保质期批次历史";

    var $workground = "aftersale_center";

    function index(){
       $params = array(
            'title'=>'保质期批次历史',
            'actions'=>array(),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_buildin_filter' => true,
            'base_filter' => $filter,
         );
       $this->finder('ome_mdl_product_storagelife_history',$params);
    }
}
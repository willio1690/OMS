<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_shop extends desktop_controller {

    var $name = "线下店铺管理";
    var $workground = "o2o_center";

    function index() {

        $this->finder('o2o_mdl_shop', array(
            'title' => '线下店铺管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter'=>true,
        ));
    }

}
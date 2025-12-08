<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_branch extends desktop_controller {

    var $name = "虚拟仓管理";
    var $workground = "o2o_center";

    function index() {

        $this->finder('o2o_mdl_branch', array(
            'title' => '虚拟仓管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter'=>true,
        ));
    }

}
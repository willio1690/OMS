<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_ctl_region extends desktop_controller{

    var $workground = 'desktop_ctl_system';

    function index(){
        $this->finder('base_mdl_regions');
    }

}

<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_ctl_default extends base_controller{
    
    function index(){
        $this->pagedata['project_name'] = '%*APP_NAME*%';
        $this->display('default.html');
    }
    
}
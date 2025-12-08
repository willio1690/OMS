<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_analysis extends desktop_controller{

    /**
     * storedaliy
     * @return mixed 返回值
     */
    public function storedaliy(){
        kernel::single('o2o_analysis_store_daliy_show')->set_params($_POST)->display();
    }

}
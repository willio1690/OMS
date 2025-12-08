<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_ctl_admin_analysis extends desktop_controller{

    /**
     * pick
     * @return mixed 返回值
     */
    public function pick(){ //捡货绩效统计
        kernel::single('tgkpi_analysis_pick')->set_params($_POST)->display();
    }

    /**
     * 检查
     * @return mixed 返回验证结果
     */
    public function check(){
        kernel::single('tgkpi_analysis_check')->set_params($_POST)->display();
    }

    /**
     * reason
     * @return mixed 返回值
     */
    public function reason(){
        kernel::single('tgkpi_analysis_reason')->set_params($_POST)->display();
    }
    #发货统计
    /**
     * delivery
     * @return mixed 返回值
     */
    public function delivery(){
        kernel::single('tgkpi_analysis_delivery')->set_params($_POST)->display();
    }

}
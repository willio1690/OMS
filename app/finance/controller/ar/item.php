<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_ar_item extends desktop_controller{
	var $name = "销售到账明细";
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        /*
       if(empty($_POST)){
            $_POST['time_from'] = date("Y-n-d");
            $_POST['time_to'] = date("Y-n-d");
            $_POST['shop_id'] = 0;
        }else{
            $_POST['time_from'] = $_POST['time_from'];
            $_POST['time_to'] = $_POST['time_to'];
        }
        */
        kernel::single('finance_ar_item')->set_params($_POST)->display();
    }


}
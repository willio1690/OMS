<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_serial extends desktop_controller{

    function ajaxCheckSerial(){
        $serialObj = $this->app->model('product_serial');
        $filter['serial_number'] = $_POST['serial'];
        $filter['bn'] = $_POST['bn'];
        $filter['branch_id'] = $_POST['bh_id'];
        $serialData = $serialObj->dump($filter);
        if($serialData['serial_id'] > 0 && $serialData['status'] == 0){
            echo json_encode(array('result' => 'true', 'msg'=>'OK'));
        }else{
            echo json_encode(array('result' => 'false', 'msg'=>'唯一码不存在或当前状态不可用'));
        }
    }

}
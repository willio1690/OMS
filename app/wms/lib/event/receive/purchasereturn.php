<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_receive_purchasereturn extends wms_event_response{

     /**
     * 采购退货通知单创建事件
     * @param array $data
     */
    public function create($data){
        //error_log('purchasereturn:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    /**
     * 采购退货通知单状态变更事件
     * @param array $data
     */
    public function updateStatus($data){
        return $this->send_succ();
    }
}

?>

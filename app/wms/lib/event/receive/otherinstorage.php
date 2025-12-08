<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_receive_otherinstorage extends wms_event_response{

    /**
     * 其他入库操作后变更其他入单状态
     */
    public function setStatus(){

    }

    public function create($data){
        #error_log('other:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    public function updateStatus($data){
        return $this->send_succ();
    }
}

?>

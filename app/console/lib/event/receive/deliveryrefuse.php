<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_event_receive_deliveryrefuse extends wms_event_response{

    /**
     * 
     * 拒绝事件处理
     * @param array
     */
    public function updateStatus($data){
        
        if ($data['io_source'] != 'selfwms'){
            return $this->send_succ();
        }else{

            return $this->send_succ();
        }
    }

}
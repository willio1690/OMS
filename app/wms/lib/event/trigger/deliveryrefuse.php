<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_trigger_deliveryrefuse{

    /**
     * 拒收通知前台
     */
    public function updateStatus($wms_id, $data, $sync = false){
       
        //kernel::single('middleware_wms_response', $wms_id)->reship_result($data, $sync);
        $result = kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.reship.status_update')->dispatch($data);
    }
}

?>

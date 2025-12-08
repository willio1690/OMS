<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_trigger_inventory{

    /**
     * 请求控制台盘盈事件
     */
    public function overage(){
    
    }

    /**
     * 请求控制台盘亏事件
     */
    public function shortage(){

    }

    /***
    * 盘点申请单
    * 
    */
    public function apply($wms_id, $data, $sync = false) {
       
        //$result = kernel::single('middleware_wms_response', $wms_id)->inventory_result($data, $sync);
        $result = kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.inventory.add')->dispatch($data);
        return $result;
    }
}

?>

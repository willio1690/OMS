<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class edi_event_trigger_shippackage {

   
    /**
     * 添加
     * @param mixed $shippackage_id ID
     * @return mixed 返回值
     */
    public function add($shippackage_id) {

        $shippackageMdl = app::get('edi')->model('shippackage');

        $shippackage = $shippackageMdl->db_dump(array('shippackage_id'=>$shippackage_id),'*');

        $data = $this->formatShippackageData($shippackage);

        if(empty($data)) return false;
        $data['method'] = 'openapi.request.shippackage.add';


        $rs= kernel::single('erpapi_router_response')->dispatch($request_id, $data);
     
        $updata = array('sync_status'=>'2');
        if($rs['rsp'] != 'succ'){
            $updata['sync_status'] = '3';
            $updata['sync_msg'] = $rs['msg'];
        }

        $filter = array('shippackage_id'=>$shippackage_id);


        $shippackageMdl->update($updata,$filter);
      
    }


    /**
     * formatShippackageData
     * @param mixed $shippackage shippackage
     * @return mixed 返回值
     */
    public function formatShippackageData($shippackage){

        $shippackage_id = $shippackage['shippackage_id'];

        $itemsMdl = app::get('edi')->model('shippackage_items');
        $items = $itemsMdl->getlist('*',array('shippackage_id'=>$shippackage_id));
        if(empty($items)) return array();
        $data = $shippackage;
        $data['items'] = json_encode($items);

        return $data;
    
   }
    
}
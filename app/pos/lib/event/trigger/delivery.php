<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_delivery
{

    /**
     * delivery_add
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function delivery_add($delivery_id){
        $db = kernel::database();
        $deorders = $db->selectrow("SELECT od.order_id FROM sdb_ome_delivery as d LEFT JOIN sdb_ome_delivery_order as od ON d.delivery_id=od.delivery_id WHERE d.delivery_id=".$delivery_id." AND d.parent_id=0 AND d.status='succ'");
        if(!$deorders) return true;
        $order_id = $deorders['order_id'];
        $orderMdl = app::get('ome')->model('orders');
        $orders = $orderMdl->db_dump(array('order_id'=>$order_id),'createway,shop_type');
        if($orders['shop_type'] == 'pekon' && $orders['createway'] == 'after'){
            $deliverys = kernel::single('ome_event_data_delivery')->generate($delivery_id);

            $product_serial = $this->getSerials($delivery_id);
            if($product_serial){
                foreach($deliverys['delivery_items'] as $k=>$v){
                    if ($product_serial[$v['bn']]){
                        $uniqueCodes = array_splice($product_serial[$v['bn']], 0, $v['number']);
                        $deliverys['delivery_items'][$k]['uniqueCodes'] = $uniqueCodes;
                    }
               
                }
            }
        
            $branch_id = $deliverys['branch_id'];
            $branchs = $db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND b_type=1");
            if(!$branchs) return true;
            $order_bn = $deliverys['order_bn'];
            $orders = $db->selectrow("SELECT order_id FROM sdb_ome_orders where order_bn='".$order_bn."'");

            $order_id = $orders['order_id'];
            $reships = $db->selectrow("SELECT r.reship_id FROM sdb_ome_reship as r  WHERE r.source='local' AND r.return_type='change' AND r.change_order_id=".$order_id."");

            if(!$reships) return true;
            $shop_id = $deliverys['shop_id'];
            $storeMdl = app::get('o2o')->model('store');
            $stores = $storeMdl->db_dump(array('shop_id'=>$shop_id),'store_id');
            $store_id = $stores['store_id'];
            $channel_type = 'store';
            $channel_id = $store_id;
            $dlyTriggerLib = kernel::single('ome_event_trigger_delivery');
            $result = $dlyTriggerLib->create($channel_type,$channel_id,$deliverys,false);
            if($result['rsp'] == 'fail'){
                $msg = "失败. 原因:".$result['msg'];
            }else{
                $msg ="成功.";

               
            }
            $logMdl = app::get('ome')->model('operation_log');
            $logMdl->write_log('delivery_modify@ome',$delivery_id,"换出订单推送pos销售单结果:".$msg,NULL,$opInfo);

        }
        
    }

    function getSerials($delivery_id)
    {
        $product_serial = [];
        
        $serialMdl    = app::get('ome')->model('product_serial_history');
        $rows = $serialMdl->getList('bn,serial_number', array('bill_id'=>$delivery_id,'bill_type'=>1), 0, -1);

        foreach ($rows as $row) {
            $product_serial[$row['bn']][] = $row['serial_number'];
        }

        return $product_serial;
    }
  
}

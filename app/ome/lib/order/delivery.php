<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_order_delivery{

    /**
     *
     * according to the order_id, find the related delivery
     * @param Int $order_id 
     */
    function getDlyIdsByOrdId($order_id, $status = 'succ'){
        $dlyOrderObj  = app::get('ome')->model('delivery_order');
        $delivery_ids = $dlyOrderObj->db->select("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status ='{$status}'");
        $ids = array();
        if($delivery_ids){
            foreach($delivery_ids as $v){
                $ids[] = $v['delivery_id'];
            }
        }

        return $ids;
    }
}
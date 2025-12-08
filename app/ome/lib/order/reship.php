<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_order_reship{

    /**
     *
     * according to the order_id, find the related reship
     * @param Int $order_id 
     */
    function getReshipIdsByOrdId($order_id, $status = 'succ'){
        $reshipObj  = app::get('ome')->model('reship');
        $reship_ids = $reshipObj->getList('reship_id', array('order_id'=>$order_id, 'status'=>$status), 0, -1);
        $ids = array();
        if($reship_ids){
            foreach($reship_ids as $v){
                $ids[] = $v['reship_id'];
            }
        }

        return $ids;
    }
}
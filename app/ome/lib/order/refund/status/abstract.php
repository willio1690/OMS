<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class ome_order_refund_status_abstract {

    protected function getKey($tid, $nodeId) {
        return 'tmc_RefundCreated_'.$nodeId.'_'.$tid;
    }
    
    abstract function fetch($tid, $nodeId, $shopId);

    abstract public function store($sdf);
}
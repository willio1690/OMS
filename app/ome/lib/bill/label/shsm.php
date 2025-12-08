<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#送货上门

class ome_bill_label_shsm {

    /**
     * @param int $orderId
     * @return bool
     */
    public function isTinyPieces($orderId) {
        $billLabelmdl = app::get('ome')->model('bill_label');
        $filter = [
            'bill_type' => 'order',
            'bill_id'   => $orderId,
        ];
        $orderLabelInfo = $billLabelmdl->getList('label_name, label_value', $filter);
        if (!$orderLabelInfo) {
            return false;
        }

        foreach($orderLabelInfo as $v) {
            if( $v['label_name'] == '送货上门'
                && ($v['label_value'] & 0x0002)) {
                return true;
            }
        }
        return false;
    }
}
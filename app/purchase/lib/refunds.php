<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 采购退款单
 */

class purchase_refunds{

    function save_refunds($data, &$msg){
        $refundObj = app::get('purchase')->model('purchase_refunds');

         return  $refundObj->createRefund($data);

    }
}

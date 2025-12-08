<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_invoice_request_heartbeat extends erpapi_invoice_request_abstract
{
    /**
     * confirm
     * @return mixed 返回值
     */
    public function confirm()
    {
        $result = $this->__caller->call(INVOICE_HEARTBEAT, [], [], '开票心跳检查', 30, 'heartbeat');


        return $result;
    }

}
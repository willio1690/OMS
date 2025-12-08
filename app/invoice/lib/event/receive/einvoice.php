<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票接受外部app的处理类
 */
class invoice_event_receive_einvoice
{
    /**
     * 新增发票
     * 
     * @param array $params
     * @param string $type
     */
    public function create_invoice_order($params, $type='order_create')
    {
        return kernel::single('invoice_process')->create($params, $type);
    }
    
    /**
     * 作废发票
     *
     * @param array $params
     * @param string $type
     */
    public function cancel_invoce_order($params, $type='order_cancel')
    {
        kernel::single('invoice_process')->cancel($params, $type);
    }
}
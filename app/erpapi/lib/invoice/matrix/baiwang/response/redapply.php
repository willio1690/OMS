<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票-红字申请单接口响应类
 *
 */
class erpapi_invoice_matrix_baiwang_response_redapply extends erpapi_invoice_response_redapply
{
    protected function _formatUpdateParams($params)
    {
        $invoice = $params['invoice'];
        $data    = $params['data'];

        $response = $data['response'][0];

        $updateData = [
            'invoice_apply_bn' => $invoice['invoice_apply_bn'],
            'status'           => (int)$response['confirmState'],// 状态匹配
            'id'               => $invoice['id'],
            'item_id'          => $invoice['order_electronic_items']['item_id'],
            'red_confirm_uuid' => $response['redConfirmUuid'],
            'red_confirm_no'   => $response['redConfirmNo'],
            'channel_id'       => $invoice['channel_id'],
            'shop_id'          => $invoice['shop_id'],
            'red_invoice_no'   => $response['redInvoiceNo'],
        ];

        return $updateData;
    }
}

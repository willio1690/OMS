<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发票-红字申请单接口响应类
 *
 * @author xiayuanjun<xiayuanjun@shopex.cn>
 * @version 0.1
 */
class erpapi_invoice_response_redapply extends erpapi_invoice_response_abstract
{

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        $invoice = $params['invoice'];

        $this->__apilog['title']       = '红字申请单更新-' . $invoice['invoice_apply_bn'];
        $this->__apilog['original_bn'] = $invoice['invoice_apply_bn'] ?: $invoice['order_bn'];

        // 参数校验
        $checkRs = $this->_checkUpdateParams($params);

        if (!$checkRs) {
            return false;
        }

        // 参数格式化
        $data = $this->_formatUpdateParams($params);

        return $data;
    }

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
        ];

        return $updateData;
    }

    protected function _checkUpdateParams($params)
    {
        if (!is_array($params) && !isset($params['response'])) {
            return false;
        }
        return true;
    }
}

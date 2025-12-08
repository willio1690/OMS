<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发票信息回传
 *
 */
class erpapi_invoice_response_process_order
{

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        $row = current($params);
        // 单独实例,避免缓存
        $constructParams = [
            'invoice_apply_bn' => $row['invoice_apply_bn'],
        ];

        $result = kernel::single('invoice_event_receive_invoice', $constructParams)->update($params);

        return $result;
    }

}

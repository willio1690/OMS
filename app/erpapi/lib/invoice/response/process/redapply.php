<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发票冲红状态处理
 *
 */
class erpapi_invoice_response_process_redapply
{

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        // 单独实例,避免缓存
        $constructParams = [
            'invoice_apply_bn' => $params['invoice_apply_bn'],
        ];
        $result          = kernel::single('invoice_event_receive_redapply', $constructParams)->update($params);
        return $result;
    }

}

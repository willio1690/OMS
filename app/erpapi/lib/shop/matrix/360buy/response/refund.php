<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc    京东退款单数据处理
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_360buy_response_refund extends erpapi_shop_response_refund
{
    protected function _formatAddParmas($params)
    {
        $this->__apilog['result']['msg'] = '京东退款单不走此接口';
        return array();
    }
}

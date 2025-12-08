<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单催发货
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_matrix_tmall_response_delivergoods extends erpapi_shop_response_delivergoods
{
    /**
     * 格式化参数
     * 
     * @param array $params
     * @return array:
     */

    protected function _returnParams($params) {
        $sdf = $this->_formatParams($params);
        
        return $sdf;
    }
}

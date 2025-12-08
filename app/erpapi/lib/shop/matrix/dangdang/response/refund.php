<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_dangdang_response_refund extends erpapi_shop_response_refund {

    protected function _formatAddParams($params) {
        $this->__apilog['result']['msg'] = '当当退款单不走此接口';
        return array();
    }
}
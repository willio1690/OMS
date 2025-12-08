<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/5/30
 * @describe 淘宝退款单数据处理（已弃用）
 */

class erpapi_shop_matrix_taobao_response_refund extends erpapi_shop_response_refund {

    protected function _formatAddParams($params) {
        $this->__apilog['result']['msg'] = '淘宝退款单不走此接口';
        return array();
    }
}
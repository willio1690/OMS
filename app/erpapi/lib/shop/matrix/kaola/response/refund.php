<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author 20180904 by wangjianjun
 * @describe 售后数据转换
 */
class erpapi_shop_matrix_kaola_response_refund extends erpapi_shop_response_refund {

    protected function _formatAddParams($params) {
        $this->__apilog['result']['msg'] = '考拉退款单不走此接口';
        return array();
    }
    
}
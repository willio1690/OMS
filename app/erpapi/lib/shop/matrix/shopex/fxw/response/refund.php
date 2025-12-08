<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_matrix_shopex_fxw_response_refund extends erpapi_shop_matrix_shopex_response_refund {

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $sdf['update_order_payed'] = true;
        return $sdf;
    }
}
<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecstore_response_refund extends erpapi_shop_matrix_shopex_response_refund {

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);

        return $sdf;
    }
}
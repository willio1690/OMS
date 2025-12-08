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
class erpapi_shop_matrix_shopex_response_refund extends erpapi_shop_response_refund {

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $sdf['t_ready']    = $params['t_ready'];
        $sdf['t_sent']     = $params['modified'];
        $sdf['t_received'] = '';    // 如果是c2c订单不设用户收款时间
        return $sdf;
    }
}
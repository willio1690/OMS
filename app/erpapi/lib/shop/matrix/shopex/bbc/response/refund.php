<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc 退款单数据处理
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_matrix_shopex_bbc_response_refund extends erpapi_shop_matrix_shopex_response_refund {

    protected function _formatAddParams($params) {

        $field = 'ship_status';

        $tgOrder = $this->getOrder($field, $this->__channelObj->channel['shop_id'], $params['order_bn']);
        if ($tgOrder['ship_status'] == '1') {
            return array();
            exit;
        }
        $sdf = parent::_formatAddParams($params);
        $sdf['cod_zero_accept'] = true;
        return $sdf;
    }

}
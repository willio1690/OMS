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
class erpapi_shop_matrix_yihaodian_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $yhdSdf = array(
            'sendbackaddress'=> $params['receiver_address'],
            'receive_state'=> $params['good_status'],
        );
        return array_merge($sdf, $yhdSdf);
    }

    protected function _getAddType($sdf) {
        return 'returnProduct';
    }

    protected function _formatAddItemList($sdf, $convert = array()) {
        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'oid',
            'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

    protected function _returnProductAdditional($sdf) {
        $ret = array(
            'model' => 'return_product_yihaodian',
            'data' => array(
                'shop_id'         => $sdf['shop_id'],
                'sendbackaddress'=> $sdf['sendbackaddress'],
                'receive_state'=> $sdf['receive_state'],
            )
        );
        return $ret;
    }
}
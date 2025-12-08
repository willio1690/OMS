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
class erpapi_shop_matrix_zhe800_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _getAddType($sdf) {
        if ($sdf['has_good_return'] == '1') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'],array('0'))) {
                #有退货，未发货的,做退款
                return 'refund';
            }else{
                #有退货，已发货的,做售后
                return 'returnProduct';
            }
        }else{
            #无退货的，直接退款
            return 'refund';
        }
    }

    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'oid',
            'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

}
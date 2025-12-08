<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc beibei售后数据转换
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrxi_beibei_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{
    protected function _getAddType($sdf) {
        if(in_array($sdf['order']['ship_status'],array('0'))) { #退款
            return 'refund';
        } else { #退货
            return 'returnProduct';
        }
    }

    protected function _formatAddItemList($sdf, $convert = array()) {
        $convert = array(
            'sdf_field'=>'item_id',
            'order_field'=>'shop_goods_id',
            'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

}
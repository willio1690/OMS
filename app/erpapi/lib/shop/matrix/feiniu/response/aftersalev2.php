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
class erpapi_shop_matrix_feiniu_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _getAddType($sdf) {
        if(in_array($sdf['order']['ship_status'],array('0'))) { #退款
            return 'refund';
        } else { #退货
            $this->__apilog['result']['msg'] = '创建退款单失败:只接受未发货的售前退款';
            return false;
        }
    }

    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = array(
            'sdf_field'=>'item_id',
            'order_field'=>'oid',
            'default_field'=>'item_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

}
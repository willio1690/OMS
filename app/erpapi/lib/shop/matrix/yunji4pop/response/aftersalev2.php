<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/3/3
 */

class erpapi_shop_matrix_yunji4pop_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{
    
    protected function _getAddType($sdf)
    {
        if ($sdf['refund_type'] == 'returnProduct') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'], array('0'))) {
                #有退货，未发货的,做退款
                return 'refund';
            } else {
                #有退货，已发货的,做售后
                return 'returnProduct';
            }
        } elseif ($sdf['refund_type'] == 'bufa') {
            $this->__apilog['result']['msg'] = '补发类型售后不接收';
            return '';
        } else {
            #无退货的，直接退款
            return 'refund';
        }
    }
    
    protected function _formatAddItemList($sdf, $convert = array())
    {
        $convert = array(
            'sdf_field'     => 'oid',
            'order_field'   => 'oid',
            'default_field' => 'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }
    
}
<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁自营售后aftersale
 * Class erpapi_shop_matrix_suning4zy_response_aftersalev2
 */
class erpapi_shop_matrix_suning4zy_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{

    /**
     * @param $params
     * @return array
     */
    protected function _formatAddParams($params)
    {
        $sdf = parent::_formatAddParams($params);
        $suningSdf = array(
            'oid' => $params['oid'],
            'cs_status' => $params['cs_status'],
            'seller_nick' => $params['seller_nick'],
            'payment_id' => $params['payment_id'],
        );
        return array_merge($sdf, $suningSdf);
    }

    /**
     * 售后类型
     * @param $sdf
     * @return string
     */
    protected function _getAddType($sdf)
    {
        if ($sdf['has_good_return'] == 'true') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'], array('0'))) {
                #有退货，未发货的,做退款
                return 'refund';
            } else {
                #有退货，已发货的,做售后
                return 'returnProduct';
            }
        } else {
            #无退货的，直接退款
            return 'refund';
        }
    }

    /**
     * @param array $sdf
     * @param array $convert
     * @return array
     */
    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'oid',
            'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

}
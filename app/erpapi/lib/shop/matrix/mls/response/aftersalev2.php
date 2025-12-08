<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/8/25
 */
class erpapi_shop_matrix_mls_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{
    /**
     * @param $sdf
     * @return string
     */
    protected function _getAddType($sdf)
    {
        return ''; //暂未使用 因为美丽说在内测中
        if ($sdf['has_good_return'] == '1') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'],array('0'))) {

                //有退货，未发货的,做退款
                return 'refund';
            }else{

                //有退货，已发货的,做售后
                return 'returnProduct';
            }
        }else{

            //无退货的，直接退款
            return 'refund';
        }
    }

    /**
     * @param array $sdf
     * @param array $convert 例 array('sdf_field'=>'item_id','order_field'=>'shop_goods_id','default_field'=>'outer_id');
     * @return array 返回 以bn作主键的数组 捆绑商品使用捆绑商品的bn
     */
    protected function _formatAddItemList($sdf, $convert=array())
    {
        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'oid',
            'default_field'=>'outer_id'
        );

        return parent::_formatAddItemList($sdf, $convert);
    }
}
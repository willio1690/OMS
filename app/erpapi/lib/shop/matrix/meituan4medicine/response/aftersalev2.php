<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing@shopex.cn
 * @describe
 */

class erpapi_shop_matrix_meituan4medicine_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{
    protected function _formatAddParams($params)
    {
        $sdf = parent::_formatAddParams($params);
        if (in_array($sdf['refund_type'], array('refund', 'apply'))) {
            //售前售后报错退款明细
            if (empty($sdf['refund_item_list']) || empty($sdf['refund_item_list']['return_item'])) {
                $sdf['refund_item_list'] = array(1);
            }
        }
        return $sdf;
    }
    
    protected function _getAddType($sdf)
    {
        //需要退货才更新为售后单
        if ($sdf['has_good_return'] == 'true') {
            if (in_array($sdf['order']['ship_status'],array('0'))) {
                //有退货，未发货的,做退款
                return 'refund';
            } else{
                //有退货，已发货的,做售后
                return 'returnProduct';
            }
        }else{
            //无退货的，直接退款
            return 'refund';
        }
    }
    
    protected function _formatAddItemList($sdf, $convert = array())
    {
        //退款增加整单明细
        if (in_array($sdf['refund_type'], array('refund', 'apply')) && !empty($sdf['refund_item_list']) && count($sdf['refund_item_list'],1) == 1) {
            //退款明细
                $orderObj = app::get('ome')->model('order_objects')->getList('bn,name,quantity as nums,price,oid',
                    array('order_id' => $sdf['order']['order_id']));
                if ($orderObj) {
                    $arrProduct = array();
                    foreach ($orderObj as $val) {
                        $arrProduct[$val['bn']] = array(
                            'bn'       => $val['bn'],
                            'name'     => $val['name'],
                            'num'      => $val['nums'],
                            'price'    => $val['price'],
                            'oid'      => $val['oid'],
                            'modified' => time(),
                        );
                    }
                    return $arrProduct;
                }
        } else {
            $convert = array(
                'sdf_field'     => 'oid',
                'order_field'   => 'oid',
                'default_field' => 'outer_id'
            );
            return parent::_formatAddItemList($sdf, $convert);
        }
    }
}
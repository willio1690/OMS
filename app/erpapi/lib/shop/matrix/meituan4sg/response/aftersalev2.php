<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing@shopex.cn
 * @describe
 */

class erpapi_shop_matrix_meituan4sg_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{
    protected function _formatAddParams($params)
    {
        $sdf = parent::_formatAddParams($params);
        $sdf['source_refund_type'] = $params['source_refund_type'];
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
        if ($sdf['source_refund_type'] == 1 && empty($sdf['refund_item_list']['return_item'])) {
            //退款明细
                $orderObj = app::get('ome')->model('order_objects')->getList('obj_id,bn,name,quantity as nums,price,oid',
                    array('order_id' => $sdf['order']['order_id'], 'delete' => 'false'));
                if ($orderObj) {
                    $orderItems = app::get('ome')->model('order_items')->getList('item_id,obj_id,nums,sendnum,return_num',
                        array('obj_id' => array_column($orderObj, 'obj_id')));
                    $orderItems = array_column($orderItems, null, 'obj_id');
                    $reship = app::get('ome')->model('reship')->getList('reship_id',array('order_id'=>$sdf['order']['order_id'], 'reship_bn|noequal'=>$sdf['refund_bn'], 'is_check|notin'=>array('7','5')));
                    $unFinishedReship = [];
                    if($reship){
                        $reshipItems = app::get('ome')->model('reship_items')->getList('num,order_item_id',array('reship_id'=>array_column($reship, 'reship_id'),'return_type'=>array('return'),'order_item_id|in'=>array_column($orderItems, 'item_id')));
                        foreach($reshipItems as $reshipItem){
                            $unFinishedReship[$reshipItem['order_item_id']] += $reshipItem['num'] ?? 0;
                        }
                    }
                    $arrProduct = array();
                    foreach ($orderObj as $val) {
                        $num = ($orderItems[$val['obj_id']]['sendnum'] ? : $orderItems[$val['obj_id']]['nums']) - $orderItems[$val['obj_id']]['return_num'] - ($unFinishedReship[$orderItems[$val['obj_id']]['item_id']] ?? 0);
                        $radio = $orderItems[$val['obj_id']]['nums'] / $val['nums'];
                        $num = sprintf('%.0f', $num / $radio);
                        if($num <= 0){
                            continue;
                        }
                        $arrProduct[$val['bn']] = array(
                            'bn'       => $val['bn'],
                            'name'     => $val['name'],
                            'num'      => $num,
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
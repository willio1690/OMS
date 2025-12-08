<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc 以order_items表为基准的操作类
 * @author: jintao
 * @since: 2016/7/22
 */
class ome_order_object_item {

    /**
     * 获取NumPrice
     * @param mixed $arrOrderId ID
     * @param mixed $is_archive is_archive
     * @return mixed 返回结果
     */

    public function getNumPrice($arrOrderId,$is_archive = false) {
        $field = 'obj_id,order_id,obj_type,goods_id,bn,price,amount,quantity,pmt_price,sale_price,divide_order_fee,oid';
        //$itemField = 'item_id,order_id,obj_id,product_id,bn,cost,price,pmt_price,sale_price,amount,nums,sendnum,item_type,divide_order_fee';
        
        //@todo：之前少了delete字段,导致退货有PKG组合商品代码报错
        $itemField = '*';
        
        return $this->_getOrderObjectsItems($arrOrderId, $field, $itemField, array('delete'=>'false'), array('delete'=>'false'),$is_archive);
    }

    /**
     * batchGetObjectsItems
     * @param mixed $arrOrderId ID
     * @return mixed 返回值
     */
    public function batchGetObjectsItems($arrOrderId) {
        return $this->_getOrderObjectsItems($arrOrderId, '*', '*', array(), array(),false);
    }

    /**
     * @param $arrOrderId array orders表的主键数组
     * @param $field string order_objects表要查询的字段
     * @param $itemField string order_items表要查询的字段
     * @param $filter array order_objects表除order_id字段外的查询条件
     * @param $itemFilter array order_items表除obj_id字段外的查询条件
     * @return array array (
    170 => #orders表主键ID
    array (
    309 => #order_objects表主键obj_id
    array (
    'obj_id' => '309',
    'order_id' => '170',
    'obj_type' => 'goods',
    'obj_alias' => '商品',
    'shop_goods_id' => '223',
    'goods_id' => '2',
    'bn' => 'BN-521433731232-31',
    'name' => '测试商品',
    'price' => '12.750',
    'amount' => '12.750',
    'quantity' => '1',
    'weight' => '100.000',
    'score' => '0',
    'pmt_price' => '0.000',
    'sale_price' => '5.100',
    'oid' => '20160324096378',
    'is_oversold' => '0',
    'item_nums' => 1, #对应order_items中delete为‘false’的nums之和
    'order_items' =>
    array (
    447 => #order_items主键item_id
    array (
    'item_id' => '447',
    'order_id' => '170',
    'obj_id' => '309',
    'shop_goods_id' => '223',
    'product_id' => '3',
    'shop_product_id' => '0',
    'bn' => 'BN-521433731232-31',
    'name' => '测试商品',
    'cost' => '0.000',
    'price' => '12.750',
    'pmt_price' => '7.650',
    'sale_price' => '5.100',
    'amount' => '12.750',
    'weight' => '100.000',
    'nums' => '1',
    'sendnum' => '0',
    'addon' => 'a:1:{s:12:"product_attr";a:1:{i:0;a:3:{s:5:"value";s:12:"实色双跳";s:5:"label";s:6:"颜色";s:12:"original_str";N;}}}',
    'item_type' => 'product',
    'score' => '0',
    'sell_code' => '',
    'promotion_id' => NULL,
    'return_num' => '0',
    'delete' => 'false',
    'quantity' => '1', #等价字段nums
    ),
    ),
    ),
    ),
    )
     */
    private function _getOrderObjectsItems($arrOrderId, $field, $itemField, $filter, $itemFilter,$is_archive) {
        $returnData = array();
        $filter['order_id'] = $arrOrderId;
        if ($is_archive) {
            $objectData = app::get('archive')->model('order_objects')->getList($field, $filter);
        } else {
            $objectData = app::get('ome')->model('order_objects')->getList($field, $filter);
        }
        if(empty($objectData)) {
            return array();
        }
        $arrObjId = array();
        foreach($objectData as $oVal) {
            //若归档订单无实付金额时使用销售金额
            if ($is_archive && ($oVal['divide_order_fee'] <= 0 || empty($oVal['divide_order_fee']))) {
                $oVal['divide_order_fee'] = $oVal['sale_price'];
            }
            $oVal['item_nums'] = 0;
            $returnData[$oVal['order_id']][$oVal['obj_id']] = $oVal;
            $arrObjId[] = $oVal['obj_id'];
        }
        $itemFilter['obj_id'] = $arrObjId;
        if ($is_archive) {
            $itemData = app::get('archive')->model('order_items')->getList($itemField, $itemFilter);
        } else {
            $itemData = app::get('ome')->model('order_items')->getList($itemField, $itemFilter);
        }
        if(empty($itemData)) {
            return array();
        }
        foreach($itemData as $iVal) {
            //若归档订单无实付金额时使用销售金额
            if ($is_archive && ($iVal['divide_order_fee'] <= 0 || empty($iVal['divide_order_fee']))) {
                $iVal['divide_order_fee'] = $iVal['sale_price'];
            }
            $iVal['quantity'] = $iVal['nums'];
            if($iVal['delete'] == 'false') {
                $returnData[$iVal['order_id']][$iVal['obj_id']]['item_nums'] += $iVal['nums'];
            }
            $returnData[$iVal['order_id']][$iVal['obj_id']]['order_items'][$iVal['item_id']] = $iVal;
        }
        return $returnData;
    }
}
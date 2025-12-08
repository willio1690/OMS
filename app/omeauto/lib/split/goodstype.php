<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/7/14 10:03:32
 * @describe: 拆单商品类型
 * ============================
 */
class omeauto_split_goodstype extends omeauto_split_abstract {

    #拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */

    public function getSpecial() {
        $data = app::get('ome')->model('goods_type')->getList('type_id,name', array());
        return $data;
    }

    #拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf) {
        $split_config = $sdf['split_config'];
        if(empty($split_config) || empty($split_config['goods_type'])) {
            return array(false, '必须有商品类型');
        }
        $goods_type = array();
        foreach ($split_config['goods_type'] as $key => $value) {
            if(!preg_match_all("/^[1-9][0-9]*$/", $split_config['goods_num'][$key]) && $split_config['goods_num'][$key] != 'all'){
                return array(false, '数量必须为正数或all');
            }
            foreach ($value as $v) {
                if(in_array($v, $goods_type)) {
                    return array(false, "商品类型不能重复");
                }
                $goods_type[] = $v;
            }
        }
        if($split_config['goods_other_type']) {
            foreach ($split_config['goods_other_type'] as $key => $value) {
                if(!preg_match_all("/^[1-9][0-9]*$/", $split_config['goods_other_num'][$key]) && $split_config['goods_other_num'][$key] != 'all'){
                    return array(false, '数量必须为正数或all');
                }
                foreach ($value as $v) {
                    if(in_array($v, $goods_type)) {
                        return array(false, "商品类型不能重复");
                    }
                    $goods_type[] = $v;
                }
            }
        }
        return array(true, '保存成功');
    }

    #拆分订单
    /**
     * splitOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function splitOrder(&$group, $splitConfig){
        $arrOrder = $group->getOrders();
        $productGoodsTypeId = $group->getProductGoodsTypeId();
        $splitSkuKind = $this->getSplitSkuKind($arrOrder, $productGoodsTypeId, $splitConfig);
        if(empty($splitSkuKind)) {
            return array(false, '无需拆单');
        }
        foreach ($arrOrder as $ok => $order) {
            $arrOrderId[] = $order['order_id'];
            if($order['order_id'] != $splitSkuKind['order_id']) {
                continue;
            }
            $splitOrder = array();
            $orderItems = array();
            if($splitSkuKind['type'] == 'no_split') {
                $orderItems = $splitSkuKind['items'];
            } else {
                $needOther = false;
                $tmpItems = $order['items'];
                foreach ($splitConfig['goods_type'] as $key => $arrTypeId) {
                    $num = $splitConfig['goods_num'][$key];
                    list($tmpOrderItems, $lessNum) = $this->getTypeIdItems($tmpItems, $productGoodsTypeId, $arrTypeId, $num);
                    if($lessNum) {
                        $needOther = true;
                    }
                    foreach ($tmpOrderItems as $tk => $tv) {
                        $orderItems[$tk] = $tv;
                        if($tmpItems[$tk]) {
                            unset($tmpItems[$tk]);
                        }
                    }
                    if(empty($tmpItems)) {
                        break;
                    }
                }
                if($needOther && $tmpItems && $splitConfig['goods_other_type']) {
                    foreach ($splitConfig['goods_other_type'] as $key => $arrTypeId) {
                        $num = $splitConfig['goods_other_num'][$key];
                        list($tmpOrderItems, $lessNum) = $this->getTypeIdItems($tmpItems, $productGoodsTypeId, $arrTypeId, $num);
                        foreach ($tmpOrderItems as $tk => $tv) {
                            $orderItems[$tk] = $tv;
                            if($tmpItems[$tk]) {
                                unset($tmpItems[$tk]);
                            }
                        }
                        if(empty($tmpItems)) {
                            break;
                        }
                    }
                }
            }
            if($orderItems) {
                $splitOrder[$ok] = $order;
                $splitOrder[$ok]['items'] = $orderItems;
            }
        }
        if($arrOrderId) {
            $group->setSplitOrderId($arrOrderId);
        }
        $group->updateOrderInfo($splitOrder);
        if (empty($splitOrder)) {
            return array(false, '无法拆单');
        }
        return array(true);
    }

    protected function getSplitSkuKind($arrOrder, $productGoodsTypeId, $splitConfig){
        $no_split = array();
        $arrTypeId = array();
        foreach ($splitConfig['goods_type'] as $key => $value) {
            $arrTypeId = array_merge($arrTypeId, $value);
        }
        foreach ($arrOrder as $ok => $order) {
            foreach ($order['items'] as $k => $item) {
                $item['original_num'] = $item['nums'];
                $item['nums'] = $item['original_num'] - $item['split_num'];
                if($item['nums'] < 1) {
                    continue;
                }
                if($item['item_type'] != 'pkg' 
                    && in_array($productGoodsTypeId[$item['product_id']], $arrTypeId)) {
                    return array('type'=>'split', 'order_id'=>$item['order_id']);
                }
                if($no_split) {
                    if($no_split['order_id'] == $item['order_id']) {
                        $no_split['items'][$k] = $item;
                    }
                } else {
                    $no_split = array('type'=>'no_split', 'order_id'=>$item['order_id'], 'items'=>array($k=>$item));
                }
            }
        }
        return $no_split;
    }

    protected function getTypeIdItems($tmpItems, $productGoodsTypeId, $arrTypeId, $num) {
        $tmpOrderItems = array();
        $lessNum = false;
        foreach ($tmpItems as $k => $item) {
            if($num != 'all' && $num < 1) {
                break;
            }
            $item['original_num'] = $item['nums'];
            $item['nums'] = $item['original_num'] - $item['split_num'];
            if($item['item_type'] != 'pkg' 
                && in_array($productGoodsTypeId[$item['product_id']], $arrTypeId)) {
                if($num != 'all') {
                    if($item['nums'] > $num) {
                        $item['nums'] = $num;
                        $num = 0;
                    } else {
                        $num -= $item['nums'];
                    }
                }
                $tmpOrderItems[$k] = $item;
            }
        }
        if($num != 'all' && $num > 0) {
            $lessNum = true;
        }
        return array($tmpOrderItems, $lessNum);
    }
}
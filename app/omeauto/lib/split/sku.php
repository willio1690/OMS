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
 * @describe: 拆单商品, 一个一单
 * ============================
 */
class omeauto_split_sku extends omeauto_split_abstract {

    #拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */

    public function getSpecial() {
        return array();
    }

    #拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf) {
        $splitNum = $sdf['split_config']['split_goods_num'];
        if($splitNum != 'all' && $splitNum < 1) {
            return array(false, '拆分数量请输入正整数或all');
        }
        if($sdf['split_config']['split_goods'] == '2' && empty($sdf['split_config']['split_goods_product'])) {
            return array(false, '请输入基础物料');
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
        $arrOrderId = array();
        $splitSkuKind = $this->getSplitSkuKind($arrOrder, $splitConfig);
        if(empty($splitSkuKind)) {
            return array(false, '无需拆单');
        }
        foreach ($arrOrder as $ok => $order) {
            $arrOrderId[] = $order['order_id'];
            if($order['order_id'] != $splitSkuKind['order_id']) {
                continue;
            }
            $splitOrder = array();
            $splitOrder[$ok] = $order;
            $splitOrder[$ok]['objects'] = $splitSkuKind['objects'];
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

    protected function getSplitSkuKind($arrOrder, $splitConfig){
        $no_split = array();
        foreach ($arrOrder as $ok => $order) {
            foreach ($order['objects'] as $objKey =>$object) {
                foreach ($object['items'] as $k => $item) {
                    $item['original_num'] = $item['nums'];
                    $item['nums'] = $item['original_num'] - $item['split_num'];
                    if($item['nums'] < 1) {
                        continue;
                    }
                    
                    $tmpObject = array($objKey=>$object);
                    $tmpObject[$objKey]['items'] = array($k => $item);
                    if($splitConfig['split_goods'] == '1') {
                        
                        //指定拆分数量
                        if($splitConfig['split_goods_num'] != 'all') {
                            if($item['nums'] > $splitConfig['split_goods_num']) {
                                $tmpObject[$objKey]['items'][$k]['nums'] = $splitConfig['split_goods_num'];
                            }
                        }
                        
                        return array('type'=>'split', 'order_id'=>$item['order_id'], 'objects'=>$tmpObject);
                    }
                    
                    if($splitConfig['split_goods'] == '2') {
                        if(in_array($item['bn'], array_map('trim', explode(',', $splitConfig['split_goods_product'])))) {
                            
                            //指定拆分数量
                            if($splitConfig['split_goods_num'] != 'all') {
                                if($item['nums'] > $splitConfig['split_goods_num']) {
                                    $tmpObject[$objKey]['items'][$k]['nums'] = $splitConfig['split_goods_num'];
                                }
                            }
                            
                            return array('type'=>'split', 'order_id'=>$item['order_id'], 'objects'=>$tmpObject);
                        }
                    }
                    
                    if($no_split) {
                        if($no_split['order_id'] == $item['order_id']) {
                            if(!$no_split['objects'][$objKey]) {
                                $no_split['objects'][$objKey] = $object;
                                $no_split['objects'][$objKey]['items'] = array();
                            }
                            $no_split['objects'][$objKey]['items'][$k] = $item;
                        }
                    } else {
                        $no_split = array('type'=>'no_split', 'order_id'=>$item['order_id'], 'objects'=>$tmpObject);
                    }
                }
            }
        }
        return $no_split;
    }
}
<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/7/9 10:31:56
 * @describe: 按重量拆单
 * ============================
 */
class omeauto_split_skuweight extends omeauto_split_abstract {

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
        $sku_weight = $sdf['split_config']['sku_weight'];
        if(empty($sku_weight) || $sku_weight < 1) {
            return array(false, '重量请输入正数');
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
        $arrOrder   = $group->getOrders();
        $arrOrderId = array();
        $splitOrder = array();
        foreach ($arrOrder as $ok => $order) {
            $arrOrderId[] = $order['order_id'];
        }
        list($rs, $msg) = $this->_splitOrderByWeight($arrOrder, $splitConfig);
        if (!$rs) {
            return array(false, $msg);
        }
        $splitOrder = $arrOrder;
        if ($arrOrderId) {
            $group->setSplitOrderId($arrOrderId);
        }
        $group->updateOrderInfo($splitOrder);
        if (empty($splitOrder)) {
            return array(false, '无法拆单');
        }
        return array(true);
    }

    /**
     * splitOrderFromSplit
     * @param mixed $arrOrder arrOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function splitOrderFromSplit(&$arrOrder, &$group, $splitConfig) {
        return $this->_splitOrderByWeight($arrOrder, $splitConfig);
    }

    protected function _splitOrderByWeight(&$arrOrder, $splitConfig){
        $bmIdNum = array();
        foreach ($arrOrder as $k => $order) {
            foreach ($order['objects'] as $ok => $object) {
                foreach ($object['items'] as $ik => $item) {
                    if($splitConfig['from'] == 'split') {
                        $bmIdNum[$item['product_id']] += $item['nums'];
                        continue;
                    }
                    if ($item['nums'] > $item['split_num']) {
                        $arrOrder[$k]['objects'][$ok]['items'][$ik]['original_num'] = $item['nums'];
                        $arrOrder[$k]['objects'][$ok]['items'][$ik]['nums']         = $nums         = $item['nums'] - $item['split_num'];
                        if ($bmIdNum[$item['product_id']]) {
                            $bmIdNum[$item['product_id']] += $nums;
                        } else {
                            $bmIdNum[$item['product_id']] = $nums;
                        }
                    } else {
                        unset($arrOrder[$k]['objects'][$ok]['items'][$ik]);
                    }
                }
                if (empty($arrOrder[$k]['objects'][$ok]['items'])) {
                    unset($arrOrder[$k]['objects'][$ok]);
                }
            }
            if (empty($arrOrder[$k]['objects'])) {
                unset($arrOrder[$k]);
            }
        }
        if (empty($arrOrder)) {
            return array(false, '可拆单明细为空，无需拆分');
        }
        $bmIdWeight = array();
        $bmRows = app::get('material')->model('basic_material_ext')->getList('bm_id,weight', ['bm_id'=>array_keys($bmIdNum)]);
        foreach ($bmRows as $v) {
            $bmIdWeight[$v['bm_id']] = $v['weight'];
        }
        $bmIdSelNum = [];
        $limitWeight = $splitConfig['sku_weight'];
        foreach ($bmIdNum as $bmId => $num) {
            if($bmIdWeight[$bmId] <= 0) {
                $bmIdSelNum[$bmId] = $num;
                continue;
            }
            if($limitWeight <= 0) {
                continue;
            }
            $limitNum = floor($limitWeight/$bmIdWeight[$bmId]);
            if($limitNum < 1) {
                continue;
            }
            if($limitNum < $num) {
                $bmIdSelNum[$bmId] = $limitNum;
            } else {
                $bmIdSelNum[$bmId] = $num;
            }
            $limitWeight -= $bmIdSelNum[$bmId] * $bmIdWeight[$bmId];
        }
        if(empty($bmIdSelNum)) {
            return array(false, '没有在重量：' . $splitConfig['sku_weight'] . 'g以下的商品');
        }

        #重置订单明细
        foreach ($arrOrder as $k => $order) {
            foreach ($order['objects'] as $ok => $object) {
                foreach ($object['items'] as $ik => $item) {
                    $bmId = $item['product_id'];
                    if ($bmIdSelNum[$bmId] > 0) {
                        if ($bmIdSelNum[$bmId] > $item['nums']) {
                            $bmIdSelNum[$bmId] -= $item['nums'];
                        } else {
                            $arrOrder[$k]['objects'][$ok]['items'][$ik]['nums'] = $bmIdSelNum[$bmId];
                            $bmIdSelNum[$bmId]                              = 0;
                        }
                    } else {
                        unset($arrOrder[$k]['objects'][$ok]['items'][$ik]);
                    }
                }
                if (empty($arrOrder[$k]['objects'][$ok]['items'])) {
                    unset($arrOrder[$k]['objects'][$ok]);
                }
            }
            if (empty($arrOrder[$k]['objects'])) {
                unset($arrOrder[$k]);
            }
        }
        return array(true, '拆单成功');
    }
}
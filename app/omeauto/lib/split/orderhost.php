<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   maxiaochen
 * @Version:  1.0
 * @DateTime: 2024/7/24 17:03:32
 * @describe: 订单达人拆
 * ============================
 */
class omeauto_split_orderhost extends omeauto_split_abstract {

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

        $sdf['split_config']['is_host']['author'] = str_replace('，', ',', trim($sdf['split_config']['is_host']['author']));
        $sdf['split_config']['is_host']['room']   = str_replace('，', ',', trim($sdf['split_config']['is_host']['room']));
        if (empty($sdf['split_config']['is_host']['author'])) {
            return array(false, '按达人信息拆，达人ID为必填');
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
        list($rs, $msg) = $this->getSplitByHost($arrOrder, $splitConfig);
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

    protected function getSplitByHost(&$arrOrder, $splitConfig){
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

        // 根据规则提取数据
        $room_id_config = $splitConfig['is_host']['room'] ? explode(',', $splitConfig['is_host']['room']) : [];
        $authod_id_config = $splitConfig['is_host']['author'] ? explode(',', $splitConfig['is_host']['author']) : [];
        $newArrOrder = [];
        foreach ($arrOrder as $k => $order) {
            foreach ($order['objects'] as $ok => $object) {
                if (!$object['authod_id'] && (!$object['addon'] || !$object['addon']['room_id'])) {
                    continue;
                }
                if ($object['authod_id'] && in_array($object['authod_id'], $authod_id_config)) {
                    if ($authod_id_config) {
                        // room_id配置，需要判断订单的room_id是否在其中
                        if ($object['addon'] && $object['addon']['room_id'] && in_array($object['addon']['room_id'], $authod_id_config)) {
                            $newArrOrder[$k]['objects'][$ok] = true;
                        }
                    } else {
                        // room_id不配置，无需判断room_id
                        $newArrOrder[$k]['objects'][$ok] = true;
                    }
                }
            }
        }

        if(empty($newArrOrder)) {
            return array(false, '没有符合达人ID：' . $splitConfig['is_host']['author'] . ($splitConfig['is_host']['room'] ? '且直播间ID：' . $splitConfig['is_host']['room'] : '') . '的商品');
        }

        #重置订单明细
        foreach ($arrOrder as $k => $order) {
            if (!isset($newArrOrder[$k])) {
                unset($arrOrder[$k]);
            }
            foreach ($order['objects'] as $ok => $object) {
                if (!isset($newArrOrder[$k]['objects'][$ok])) {
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
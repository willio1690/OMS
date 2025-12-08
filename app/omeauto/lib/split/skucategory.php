<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   fire
 * @Version:  1.0
 * @DateTime: 2021/7/12 13:20:58
 * @describe: 按分类拆单
 * ============================
 */
class omeauto_split_skucategory extends omeauto_split_abstract {

    #拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */

    public function getSpecial() {
        if($_POST['from'] == 'split') {
            return [];
        }
        return ['split_type'=>array(
                  'storemax' => '按库存就全拆',
                  'skuweight' => '按商品重量拆',
                  'skuvolume' => '按商品体积拆',
              )];
    }

    #拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf) {
        if($sdf['split_type'] != 'skucategory') {
            return [true];
        }
        if($sdf['split_config']['split_type_2']) {
            list($rs, $msg) = kernel::single('omeauto_split_router', $sdf['split_config']['split_type_2'])->preSaveSdf($sdf);
            if(!$rs) {
                return [false, $msg];
            }
            if($sdf['split_config']['split_type_2'] == $sdf['split_config']['split_type_3']) {
                return [false, '第二层与第三层拆分不能一致'];
            }
        }
        if($sdf['split_config']['split_type_3']) {
            list($rs, $msg) = kernel::single('omeauto_split_router', $sdf['split_config']['split_type_3'])->preSaveSdf($sdf);
            if(!$rs) {
                return [false, $msg];
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
        $arrOrder   = $group->getOrders();
        $arrOrderId = array();
        $splitOrder = array();
        foreach ($arrOrder as $ok => $order) {
            $arrOrderId[] = $order['order_id'];
        }
        list($rs, $msg) = $this->_splitOrderByCategory($arrOrder, $splitConfig);
        if (!$rs) {
            return array(false, $msg);
        }
        if($splitConfig['split_type_2']) {
            list($rs, $msg) = kernel::single('omeauto_split_router', $splitConfig['split_type_2'])->splitOrderFromSplit($arrOrder, $group, $splitConfig);
            if(!$rs) {
                return [false, $msg];
            }
        }
        if($splitConfig['split_type_3']) {
            list($rs, $msg) = kernel::single('omeauto_split_router', $splitConfig['split_type_3'])->splitOrderFromSplit($arrOrder, $group, $splitConfig);
            if(!$rs) {
                return [false, $msg];
            }
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
        return $this->_splitOrderByCategory($arrOrder, $splitConfig);
    }

    /**
     * _splitOrderByCategory
     * @param mixed $arrOrder arrOrder
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function _splitOrderByCategory(&$arrOrder, $splitConfig){
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
      
        $bmIdCategory = array();
        $catIds = array();
        $bmRows = app::get('material')->model('basic_material')->getList('bm_id,cat_id', ['bm_id'=>array_keys($bmIdNum)]);
        foreach ($bmRows as $v) {
            $v['cat_id'] = (int)$v['cat_id'];
            $bmIdCategory[$v['bm_id']] = $v['cat_id'];
            $selCatId = $v['cat_id'];
        }
       #重置订单明细
       foreach ($arrOrder as $k => $order) {
           foreach ($order['objects'] as $ok => $object) {
               foreach ($object['items'] as $ik => $item) {
                   $bmId = $item['product_id'];
                   if ($bmIdCategory[$bmId] == $selCatId) {
                        //
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
           return array(false, '没有明细');
       }
        return array(true);
    }
}
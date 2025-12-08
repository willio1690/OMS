<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/10 16:40:32
 * @describe: 拆单数量
 * ============================
 */
class ome_order_object_splitnum
{

    /**
     * 添加DeliverySplitNum
     * @param mixed $orderItems orderItems
     * @return mixed 返回值
     */

    public function addDeliverySplitNum($orderItems)
    {
        if (!is_array($orderItems)) {
            return true;
        }
        $modelItems = app::get('ome')->model('order_items');
        foreach ($orderItems as $item) {
            $rs = $modelItems->updateSplitNum($item['item_id'], $item['number'], '+');
            if ($rs == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * backDeliverySplitNum
     * @param mixed $deliveryId ID
     * @return mixed 返回值
     */
    public function backDeliverySplitNum($deliveryId)
    {
        $dlyItemsDetailObj = app::get('ome')->model('delivery_items_detail');
        $itemDetailData    = $dlyItemsDetailObj->getList('order_item_id,number', array('delivery_id' => $deliveryId), 0, -1);
        $modelItems        = app::get('ome')->model('order_items');
        foreach ($itemDetailData as $IDVal) {
            $modelItems->updateSplitNum($IDVal['order_item_id'], $IDVal['number'], '-');
        }
    }
}

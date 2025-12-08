<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [抖音平台]获取数据
 * 
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_luban extends ome_event_trigger_shop_data_delivery_common
{
    /**
     * 支持拆单发货、多个订单合并发货
     * 
     * @param int $delivery_id
     * @return array
     */
    public function get_sdf($delivery_id)
    {
        //获取发货单主信息
        $this->__sdf = parent::get_sdf($delivery_id);
        if (!$this->__sdf) {
            return [];
        }
        
        //拆单逻辑
        $this->_get_split_sdf($delivery_id);
        
        //获取发货的商品明细
        $this->_get_delivery_items_sdf($delivery_id);
        //手工编辑替换过平台商品(需要加上被删除的平台oid子订单)
        if($this->__sdf['orderinfo']['is_modify'] == 'true' && $this->__sdf['is_split'] == 1){
            $this->_compatible_order_sync();
        }
        
        //过滤已经退款的oid子订单
        $this->_refund_split_sdf($delivery_id);
        
        return $this->__sdf;
    }
}
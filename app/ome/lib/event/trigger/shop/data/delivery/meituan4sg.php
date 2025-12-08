<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_shop_data_delivery_meituan4sg extends ome_event_trigger_shop_data_delivery_common
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
        
        //获取发货单详细信息（包含配送员信息）
        $this->__sdf['delivery_bill'] = $this->_get_delivery_bill_detail($delivery_id);
        
        //获取仓库信息（包含经纬度）
        $this->__sdf['branch'] = $this->_get_branch($this->__sdf['branch_id']);
        
        //获取发货单标签信息
        $this->__sdf['bill_labels'] = $this->_get_bill_label($delivery_id);
        
        return $this->__sdf;
    }
} 
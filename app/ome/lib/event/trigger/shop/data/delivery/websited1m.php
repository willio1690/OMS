<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 * Class ome_event_trigger_shop_data_delivery_websited1m
 */
class ome_event_trigger_shop_data_delivery_websited1m extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        
        if ($this->__sdf) {
            $this->_get_delivery_items_sdf($delivery_id);
            $this->_get_split_sdf($delivery_id);
//             $this->_get_order_objects_sdf($delivery_id);
//             $this->_get_members_sdf($delivery_id);
//             $this->__sdf['split_model'] = $this->_is_split_switch($delivery_id);
            $this->__sdf['branch'] = $this->_get_branch($this->__deliverys[$delivery_id]['branch_id']);
        }
        
        return $this->__sdf;
    }
    
    /**
     * 获取添加发货单SDF
     *
     * @return void
     * @author
     **/
    public function get_add_delivery_sdf($delivery_id)
    {
        return $this->get_sdf($delivery_id);
    }
}

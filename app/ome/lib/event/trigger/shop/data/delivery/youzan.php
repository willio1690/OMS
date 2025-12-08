<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_youzan extends ome_event_trigger_shop_data_delivery_common
{
    
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        
        if ($this->__sdf) {
            $order = $this->__delivery_orders[$delivery_id];
            
            $this->_get_split_sdf($delivery_id);
            // 如果是拆单
            if ($this->__sdf['is_split'] == 1 && !$this->__sdf['oid_list']) {
                return array();
            }
            $switch = $this->_is_split_switch($delivery_id);
            if ($switch == '2') {
                
                $this->_get_delivery_items_sdf($delivery_id, true, false);
                
                $expresses = [];
                foreach ($this->__sdf['delivery_items'] as $item) {
                    //$item['oid'] = $item['oid'] ? $item['oid'] : $item['order_obj_id'];
                    if(empty($item['oid']) || !in_array($item['oid'], $this->__sdf['oid_list'])) {
                        continue;
                    }
                    $expresses[$item['oid']]['nums'] = $item['nums'];
                    $expresses[$item['oid']]['packages'][$item['logi_no']]['express_no']    = $item['logi_no'];
                    $expresses[$item['oid']]['packages'][$item['logi_no']]['company_name']  = $item['logi_name'];
                    $expresses[$item['oid']]['packages'][$item['logi_no']]['company_code']  = $item['logi_type'];
                    $expresses[$item['oid']]['packages'][$item['logi_no']]['num']           += $item['number'];
                }
                
                $packages = [];
                foreach ($expresses as $oid => $express) {
                    if ($express['nums'] == ceil(array_sum(array_column($express['packages'], 'num')))){
                        $packages[$oid] = array_values($express['packages']);
                    }
                }
                
                if (!$packages) {
                    return array();
                }
                
                $this->__sdf['switch']              = $switch;
                $this->__sdf['is_single_item_send'] = true;
                $this->__sdf['packages']            = $packages;
            }
        }
        
        return $this->__sdf;
    }
    
    protected function _nonsupport_mode_request($delivery_id)
    {
        return true;
    }
}
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
 * @author by mxc <maxiachen@shopex.cn> 
 * @version 
 */
class ome_event_trigger_shop_data_delivery_gs extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        $delivery_bill_list = array();
        if ($this->__sdf) {
        	$delivery_bill_detail = $this->_get_delivery_bill_detail($delivery_id);
        	foreach ($delivery_bill_detail as $d_b_detail) {
        		if ($d_b_detail['logi_no']) {
        			$delivery_bill_list[] = array(
        				'company_code'	=>	$this->__sdf['logi_type'],
		        		'company_name'	=>	$this->__sdf['logi_name'],
		        		'logistics_no'	=>	$d_b_detail['logi_no'],
        			);
        		}
        	}
            $this->__sdf['delivery_bill_list'] = $delivery_bill_list;
        }

        return $this->__sdf;
    }
}
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
 * @author sunjing@shopex.cn
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_jd extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        $this->__sdf['branch'] = $this->_get_branch($this->__deliverys[$delivery_id]['branch_id']);
        return $this->__sdf;
    }

	/**
     * JD 
     *
     * @return void
     * @author 
     **/
    public function get_add_delivery_sdf($delivery_id)
    {
        return $this->get_sdf($delivery_id);
    }
	
}
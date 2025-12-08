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
 * @author fire
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_alibaba4ascp extends ome_event_trigger_shop_data_delivery_common
{
  public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if ($this->__sdf) {
            //获取所有订单明细(包括已删除商品)
            $this->_get_order_all_objects_sdf($delivery_id);

            $this->_get_delivery_items_sdf($delivery_id);

            $order_extend = $this->_get_order_extend($delivery_id);

            $this->__sdf['orderinfo']['sellermemberid'] = $order_extend['sellermemberid'];
            
            //[兼容]阿里巴巴不支持按数量拆单回写
            $this->_format_confirm_oid();
            
            //[兼容]订单全部发货&&是被编辑过,要加入被删除的oid前端平台商品
            $this->_compatible_order_sync();
        }

        return $this->__sdf;
    }
}
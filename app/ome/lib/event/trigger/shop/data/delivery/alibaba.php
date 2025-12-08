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
class ome_event_trigger_shop_data_delivery_alibaba extends ome_event_trigger_shop_data_delivery_common
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
            $this->__sdf['oid_list'] = array_unique(array_column($this->__sdf['delivery_items'], 'oid'));
            $delivery = $this->__deliverys[$delivery_id];
            $shipMent = app::get('ome')->model('shipment_log')->getList('deliveryCode,oid_list', ['shopId'=>$delivery['shop_id'], 'orderBn'=>$this->__sdf['orderinfo']['order_bn']]);
            foreach ($shipMent as $value) {
               if(!$value['oid_list'] || $this->__sdf['logi_no'] == $value['deliveryCode']) {
                   continue;
               }
               $oid_list = explode(',', $value['oid_list']);
               foreach ($this->__sdf['oid_list'] as $k => $v) {
                   if(in_array($v, $oid_list)) {
                       unset($this->__sdf['oid_list'][$k]);
                   }
               }
               foreach ($this->__sdf['delivery_items'] as $k => $v) {
                   if(in_array($v['oid'], $oid_list)) {
                       unset($this->__sdf['delivery_items'][$k]);
                   }
               }
               if(empty($this->__sdf['oid_list'])) {
                   return false;
               }
            }
        }
        
        return $this->__sdf;
    }
}
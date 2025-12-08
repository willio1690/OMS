<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [小红书平台]获取数据
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_xhs extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        
        if ($this->__sdf) {
            $this->_get_product_serial_sdf($delivery_id);
            
            $this->_get_split_sdf($delivery_id);
            
            if($this->__sdf['oid_list']) {
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
                    if(empty($this->__sdf['oid_list'])) {
                        return false;
                    }
                }
            }
        }
        
        return $this->__sdf;
    }
}
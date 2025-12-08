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
class ome_event_trigger_shop_data_delivery_360buy extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if ($this->__sdf) {
            $this->_get_delivery_items_sdf($delivery_id, true);

            if(empty($this->__sdf['delivery_items'])) {
                return array ();
            }

            $delivery_bill_detail = $this->_get_delivery_bill_detail($delivery_id);
            
            $delivery_bill_list = array ();
            foreach ($delivery_bill_detail as $d_b_detail) {
                if ($d_b_detail['logi_no']) {
                    $delivery_bill_list[] = array(
                        'logi_type' => $this->__sdf['logi_type'],
                        'logi_name' => $this->__sdf['logi_name'],
                        'logi_no' => $d_b_detail['logi_no'],
                    );
                }
            }
            $this->__sdf['delivery_bill_list'] = $delivery_bill_list;

            // 唯一码
            $this->__sdf['serial_number'] = $this->_get_product_serial_sn_imei($delivery_id);
        }
        $order    = $this->__delivery_orders[$delivery_id];

        $is_jdlvmi = kernel::single('ome_order_bool_type')->isJDLVMI($order['order_bool_type']);
  
        if ($is_jdlvmi) {
            $this->_get_order_objects_sdf($delivery_id);

            $delivery_package = $this->_get_delivery_package($delivery_id);

            $packages = array();

            foreach($delivery_package as $v){
                $packages[$v['package_bn']][] = $v; 
            }
            $this->__sdf['packages'] = $packages;
        }

        $is_jdzd = kernel::single('ome_bill_label')->isJDZD($order['order_id']);

        if($is_jdzd){
            $this->__sdf['is_jdzd'] = true;
        }

        return $this->__sdf;
    }
}

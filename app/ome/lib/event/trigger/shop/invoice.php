<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#发票处理
class ome_event_trigger_shop_invoice{
 /**
  * ome调用发票统一方法
  * @param string $receive_type  (create_invoice_order、cancel_invoice_order)
  * @param string $type  (order_create、order_cancel、order_detail_basic、batch_create)
  */
   public function process($params,$receive_type='create_invoce_order',$type){
      if($this->check_invoice_valid()){
         kernel::single('invoice_event_receive_einvoice')->$receive_type($params,$type);
      }
      return true;
   }
   #检查发票是否有效
   function check_invoice_valid(){
      $is_install = kernel::single('ome_func')->check_install_invoice();
     
      if($is_install ){
          return true;
      }
      return false;
   }

    public function update_register_no($data) {
        if($this->check_invoice_valid()){
            $arr_filter = array(
                'order_id' => $data['order_id'],
                'is_status|in' => array(0,1),
            );
            $upData = array('ship_tax'=>$data['register_no'], 'mode'=>$data['invoice_kind']);
            app::get('invoice')->model('order')->update($upData, $arr_filter);
        }
    }
}
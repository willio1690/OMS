<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_bill_label_delivery {

    /**
     * ToDeliveryPresaleLabel
     * @param mixed $orders orders
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function ToDeliveryPresaleLabel($orders, $delivery_id) {

        if($orders['order_type'] == 'presale' && $orders['step_trade_status'] == 'FRONT_PAID_FINAL_NOPAID' && kernel::single('ome_order_func')->checkPresaleOrder()){

            $label_code = 'SOMS_PRESALEPARTPAD';
            kernel::single('ome_bill_label')->markBillLabel($delivery_id, '', $label_code, 'ome_delivery');
        }
        
        
    }

    /**
     * isPrepackage
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function isPrepackage($delivery_id){

        //京东变成可发货
        $ordLabelObj = app::get('ome')->model('bill_label');
       
        $bills = $ordLabelObj->dump(array('label_code'=>'SOMS_PRESALEPARTPAD','bill_type'=>'ome_delivery','bill_id'=>$delivery_id),'bill_id');

        
        if($bills){
           return true;

        }

        return false;

    }

    /**
     * isPrepayed
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function isPrepayed($order_id){

        $ordLabelObj = app::get('ome')->model('bill_label');
       
        $bills = $ordLabelObj->dump(array('label_code'=>'SOMS_PREPAYED','bill_type'=>'order','bill_id'=>$order_id),'bill_id');

        
        if($bills){
           return true;

        }

        return false;
    }


    /**
     * ToChangeOrderLabel
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function ToChangeOrderLabel($order_id) {

        $label_code = 'SOMS_CHANGE_CANCEL';
        kernel::single('ome_bill_label')->markBillLabel($order_id, '', $label_code, 'order');
        
    }

}
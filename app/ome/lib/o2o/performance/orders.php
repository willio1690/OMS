<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_o2o_performance_orders
{

    //门店履约订单状态标记更新
    /**
     * 更新ProcessStatus
     * @param mixed $order_id ID
     * @param mixed $action action
     * @return mixed 返回值
     */
    public function updateProcessStatus($order_id, $action){
        if(empty($order_id)){
            return false;
        }

        switch($action){
            case 'confirm':
                $status = '1';
                break;
            case 'refuse':
                $status = '2';
                break;
            case 'accept':
                $status = '3';
                break;
            case 'consign':
                $status = '4';
                break;
            case 'sign':
                $status = '5';
                break;
        }

        if($status){
            $orderExtendObj = app::get('ome')->model('order_extend');
            $order_extend_info = array('order_id'=>$order_id,'store_process_status'=>$status);
            return $orderExtendObj->save($order_extend_info);
        }else{
            return false;
        }
    }
}